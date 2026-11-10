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

            $table->string('order_id')
                ->unique()
                ->nullable()
                ->after('transaction_id');

            $table->date('session_date')
                ->nullable()
                ->after('order_id');

            $table->string('session_time')
                ->nullable()
                ->after('session_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {

            $table->dropColumn([
                'order_id',
                'session_date',
                'session_time'
            ]);
        });
    }
};