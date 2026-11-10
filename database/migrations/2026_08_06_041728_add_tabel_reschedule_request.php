<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reschedule_requests', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('transaction_id')->unique();
            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('transaksi')
                ->cascadeOnDelete();

            $table->date('old_schedule_date');
            $table->time('old_schedule_time');
            $table->date('new_schedule_date');
            $table->time('new_schedule_time');
            $table->text('reason')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reschedule_requests');
    }
};
