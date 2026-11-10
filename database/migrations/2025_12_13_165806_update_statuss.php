<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kita perlu mengubah definisi kolom ENUM process_status
        // Karena Doctrine DBAL tidak mendukung modifikasi ENUM dengan mudah, kita gunakan raw SQL
        // Sesuaikan dengan driver database Anda (MySQL diasumsikan di sini)
        
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM(
            'Pelanggan Belum Foto',
            'Pelanggan Pilih Foto',
            'Siap Edit dan Cetak', 
            'Proses Edit dan Cetak', 
            'Proses Edit',
            'Proses Cetak',
            'Selesai'
        ) DEFAULT 'Pelanggan Belum Foto'");

        // Note: Saya tetap menyertakan status lama ('Siap Edit dan Cetak', dll) 
        // agar data lama tidak rusak/hilang saat migrasi. 
        // Aplikasi akan mulai menggunakan status baru ('Proses Edit', 'Proses Cetak') mulai sekarang.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke status lama jika rollback (Hati-hati data dengan status baru bisa error)
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM(
            'Pelanggan Belum Foto',
            'Pelanggan Pilih Foto',
            'Siap Edit dan Cetak',
            'Proses Edit dan Cetak',
            'Selesai'
        ) DEFAULT 'Pelanggan Belum Foto'");
    }
};