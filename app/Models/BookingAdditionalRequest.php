<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingAdditionalRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'booking_additional_requests';

    protected $fillable = [
        'transaction_id',
        'additional_id',
        'quantity',
        'price',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
        'reviewed_at' => 'datetime',
    ];

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'transaction_id', 'transaction_id');
    }

    public function additional()
    {
        return $this->belongsTo(Additional::class, 'additional_id')->withTrashed();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->price * (int) $this->quantity;
    }
}
