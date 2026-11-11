<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Review state of the customer's "photos to edit" selection.
     *  null       = draft (customer is still picking, not submitted yet)
     *  submitted  = submitted, waiting for admin review (customer can withdraw)
     *  approved   = approved by admin, customer can no longer change it
     *  rejected   = rejected by admin, selection was reset and customer picks again
     */
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('edit_selection_status', 20)->nullable()->after('select_print_photo');
            $table->text('edit_selection_note')->nullable()->after('edit_selection_status');
            $table->timestamp('edit_selection_submitted_at')->nullable()->after('edit_selection_note');
            $table->timestamp('edit_selection_reviewed_at')->nullable()->after('edit_selection_submitted_at');
            $table->index('edit_selection_status');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex(['edit_selection_status']);
            $table->dropColumn([
                'edit_selection_status',
                'edit_selection_note',
                'edit_selection_submitted_at',
                'edit_selection_reviewed_at',
            ]);
        });
    }
};
