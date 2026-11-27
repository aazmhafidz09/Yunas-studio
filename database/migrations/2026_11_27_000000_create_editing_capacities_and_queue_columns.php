<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editing_capacities', function (Blueprint $table) {
            $table->id();
            // 'default' for global daily capacity, or 'YYYY-MM-DD' for date-specific override
            $table->string('date_key', 20)->unique();
            $table->date('date')->nullable()->index();
            $table->unsignedInteger('capacity')->default(20);
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Seed global default daily editing capacity = 20
        DB::table('editing_capacities')->insert([
            'date_key' => 'default',
            'date' => null,
            'capacity' => 20,
            'note' => 'Kapasitas editing default per hari',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('transaksi', function (Blueprint $table) {
            $table->timestamp('editing_queued_at')->nullable()->after('edit_selection_reviewed_at');
            $table->timestamp('editing_completed_at')->nullable()->after('editing_queued_at');
            $table->index('editing_queued_at');
            $table->index('editing_completed_at');
        });

        // Backfill editing_queued_at for existing unfinished transactions already submitted/approved/in editing
        DB::table('transaksi')
            ->whereNull('editing_queued_at')
            ->where(function ($q) {
                $q->whereNull('url_photos_result')
                  ->orWhere('url_photos_result', '');
            })
            ->whereNotIn('process_status', ['Proses Cetak', 'Selesai'])
            ->where(function ($q) {
                $q->whereIn('edit_selection_status', ['submitted', 'approved'])
                  ->orWhere('process_status', 'Proses Edit');
            })
            ->update([
                'editing_queued_at' => DB::raw('COALESCE(edit_selection_submitted_at, edit_selection_reviewed_at, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex(['editing_queued_at']);
            $table->dropIndex(['editing_completed_at']);
            $table->dropColumn(['editing_queued_at', 'editing_completed_at']);
        });

        Schema::dropIfExists('editing_capacities');
    }
};
