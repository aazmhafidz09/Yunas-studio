<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selected_prints', function (Blueprint $table) {
            $table->string('print_size')->nullable()->after('file_url');
        });
    }

    public function down(): void
    {
        Schema::table('selected_prints', function (Blueprint $table) {
            $table->dropColumn('print_size');
        });
    }
};
