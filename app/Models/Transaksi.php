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