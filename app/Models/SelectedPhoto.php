<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectedPhoto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'transaction_id',
        'file_url',
        'is_print',
        'additional_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_print' => 'boolean', // Cast to a true/false value
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'transaction_id');
    }

    public function additional()
    {
        return $this->belongsTo(Additional::class);
    }
}
