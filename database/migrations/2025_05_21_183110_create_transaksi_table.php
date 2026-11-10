<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('customer_name', 50)->nullable(false);
            $table->enum('status', ['belum dibayar', 'dp', 'sudah dibayar'])->default('belum dibayar')->nullable(false);
            $table->string('receipt_code', 50)->unique()->nullable(false);
            $table->string('temporary_link', 50)->nullable();
            $table->string('selected_photos', 255)->nullable();
            $table->string('final_link', 50)->nullable();
            $table->date('transaction_date')->nullable(false);
            $table->timestamps();
            $table->boolean('isActive')->default(true)->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
