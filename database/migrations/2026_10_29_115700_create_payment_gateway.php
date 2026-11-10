<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_gateway', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('order_id')->nullable()->unique();
            $table->date('session_date')->nullable();
            $table->time('session_time')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('phone_number', 20)->nullable();
            $table->unsignedBigInteger('packet_id')->nullable()->index();
            $table->string('status')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('process_status')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('dp_amount', 12, 2)->default(0)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('receipt_code')->nullable()->index();
            $table->string('snap_token')->nullable();
            $table->string('midtrans_status')->nullable();
            $table->text('note')->nullable();
            $table->text('url_images')->nullable();
            $table->text('url_photos_result')->nullable();
            $table->text('select_edit_photo')->nullable();
            $table->text('select_print_photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_gateway');
    }
};