<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan 'menunggu pembayaran' dan 'gagal' ke dalam ENUM
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status ENUM('belum dibayar', 'menunggu pembayaran', 'sudah dibayar', 'dp', 'gagal') NOT NULL DEFAULT 'belum dibayar'");
    }

    public function down(): void
    {
        // Kembalikan ke enum awal jika rollback (pastikan tidak ada data berstatus baru saat rollback)
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status ENUM('belum dibayar', 'dp', 'sudah dibayar') NOT NULL DEFAULT 'belum dibayar'");
    }
};