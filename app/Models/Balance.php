<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    protected $table = 'balance';

    protected $fillable = [
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    /**
     * Get current balance
     */
    public static function getCurrentBalance()
    {
        $balance = self::first();
        return $balance ? $balance->amount : 0;
    }

    /**
     * Update balance
     */
    public static function updateBalance($amount, $description = null)
    {
        $balance = self::first();
        if (!$balance) {
            $balance = self::create([
                'amount' => $amount,
                'description' => $description ?? 'Balance update'
            ]);
        } else {
            $balance->update([
                'amount' => $balance->amount + $amount,
                'description' => $description ?? 'Balance update'
            ]);
        }
        return $balance;
    }

    /**
     * Set balance to specific amount
     */
    public static function setBalance($amount, $description = null)
    {
        $balance = self::first();
        if (!$balance) {
            $balance = self::create([
                'amount' => $amount,
                'description' => $description ?? 'Balance set'
            ]);
        } else {
            $balance->update([
                'amount' => $amount,
                'description' => $description ?? 'Balance set'
            ]);
        }
        return $balance;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}