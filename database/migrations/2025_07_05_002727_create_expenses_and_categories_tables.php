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
        // Buat tabel expense_categories terlebih dahulu
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_monthly_default')->default(false);
            $table->timestamps();
        });

        // Insert default expense categories
        DB::table('expense_categories')->insert([
            ['name' => 'Listrik', 'is_monthly_default' => true],
            ['name' => 'Wifi', 'is_monthly_default' => true],
            ['name' => 'Pulsa', 'is_monthly_default' => true],
            ['name' => 'Gaji', 'is_monthly_default' => true],
            ['name' => 'Subscribe Adobe', 'is_monthly_default' => true],
            ['name' => 'Marketing', 'is_monthly_default' => true],
            ['name' => 'Subscribe Google Drive', 'is_monthly_default' => true],
            ['name' => 'Print Cetak', 'is_monthly_default' => true],
        ]);

        // Kemudian buat tabel expenses dengan foreign key ke expense_categories
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('number')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('receipt_image')->nullable();
            $table->timestamps();
            
            // Tambahkan foreign key ke expense_categories
            $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus tabel expenses terlebih dahulu karena memiliki foreign key
        Schema::dropIfExists('expenses');
        
        // Kemudian hapus tabel expense_categories
        Schema::dropIfExists('expense_categories');
    }
};