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
        // Menggunakan DB::statement untuk mengubah tipe enum
        DB::statement("ALTER TABLE expenses MODIFY COLUMN type ENUM('income', 'expense', 'debt', 'monthly') NOT NULL DEFAULT 'expense'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke definisi awal
        DB::statement("ALTER TABLE expenses MODIFY COLUMN type ENUM('income', 'expense', 'debt') NOT NULL DEFAULT 'expense'");
    }
};
