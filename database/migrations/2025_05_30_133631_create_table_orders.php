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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id'); // Creates an auto-incrementing BIGINT UNSIGNED primary key named 'order_id'

            $table->unsignedBigInteger('product_id'); // For foreign key to products table
            $table->unsignedBigInteger('transaction_id'); // For foreign key to transaksi table

            // Optional: You might want to add quantity or price at the time of order
            // $table->integer('quantity')->default(1);
            // $table->decimal('price_at_order', 10, 2); // If product price can change, store it here

            $table->timestamps(); // Adds created_at and updated_at columns

            // Foreign key constraints
            // Assumes 'id' is the PK in 'products' and 'transaction_id' is the PK in 'transaksi'
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade'); // If a product is deleted, its related order entries are also deleted.
                                       // Consider 'restrict' or 'set null' based on your business logic.

            $table->foreign('transaction_id')
                  ->references('transaction_id')
                  ->on('transaksi')
                  ->onDelete('cascade'); // If a transaction is deleted, its related order entries are also deleted.
                                       // Consider 'restrict' based on your business logic.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};