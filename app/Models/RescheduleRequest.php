<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RescheduleRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_id',
        'old_schedule_date',
        'old_schedule_time',
        'new_schedule_date',
        'new_schedule_time',
        'status',
        'admin_note',
        'reason',
        'approved_at',
        'approved_by'
    ];
  public function transaction()
{
    return $this->belongsTo(
        Transaksi::class,
        'transaction_id',
        'transaction_id'
    );
}
    function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
