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
        // This table stores the specific additionals for each transaction
        Schema::create('additional_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi', 'transaction_id')->onDelete('cascade');
            $table->foreignId('additional_id')->constrained('additionals')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Price of the additional at the time of transaction
            $table->timestamps();
        });

        // Add a total_price column to the main transaksi table
        Schema::table('transaksi', function (Blueprint $table) {
            $table->decimal('total_price', 15, 2)->default(0)->after('process_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('total_price');
        });
        Schema::dropIfExists('additional_transaksi');
    }
};