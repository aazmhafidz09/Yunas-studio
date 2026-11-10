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
        // Create balance table to track current balance
        Schema::create('balance', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add columns to expenses table for partial debt payments
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
        });

        // Create debt_payments table to track partial payments
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Insert initial balance record
        DB::table('balance')->insert([
            'amount' => 0,
            'description' => 'Initial balance',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update existing expenses to set remaining_amount for debts
        DB::statement('
            UPDATE expenses 
            SET remaining_amount = CASE 
                WHEN type = "debt" AND is_paid = 0 THEN amount
                ELSE 0
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'remaining_amount']);
        });
        Schema::dropIfExists('balance');
    }
};