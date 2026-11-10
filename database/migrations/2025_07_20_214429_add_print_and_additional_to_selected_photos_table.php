<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('selected_photos', function (Blueprint $table) {
            $table->boolean('is_print')->default(false)->after('file_url');
            $table->foreignId('additional_id')->nullable()->after('is_print')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('selected_photos', function (Blueprint $table) {
            $table->dropForeign(['additional_id']);
            $table->dropColumn(['is_print', 'additional_id']);
        });
    }
};
