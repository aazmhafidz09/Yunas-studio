<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'customer_name',
        'phone_number',
        'product_id',
        'packet_id',
        'session_date',
        'session_time',
        'total_price',
        'order_id',
        'snap_token',
        'payment_status',
        'transaksi_id',
    ];

    protected $casts = [
        'session_date' => 'date',
        'total_price'  => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    /**
     * Generate unique order ID untuk Midtrans.
     */
    public static function generateOrderId(): string
    {
        return 'BOOKING-' . time() . '-' . strtoupper(substr(uniqid(), -5));
    }
}