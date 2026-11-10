<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['temporary_link', 'selected_photos', 'final_link']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('temporary_link', 50)->nullable();
            $table->string('selected_photos', 255)->nullable();
            $table->string('final_link', 50)->nullable();
        });
    }
};
