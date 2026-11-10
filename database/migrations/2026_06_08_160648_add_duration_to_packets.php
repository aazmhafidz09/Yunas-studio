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
        Schema::table('packets', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->default(60)
                  ->after('max_photos_for_edit')
                  ->comment('Durasi sesi pemotretan dalam menit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('packets', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};
