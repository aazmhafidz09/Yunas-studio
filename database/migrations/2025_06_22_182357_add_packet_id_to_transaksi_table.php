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
            // Add the packet_id column after 'customer_name'
            // It's nullable for now to support any existing data, but we'll enforce it in the code
            $table->foreignId('packet_id')->nullable()->after('customer_name')->constrained('packets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['packet_id']);
            $table->dropColumn('packet_id');
        });
    }
};