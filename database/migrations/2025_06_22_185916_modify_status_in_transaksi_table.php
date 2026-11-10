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
            // Drop the old boolean column if it exists
            if (Schema::hasColumn('transaksi', 'isActive')) {
                $table->dropColumn('isActive');
            }

            // Add the new status column
            $table->enum('process_status', ['Belum Foto', 'Pilih Foto', 'Siap Edit', 'Proses Edit', 'Selesai Editing', 'Siap Cetak', 'Proses Cetak', 'Selesai'])
                  ->default('Belum Foto')
                  ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('process_status');
            $table->boolean('isActive')->default(1);
        });
    }
};
