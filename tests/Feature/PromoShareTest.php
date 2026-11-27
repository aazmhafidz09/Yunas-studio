<?php

namespace Tests\Feature;

use App\Models\Packet;
use App\Models\Product;
use App\Models\Promo;
use App\Models\PromoShare;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromoShareTest extends TestCase
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

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name');
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('packet_id')->nullable();
            $table->string('status')->default('sudah dibayar');
            $table->string('process_status')->default('Selesai');
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

        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('content');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_id');
            $table->unsignedBigInteger('transaction_id');
            $table->string('phone_number', 30)->nullable();
            $table->unsignedBigInteger('shared_by')->nullable();
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamps();
        });
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

    public function test_admin_can_crud_promos_and_customer_is_forbidden(): void
    {
        $admin = $this->makeUser(1, 'admin1');
        $customer = $this->makeUser(3, '08120000001');

        $this->actingAs($customer)
            ->get(route('promos.index'))
            ->assertStatus(403);

        // Create promo
        $this->actingAs($admin)
            ->post(route('promos.store'), [
                'name' => 'Promo Akhir Tahun 20%',
                'content' => 'Dapatkan diskon 20% untuk semua sesi foto bulan ini!',
            ])
            ->assertRedirect();

        $promo = Promo::firstOrFail();
        $this->assertEquals('Promo Akhir Tahun 20%', $promo->name);

        // Update promo
        $this->actingAs($admin)
            ->put(route('promos.update', $promo->id), [
                'name' => 'Promo Akhir Tahun 30%',
                'content' => 'Diskon 30% spesial pelanggan lama {nama}!',
            ])
            ->assertRedirect(route('promos.index'));

        $promo->refresh();
        $this->assertEquals('Promo Akhir Tahun 30%', $promo->name);

        // Delete promo
        $this->actingAs($admin)
            ->delete(route('promos.destroy', $promo->id))
            ->assertRedirect(route('promos.index'));

        $this->assertDatabaseCount('promos', 0);
    }

    public function test_promo_page_loads_previous_transactions_and_builds_whatsapp_share_links(): void
    {
        $admin = $this->makeUser(1, 'admin2');

        $product = new Product();
        $product->name = 'Studio Wisuda';
        $product->save();

        $packet = new Packet();
        $packet->product_id = $product->id;
        $packet->name = 'Paket Gold';
        $packet->price = 350000;
        $packet->save();

        // Previous transaction from 3 months ago
        $oldTrx = new Transaksi();
        $oldTrx->customer_name = 'Siti Lama';
        $oldTrx->phone_number = '081234567890';
        $oldTrx->packet_id = $packet->id;
        $oldTrx->status = 'sudah dibayar';
        $oldTrx->process_status = 'Selesai';
        $oldTrx->receipt_code = 'INV/20260815/001';
        $oldTrx->total_price = 350000;
        $oldTrx->created_at = Carbon::now()->subMonths(3);
        $oldTrx->save();

        // Recent transaction for another customer
        $newTrx = new Transaksi();
        $newTrx->customer_name = 'Raka Baru';
        $newTrx->phone_number = '085199998888';
        $newTrx->packet_id = $packet->id;
        $newTrx->status = 'dp';
        $newTrx->process_status = 'Proses Edit';
        $newTrx->receipt_code = 'INV/20261127/002';
        $newTrx->total_price = 350000;
        $newTrx->save();

        $promo1 = Promo::create([
            'name' => 'Promo Lebaran',
            'content' => 'Terima kasih sudah ambil {paket}. Ada voucher 25% untuk sesi berikutnya!',
            'created_by' => $admin->id,
        ]);

        $promo2 = Promo::create([
            'name' => 'Promo Wisuda',
            'content' => 'Diskon 15% khusus paket wisuda!',
            'created_by' => $admin->id,
        ]);

        // Both old (3 months ago) and new transactions appear with WhatsApp buttons for BOTH promos
        $response = $this->actingAs($admin)
            ->get(route('promos.index'));

        $response->assertStatus(200);
        $response->assertSee('Bagikan Promo');
        $response->assertSee('Siti Lama');
        $response->assertSee('Raka Baru');
        $response->assertSee('Promo Lebaran');
        $response->assertSee('Promo Wisuda');
        $response->assertSee('https://api.whatsapp.com/send?phone=6281234567890', false);
        $response->assertSee('https://api.whatsapp.com/send?phone=6285199998888', false);
        $response->assertSee('data-promo-id="' . $promo1->id . '"', false);
        $response->assertSee('data-promo-id="' . $promo2->id . '"', false);

        // Verify placeholder replacement in Promo::buildMessage
        $msg = Promo::buildMessage($promo1->name, $promo1->content, $oldTrx->load('packet.product'));
        $this->assertStringContainsString('Halo Kak *Siti Lama*', $msg);
        $this->assertStringContainsString('*Promo Lebaran*', $msg);
        $this->assertStringContainsString('Paket Gold', $msg);

        // Log share via AJAX endpoint for promo1
        $this->actingAs($admin)
            ->postJson(route('promos.log-share'), [
                'promo_id' => $promo1->id,
                'transaction_id' => $oldTrx->transaction_id,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(1, PromoShare::where('promo_id', $promo1->id)->where('transaction_id', $oldTrx->transaction_id)->count());

        // Reload page -> sent checkmark icon appears for promo1 on oldTrx
        $this->actingAs($admin)
            ->get(route('promos.index'))
            ->assertSee('js-sent-icon', false);
    }

    public function test_unique_phone_filter_keeps_latest_transaction_per_customer(): void
    {
        $admin = $this->makeUser(1, 'admin3');

        $t1 = new Transaksi();
        $t1->customer_name = 'Aulia Pertama';
        $t1->phone_number = '085511112222';
        $t1->receipt_code = 'INV/20260701/001';
        $t1->total_price = 100000;
        $t1->save();

        $t2 = new Transaksi();
        $t2->customer_name = 'Aulia Kedua';
        $t2->phone_number = '085511112222';
        $t2->receipt_code = 'INV/20261127/009';
        $t2->total_price = 200000;
        $t2->save();

        $response = $this->actingAs($admin)
            ->get(route('promos.index', ['unique_phone' => 1]));

        $response->assertStatus(200);
        $response->assertSee('INV/20261127/009');
        $response->assertDontSee('INV/20260701/001');
    }
}
