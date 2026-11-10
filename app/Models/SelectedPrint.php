<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectedPrint extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'file_url',
        'print_size',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaksi::class, 'transaction_id', 'transaction_id');
    }
}