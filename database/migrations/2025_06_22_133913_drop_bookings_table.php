<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop foreign key first if it exists
        Schema::table('bookings', function (Blueprint $table) {
             if (Schema::hasColumn('bookings', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
             }
        });
        Schema::dropIfExists('bookings');
    }

    public function down()
    {
        // Define schema to recreate table if migration is rolled back
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');
            $table->foreignId('transaction_id')->constrained('transaksi', 'transaction_id')->onDelete('cascade');
            $table->dateTime('booking_datetime');
            $table->timestamps();
        });
    }
};