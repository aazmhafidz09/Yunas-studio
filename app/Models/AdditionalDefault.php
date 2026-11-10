<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'packet_id',
        'additional_id',
        'quantity',
        'note',
    ];

    /**
     * Get the packet that owns this default additional.
     */
    public function packet(): BelongsTo
    {
        return $this->belongsTo(Packet::class);
    }

    /**
     * Get the additional item.
     */
    public function additional(): BelongsTo
    {
        return $this->belongsTo(Additional::class);
    }
}
