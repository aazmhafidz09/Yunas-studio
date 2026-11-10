<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tambahkan kolom type ke expense_categories
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->enum('type', ['income', 'expense', 'debt'])->default('expense')->after('name');
        });

        // Tambahkan kolom type dan is_paid ke expenses
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('type', ['income', 'expense', 'debt'])->default('expense')->after('category_id');
            $table->boolean('is_paid')->default(false)->after('type');
        });

        // Update existing categories dengan type default 'expense'
        DB::table('expense_categories')->update(['type' => 'expense']);
        
        // Update existing expenses dengan type default 'expense'
        DB::table('expenses')->update(['type' => 'expense', 'is_paid' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_paid']);
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};