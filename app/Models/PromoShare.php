<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoShare extends Model
{
    use HasFactory;

    protected $table = 'promo_shares';

    protected $fillable = [
        'promo_id',
        'transaction_id',
        'phone_number',
        'shared_by',
        'shared_at',
    ];

    protected $casts = [
        'shared_at' => 'datetime',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'transaction_id');
    }

    public function sharer()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
