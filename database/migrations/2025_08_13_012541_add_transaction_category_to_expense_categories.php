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
        // Periksa apakah kategori 'Transaction' sudah ada
        $exists = DB::table('expense_categories')
            ->where('name', 'Transaction')
            ->exists();
            
        // Jika belum ada, tambahkan kategori 'Transaction'
        if (!$exists) {
            DB::table('expense_categories')->insert([
                'name' => 'Transaction',
                'type' => 'expense',
                'is_monthly_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus kategori 'Transaction' jika ada
        DB::table('expense_categories')
            ->where('name', 'Transaction')
            ->delete();
    }
};
