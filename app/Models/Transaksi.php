<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'user_id',
        'customer_name',
        'phone_number',
        'packet_id',
        'status',
        'process_status',
        'payment_type',
        'receipt_code',
        'total_price',
        'dp_amount',
        'discount',
        'note',
        'url_images',
        'url_photos_result',
        'select_edit_photo',
        'select_print_photo',
        'order_id',
        'session_date',
        'session_time',
    ];

    protected $casts = [
        'edit_selection_submitted_at' => 'datetime',
        'edit_selection_reviewed_at' => 'datetime',
    ];

    // Review state of the customer's "photos to edit" selection (column edit_selection_status)
    public const EDIT_SELECTION_DRAFT = null;
    public const EDIT_SELECTION_SUBMITTED = 'submitted';
    public const EDIT_SELECTION_APPROVED = 'approved';
    public const EDIT_SELECTION_REJECTED = 'rejected';

    /**
     * Process statuses during which the customer may still pick photos for editing.
     */
    public const EDIT_SELECTABLE_PROCESS_STATUSES = ['Pelanggan Belum Foto', 'Pelanggan Pilih Foto'];

    /**
     * Whether the customer may currently add/remove photos in the edit selection.
     */
    public function canCustomerEditSelection(): bool
    {
        return in_array($this->process_status, self::EDIT_SELECTABLE_PROCESS_STATUSES, true)
            && !in_array($this->edit_selection_status, [self::EDIT_SELECTION_SUBMITTED, self::EDIT_SELECTION_APPROVED], true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function packet()
    {
        return $this->belongsTo(Packet::class);
    }

    public function additionals()
    {
        return $this->belongsToMany(Additional::class, 'additional_transaksi', 'transaksi_id', 'additional_id')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    /**
     * Total maximum number of photos the customer may select for editing (retouch).
     *
     * Combines:
     * 1. The packet's included retouch quota (packet->retouch_limit, parsed from packet's "Retouch N Photo" default additionals)
     * 2. Plus any extra photo editing additionals attached to this transaction (e.g. "Add Edit Photo/ 1 photo",
     *    "Retouch", etc.) multiplied by their quantity.
     *
     * @return int Total quota (0 = unlimited).
     */
    public function getRetouchLimitAttribute(): int
    {
        $this->loadMissing(['packet.additionalDefaults.additional', 'additionals']);

        $base = $this->packet?->retouch_limit ?? 0;
        $extra = $this->extra_edit_photos_count;

        return $base + $extra;
    }

    /**
     * Extra edit photo quota contributed by transaction additionals.
     *
     * Detects additionals whose name indicates photo retouching/editing (e.g. "Add Edit Photo/ 1 photo",
     * "Retouch 5 Photo", "Tambah Edit Foto"), extracts the number of photos per item, and multiplies
     * by the purchased quantity.
     */
    public function getExtraEditPhotosCountAttribute(): int
    {
        $this->loadMissing('additionals');

        $extra = 0;
        foreach ($this->additionals as $additional) {
            $name = $additional->name ?? '';

            // Match keywords indicating edit/retouch (ignoring purely print or other items)
            if (preg_match('/(edit\s*(?:photo|foto)|retouch)/i', $name)) {
                $perUnit = 1;
                if (preg_match('/(\d+)\s*(?:photo|foto|lembar|pcs)/i', $name, $matches)) {
                    $perUnit = (int) $matches[1];
                } elseif (preg_match('/retouch\D*(\d+)/i', $name, $matches)) {
                    $perUnit = (int) $matches[1];
                } elseif (preg_match('/(?:tambah|add)\D*(\d+)/i', $name, $matches)) {
                    $perUnit = (int) $matches[1];
                }

                $qty = max(1, (int) ($additional->pivot->quantity ?? 1));
                $extra += max(1, $perUnit) * $qty;
            }
        }

        return $extra;
    }

    public function selectedPhotos()
    {
        return $this->hasMany(SelectedPhoto::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Get all of the selected prints for the transaction.
     */
    public function selectedPrints()
    {
        return $this->hasMany(SelectedPrint::class, 'transaction_id', 'transaction_id');
    }

    /**
     * Check if the transaction has any items that require printing.
     *
     * @return bool
     */
    public function hasPrintableItems(): bool
    {
        if ($this->packet && $this->packet->printOptions()->exists()) {
            return true;
        }

        $hasPrintAdditional = $this->additionals()->where('name', 'LIKE', '%Cetak%')->exists();
        if ($hasPrintAdditional) {
            return true;
        }

        return false;
    }
}