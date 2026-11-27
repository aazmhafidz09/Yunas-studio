<?php

namespace Tests\Feature;

use App\Models\Additional;
use App\Models\BookingAdditionalRequest;
use App\Models\Gateway;
use App\Models\Packet;
use App\Models\Product;
use App\Models\RescheduleRequest;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingAdditionalAndRescheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::dropAllTables();

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

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
            $table->unsignedBigInteger('product_id');
            $table->string('name');
            $table->integer('price')->default(0);
            $table->integer('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('additionals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('order_id')->nullable();
            $table->date('session_date')->nullable();
            $table->string('session_time')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('receipt_code')->nullable();
            $table->string('edit_selection_status', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('transaksi_gateway', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('order_id')->nullable()->unique();
            $table->date('session_date')->nullable();
            $table->string('session_time')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name');
            $table->string('phone_number', 20)->nullable();
            $table->unsignedBigInteger('packet_id')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('process_status')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('dp_amount', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('receipt_code')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_additional_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('additional_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->unique();
            $table->date('old_schedule_date');
            $table->string('old_schedule_time');
            $table->date('new_schedule_date');
            $table->string('new_schedule_time');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Role::insert([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'User', 'created_at' => now(), 'updated_at' => now()],
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

    public function test_booking_additionals_require_admin_approve_or_reject(): void
    {
        $admin = $this->makeUser(1, 'admin_add');
        $customer = $this->makeUser(3, '08120000111');

        $product = new Product();
        $product->name = 'Studio Wisuda';
        $product->save();

        $packet = new Packet();
        $packet->product_id = $product->id;
        $packet->name = 'Paket Silver';
        $packet->price = 300000;
        $packet->duration_minutes = 60;
        $packet->is_active = true;
        $packet->save();

        $add1 = Additional::create(['name' => 'Extra Edit 5 Foto', 'price' => 50000]);
        $add2 = Additional::create(['name' => 'Makeup Artis', 'price' => 150000]);

        $sessionDate = Carbon::tomorrow()->toDateString();

        // Customer creates online booking with 2 additionals
        $res = $this->postJson(route('booking.snap-token'), [
            'customer_name' => 'Nadia',
            'phone_number' => '081299990000',
            'product_id' => $product->id,
            'packet_id' => $packet->id,
            'session_date' => $sessionDate,
            'session_time' => '10:00',
            'payment_option' => 'onLocation',
            'additionals' => [
                $add1->id => ['quantity' => 2],
                $add2->id => ['quantity' => 1],
            ],
        ]);

        $res->assertStatus(200)
            ->assertJson(['success' => true, 'has_pending_additionals' => true]);

        $gateway = Gateway::firstOrFail();
        // Initial total_price MUST be packet price only (300,000), not including unapproved additionals
        $this->assertEquals(300000.0, (float) $gateway->total_price);

        $reqs = BookingAdditionalRequest::where('transaction_id', $gateway->transaction_id)->orderBy('id')->get();
        $this->assertCount(2, $reqs);
        $this->assertEquals('pending', $reqs[0]->status);
        $this->assertEquals('pending', $reqs[1]->status);

        // Regular customer cannot access approval page
        $this->actingAs($customer)
            ->get(route('booking-additionals.index'))
            ->assertStatus(403);

        // Admin views approval page
        $this->actingAs($admin)
            ->get(route('booking-additionals.index'))
            ->assertStatus(200)
            ->assertSee('Extra Edit 5 Foto')
            ->assertSee('Makeup Artis');

        // Admin APPROVES add1 (2 x 50,000 = 100,000) -> total_price becomes 400,000
        $this->actingAs($admin)
            ->post(route('booking-additionals.approve', $reqs[0]->id))
            ->assertRedirect();

        $reqs[0]->refresh();
        $gateway->refresh();
        $this->assertEquals('approved', $reqs[0]->status);
        $this->assertEquals(400000.0, (float) $gateway->total_price);

        // Admin REJECTS add2 with note -> total_price remains 400,000
        $this->actingAs($admin)
            ->post(route('booking-additionals.reject', $reqs[1]->id), [
                'admin_note' => 'MUA sedang penuh di jam tersebut',
            ])
            ->assertRedirect();

        $reqs[1]->refresh();
        $gateway->refresh();
        $this->assertEquals('rejected', $reqs[1]->status);
        $this->assertEquals('MUA sedang penuh di jam tersebut', $reqs[1]->admin_note);
        $this->assertEquals(400000.0, (float) $gateway->total_price);
    }

    public function test_reschedule_lookup_and_submission_work_with_invoice_number(): void
    {
        $admin = $this->makeUser(1, 'admin_rs');

        $product = new Product();
        $product->name = 'Studio Keluarga';
        $product->save();

        $packet = new Packet();
        $packet->product_id = $product->id;
        $packet->name = 'Paket Family';
        $packet->price = 400000;
        $packet->duration_minutes = 60;
        $packet->is_active = true;
        $packet->save();

        $oldDate = Carbon::tomorrow()->toDateString();
        $newDate = Carbon::tomorrow()->addDays(2)->toDateString();

        $gateway = Gateway::create([
            'customer_name' => 'Raka',
            'phone_number' => '085199991234',
            'packet_id' => $packet->id,
            'session_date' => $oldDate,
            'session_time' => '11:00',
            'status' => 'dp',
            'payment_type' => 'Transfer',
            'process_status' => 'Pelanggan Belum Foto',
            'total_price' => 400000,
            'dp_amount' => 100000,
            'receipt_code' => 'INV-20261127-77',
            'order_id' => 'ORD-20261127-77',
        ]);

        // 1. Lookup by exact invoice number (receipt_code)
        $this->postJson(route('schedule.cari-booking'), ['identifier' => 'INV-20261127-77'])
            ->assertStatus(200)
            ->assertJsonPath('bookings.0.transaction_id', $gateway->transaction_id)
            ->assertJsonPath('bookings.0.receipt_code', 'INV-20261127-77');

        // 2. Lookup with '#' prefix, slashes instead of dashes, and lowercase
        $this->postJson(route('schedule.cari-booking'), ['identifier' => '#inv/20261127/77'])
            ->assertStatus(200)
            ->assertJsonPath('bookings.0.transaction_id', $gateway->transaction_id);

        // 3. Submit reschedule using invoice number
        $this->postJson(route('schedule.reschedule'), [
            'transaction_id' => $gateway->transaction_id,
            'identifier' => '#INV/20261127/77',
            'new_date' => $newDate,
            'new_time' => '14:00',
            'reason' => 'Berhalangan hadir di tanggal awal',
        ])->assertStatus(200)
          ->assertJsonPath('new_date', $newDate)
          ->assertJsonPath('new_time', '14:00');

        $req = RescheduleRequest::where('transaction_id', $gateway->transaction_id)->firstOrFail();
        $this->assertEquals('pending', $req->status);

        // 4. Admin approves reschedule -> Gateway schedule updates!
        $this->actingAs($admin)
            ->post(route('reschedule.approve', $req->id))
            ->assertRedirect();

        $gateway->refresh();
        $this->assertEquals($newDate, $gateway->session_date);
        $this->assertEquals('14:00', $gateway->session_time);
    }
}
