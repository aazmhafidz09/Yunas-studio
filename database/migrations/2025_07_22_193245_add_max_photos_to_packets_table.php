<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packets', function (Blueprint $table) {
            $table->unsignedInteger('max_photos_for_edit')->default(10)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('packets', function (Blueprint $table) {
            $table->dropColumn('max_photos_for_edit');
        });
    }
};
