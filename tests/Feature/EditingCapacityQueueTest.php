<?php

namespace Tests\Feature;

use App\Models\EditingCapacity;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\EditingQueueService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EditingCapacityQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('role_id')->default(3);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('packets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->timestamps();
        });

        Schema::create('additionals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('additional_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('packet_id');
            $table->unsignedBigInteger('additional_id');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        Schema::create('additional_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_id');
            $table->unsignedBigInteger('additional_id');
            $table->integer('quantity')->default(1);
            $table->integer('price')->default(0);
            $table->timestamps();
        });

        Schema::create('selected_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_url');
            $table->boolean('is_print')->default(false);
            $table->timestamps();
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name');
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('packet_id')->nullable();
            $table->string('status')->default('sudah dibayar');
            $table->string('process_status')->default('Pelanggan Pilih Foto');
            $table->string('payment_type')->nullable();
            $table->string('receipt_code')->unique();
            $table->integer('total_price')->default(0);
            $table->integer('dp_amount')->nullable();
            $table->integer('discount')->default(0);
            $table->text('note')->nullable();
            $table->text('url_images')->nullable();
            $table->text('url_photos_result')->nullable();
            $table->text('select_edit_photo')->nullable();
            $table->text('select_print_photo')->nullable();
            $table->string('edit_selection_status', 20)->nullable();
            $table->text('edit_selection_note')->nullable();
            $table->timestamp('edit_selection_submitted_at')->nullable();
            $table->timestamp('edit_selection_reviewed_at')->nullable();
            $table->timestamp('editing_queued_at')->nullable();
            $table->timestamp('editing_completed_at')->nullable();
            $table->string('order_id')->nullable();
            $table->date('session_date')->nullable();
            $table->string('session_time')->nullable();
            $table->timestamps();
        });

        Schema::create('editing_capacities', function (Blueprint $table) {
            $table->id();
            $table->string('date_key', 20)->unique();
            $table->date('date')->nullable()->index();
            $table->unsignedInteger('capacity')->default(20);
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        EditingCapacity::create([
            'date_key' => EditingCapacity::DEFAULT_KEY,
            'date' => null,
            'capacity' => 20,
            'note' => 'Default',
        ]);
    }

    private function makeUser(int $roleId, string $username): User
    {
        return User::create([
            'name' => 'User ' . $username,
            'username' => $username,
            'password' => Hash::make('secret'),
            'is_active' => true,
            'role_id' => $roleId,
        ]);
    }

    private function makeTransaksi(array $overrides = []): Transaksi
    {
        static $seq = 1;
        $defaults = [
            'customer_name' => 'Pelanggan ' . $seq,
            'phone_number' => '08123456' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'status' => 'sudah dibayar',
            'process_status' => 'Proses Edit',
            'receipt_code' => 'INV/20261126/' . (900 + $seq),
            'total_price' => 150000,
        ];
        $seq++;

        $trx = new Transaksi();
        foreach (array_merge($defaults, $overrides) as $k => $v) {
            $trx->{$k} = $v;
        }
        $trx->save();

        return $trx;
    }

    public function test_admin_can_update_default_capacity_and_date_override_while_user_is_forbidden(): void
    {
        $admin = $this->makeUser(1, 'admin1');
        $customer = $this->makeUser(3, '08120000001');

        // Customer cannot access admin editing capacity page
        $this->actingAs($customer)
            ->get(route('editing-capacity.index'))
            ->assertStatus(403);

        // Admin can view page and update default capacity
        $this->actingAs($admin)
            ->get(route('editing-capacity.index'))
            ->assertStatus(200)
            ->assertSee('Kapasitas Editing Per Hari');

        $this->actingAs($admin)
            ->put(route('editing-capacity.update-default'), [
                'capacity' => 15,
                'note' => 'Kapasitas baru',
            ])
            ->assertRedirect(route('editing-capacity.index'));

        $this->assertEquals(15, app(EditingQueueService::class)->getDefaultCapacity());

        // Admin sets a date override for today
        $today = Carbon::today()->toDateString();
        $this->actingAs($admin)
            ->post(route('editing-capacity.store-date'), [
                'date' => $today,
                'capacity' => 25,
                'note' => 'Lembur hari ini',
            ])
            ->assertRedirect(route('editing-capacity.index'));

        $this->assertEquals(25, app(EditingQueueService::class)->getCapacityForDate(Carbon::today()));

        // Admin deletes the override -> reverts to default (15)
        $override = EditingCapacity::where('date_key', $today)->firstOrFail();
        $this->actingAs($admin)
            ->delete(route('editing-capacity.destroy-date', $override->id))
            ->assertRedirect(route('editing-capacity.index'));

        $this->assertEquals(15, app(EditingQueueService::class)->getCapacityForDate(Carbon::today()));
    }

    public function test_queue_rollover_respects_daily_capacity_holidays_and_completed_today(): void
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $dayAfter = $today->copy()->addDays(2);

        // Default = 3/day, today override = 2, tomorrow override = 0 (libur)
        EditingCapacity::where('date_key', EditingCapacity::DEFAULT_KEY)->update(['capacity' => 3]);
        EditingCapacity::create(['date_key' => $today->toDateString(), 'date' => $today, 'capacity' => 2]);
        EditingCapacity::create(['date_key' => $tomorrow->toDateString(), 'date' => $tomorrow, 'capacity' => 0]);

        // 1 job already completed today (consumes 1 of today's 2 capacity)
        $this->makeTransaksi([
            'process_status' => 'Selesai',
            'url_photos_result' => 'https://drive.google.com/drive/folders/done1',
            'editing_completed_at' => $today->copy()->addHours(9),
        ]);

        // 3 active jobs entering queue FIFO
        $t1 = $this->makeTransaksi([
            'edit_selection_status' => Transaksi::EDIT_SELECTION_APPROVED,
            'process_status' => 'Proses Edit',
            'editing_queued_at' => $today->copy()->addHours(10),
        ]);
        $t2 = $this->makeTransaksi([
            'edit_selection_status' => Transaksi::EDIT_SELECTION_SUBMITTED,
            'process_status' => 'Pelanggan Pilih Foto',
            'editing_queued_at' => $today->copy()->addHours(11),
        ]);
        $t3 = $this->makeTransaksi([
            'edit_selection_status' => null,
            'process_status' => 'Proses Edit',
            'editing_queued_at' => $today->copy()->addHours(12),
        ]);

        $service = app(EditingQueueService::class);
        $schedule = $service->buildSchedule($today);

        $this->assertEquals(2, $schedule['today_capacity']);
        $this->assertEquals(1, $schedule['today_completed']);
        $this->assertEquals(1, $schedule['today_queued']);
        $this->assertEquals(0, $schedule['today_remaining']);
        $this->assertEquals(3, $schedule['total_queued']);

        // t1 takes today's 2nd slot (since 1 was completed today)
        $q1 = $schedule['by_transaction_id'][$t1->transaction_id];
        $this->assertEquals(1, $q1['queue_number']);
        $this->assertEquals($today->toDateString(), $q1['estimated_date_string']);
        $this->assertEquals(2, $q1['day_slot']);
        $this->assertEquals(2, $q1['day_capacity']);

        // t2 & t3 skip tomorrow (0 capacity) and land on dayAfter (capacity 3)
        $q2 = $schedule['by_transaction_id'][$t2->transaction_id];
        $this->assertEquals(2, $q2['queue_number']);
        $this->assertEquals($dayAfter->toDateString(), $q2['estimated_date_string']);
        $this->assertEquals(1, $q2['day_slot']);
        $this->assertEquals(3, $q2['day_capacity']);

        $q3 = $schedule['by_transaction_id'][$t3->transaction_id];
        $this->assertEquals(3, $q3['queue_number']);
        $this->assertEquals($dayAfter->toDateString(), $q3['estimated_date_string']);
        $this->assertEquals(2, $q3['day_slot']);
        $this->assertEquals(3, $q3['day_capacity']);
    }

    public function test_user_photo_result_page_displays_queue_number_message(): void
    {
        $customer1 = $this->makeUser(3, '08120000011');
        $customer2 = $this->makeUser(3, '08120000022');

        // First transaction in queue (queue #1)
        $this->makeTransaksi([
            'user_id' => $customer1->id,
            'phone_number' => $customer1->username,
            'receipt_code' => 'INV/20261126/995',
            'process_status' => 'Proses Edit',
            'edit_selection_status' => Transaksi::EDIT_SELECTION_APPROVED,
            'editing_queued_at' => now()->subMinutes(10),
        ]);

        // Second transaction in queue (queue #2)
        $trx2 = $this->makeTransaksi([
            'user_id' => $customer2->id,
            'phone_number' => $customer2->username,
            'receipt_code' => 'INV/20261126/996',
            'process_status' => 'Proses Edit',
            'edit_selection_status' => Transaksi::EDIT_SELECTION_APPROVED,
            'editing_queued_at' => now()->subMinutes(5),
        ]);

        $response = $this->withoutExceptionHandling()
            ->actingAs($customer2)
            ->get(route('user.foto.index', [
                'transaksi_id' => $trx2->transaction_id,
                'type' => 'result',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Hasil Foto Edit Belum Tersedia');
        $response->assertSee('Foto pilihan Anda', false);
        $response->assertSee('antrian ke - 2', false);
        $response->assertSee('#INV/20261126/996', false);
        $response->assertSee('belum selesai diedit.', false);
        $response->assertSee('Hasilnya akan tampil di sini setelah proses editing selesai.', false);
    }

    public function test_submit_and_withdraw_photo_selection_updates_editing_queue_automatically(): void
    {
        $customer = $this->makeUser(3, '08120000033');

        $trx = $this->makeTransaksi([
            'user_id' => $customer->id,
            'phone_number' => $customer->username,
            'receipt_code' => 'INV/20261127/101',
            'process_status' => 'Pelanggan Pilih Foto',
            'edit_selection_status' => null,
            'editing_queued_at' => null,
        ]);

        \App\Models\SelectedPhoto::create([
            'transaction_id' => $trx->transaction_id,
            'file_url' => 'https://drive.google.com/file/d/abc123/view',
            'is_print' => false,
        ]);

        $service = app(EditingQueueService::class);
        $this->assertNull($service->getQueueInfoForTransaction($trx));

        // Submit -> enters queue as #1
        $this->actingAs($customer)
            ->post(route('user.foto.submit-edit', $trx->transaction_id))
            ->assertRedirect();

        $trx->refresh();
        $this->assertNotNull($trx->editing_queued_at);
        $info = $service->getQueueInfoForTransaction($trx);
        $this->assertNotNull($info);
        $this->assertEquals(1, $info['queue_number']);

        // Withdraw -> leaves queue
        $this->actingAs($customer)
            ->post(route('user.foto.withdraw-edit', $trx->transaction_id))
            ->assertRedirect();

        $trx->refresh();
        $this->assertNull($trx->editing_queued_at);
        $this->assertNull($service->getQueueInfoForTransaction($trx));
    }
}
