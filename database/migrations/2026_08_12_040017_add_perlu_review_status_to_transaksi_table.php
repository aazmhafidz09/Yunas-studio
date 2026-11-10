<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status ENUM(
            'belum dibayar',
            'menunggu pembayaran',
            'sudah dibayar',
            'dp',
            'gagal',
            'perlu_review'
        ) NOT NULL");
    }

    public function down(): void
    {
        // PENTING: pastikan tidak ada baris berstatus 'perlu_review'
        // sebelum rollback, atau rollback akan gagal/data akan truncated.
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status ENUM(
            'belum dibayar',
            'menunggu pembayaran',
            'sudah dibayar',
            'dp',
            'gagal'
        ) NOT NULL");
    }
};