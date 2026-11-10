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
        Schema::table('transaksi', function (Blueprint $table) {
            // Token Snap Midtrans — disimpan sementara sampai user bayar
            $table->string('snap_token')->nullable()->after('receipt_code');
 
            // Status khusus Midtrans: pending | success | failed | expired
            // Berbeda dengan kolom 'status' yang untuk admin (belum dibayar/dp/sudah dibayar)
            $table->string('midtrans_status')->nullable()->after('snap_token');
        });
    }
 
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'midtrans_status']);
        });
    }
};
