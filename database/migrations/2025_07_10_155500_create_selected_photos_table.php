<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('selected_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('file_url');
            $table->timestamps();

            $table->index('transaction_id');
            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('transaksi')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('selected_photos', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
        });

        Schema::dropIfExists('selected_photos');
    }
};
