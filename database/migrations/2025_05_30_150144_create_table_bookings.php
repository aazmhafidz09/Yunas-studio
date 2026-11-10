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
            Schema::create('bookings', function (Blueprint $table) {
                $table->id('booking_id');
                $table->unsignedBigInteger('transaction_id')->unique();
                $table->dateTime('booking_datetime');
                $table->timestamps();

                // Foreign key constraint
                $table->foreign('transaction_id')
                      ->references('transaction_id')
                      ->on('transaksi')
                      ->onDelete('cascade');

                // Add an index for faster lookups on booking_datetime
                $table->index('booking_datetime');
                // Optional: Add a unique constraint if a specific datetime slot can only be booked once across all transactions
                // $table->unique('booking_datetime', 'unique_booking_slot');
                // However, if multiple 'resources' can be booked at the same time, this global unique might not be what you want.
                // For now, we'll handle slot availability in the application logic.
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('bookings');
        }
    };
    