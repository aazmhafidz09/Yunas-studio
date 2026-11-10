<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Memperbarui kolom ENUM untuk mendukung status baru
        // Kita gunakan Raw SQL agar kompatibel dengan berbagai versi Laravel/Doctrine
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM(
            'Pelanggan Belum Foto',
            'Pelanggan Pilih Foto',
            'Siap Edit dan Cetak', 
            'Proses Edit dan Cetak', 
            'Proses Edit',
            'Proses Cetak',
            'Selesai'
        ) DEFAULT 'Pelanggan Belum Foto'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke definisi lama jika rollback (Hati-hati potensi data loss untuk status baru)
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM(
            'Pelanggan Belum Foto',
            'Pelanggan Pilih Foto',
            'Siap Edit dan Cetak',
            'Proses Edit dan Cetak',
            'Selesai'
        ) DEFAULT 'Pelanggan Belum Foto'");
    }
};