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
                if (Schema::hasColumn('transaksi', 'transaction_date')) {
                    $table->dropColumn('transaction_date');
                }
            });
        }

        /**
         * Reverse the migrations.
         * Re-adds the column if you roll back.
         */
        public function down(): void
        {
            Schema::table('transaksi', function (Blueprint $table) {
                if (!Schema::hasColumn('transaksi', 'transaction_date')) {
                    $table->date('transaction_date')->nullable()->after('final_link'); // Or wherever it was
                }
            });
        }
    };
    