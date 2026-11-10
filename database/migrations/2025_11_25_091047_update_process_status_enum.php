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
        // 1. Ubah dulu kolom menjadi string/varchar agar bisa menampung data sementara saat kita rename
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status VARCHAR(50)");

        // 2. Update data lama ke nama status baru (Mapping Data)
        
        // "Belum Foto" -> "Pelanggan Belum Foto"
        DB::table('transaksi')->where('process_status', 'Belum Foto')->update(['process_status' => 'Pelanggan Belum Foto']);
        
        // "Pilih Foto" -> "Pelanggan Pilih Foto"
        DB::table('transaksi')->where('process_status', 'Pilih Foto')->update(['process_status' => 'Pelanggan Pilih Foto']);
        
        // Gabungkan "Siap Edit" & "Siap Cetak" -> "Siap Edit dan Cetak"
        DB::table('transaksi')->whereIn('process_status', ['Siap Edit', 'Siap Cetak'])->update(['process_status' => 'Siap Edit dan Cetak']);
        
        // Gabungkan "Proses Edit", "Selesai Editing", "Proses Cetak" -> "Proses Edit dan Cetak"
        DB::table('transaksi')->whereIn('process_status', ['Proses Edit', 'Selesai Editing', 'Proses Cetak'])->update(['process_status' => 'Proses Edit dan Cetak']);

        // 3. Ubah kembali kolom menjadi ENUM dengan definisi baru
        // Status: 'Pelanggan Belum Foto', 'Pelanggan Pilih Foto', 'Siap Edit dan Cetak', 'Proses Edit dan Cetak', 'Selesai'
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM('Pelanggan Belum Foto', 'Pelanggan Pilih Foto', 'Siap Edit dan Cetak', 'Proses Edit dan Cetak', 'Selesai') DEFAULT 'Pelanggan Belum Foto'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke Varchar dulu untuk mapping balik (opsional, jika rollback diperlukan)
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status VARCHAR(50)");

        // Mapping balik data (Best effort)
        DB::table('transaksi')->where('process_status', 'Pelanggan Belum Foto')->update(['process_status' => 'Belum Foto']);
        DB::table('transaksi')->where('process_status', 'Pelanggan Pilih Foto')->update(['process_status' => 'Pilih Foto']);
        DB::table('transaksi')->where('process_status', 'Siap Edit dan Cetak')->update(['process_status' => 'Siap Edit']);
        DB::table('transaksi')->where('process_status', 'Proses Edit dan Cetak')->update(['process_status' => 'Proses Edit']);

        // Kembalikan definisi ENUM lama
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN process_status ENUM('Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai') DEFAULT 'Belum Foto'");
    }
};