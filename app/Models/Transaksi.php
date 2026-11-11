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