<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('content');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('promo_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_id');
            $table->unsignedBigInteger('transaction_id');
            $table->string('phone_number', 30)->nullable();
            $table->unsignedBigInteger('shared_by')->nullable();
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamps();

            $table->index(['promo_id', 'transaction_id']);
            $table->foreign('promo_id')->references('id')->on('promos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_shares');
        Schema::dropIfExists('promos');
    }
};
