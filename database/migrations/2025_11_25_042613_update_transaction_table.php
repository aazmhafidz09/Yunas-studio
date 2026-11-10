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
            $table->text('url_images')->nullable()->after('note');
            $table->text('select_edit_photo')->nullable()->after('url_images');
            $table->text('select_print_photo')->nullable()->after('select_edit_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['url_images', 'select_edit_photo', 'select_print_photo']);
        });
    }
};
