<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Additional extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
    ];

    /**
     * Get the default configurations for this additional item.
     */
    public function defaultConfigurations(): HasMany
    {
        return $this->hasMany(AdditionalDefault::class);
    }

    /**
     * Get all packets that include this additional as default.
     */
    public function packets()
    {
        return $this->belongsToMany(Packet::class, 'additional_defaults');
    }

    public function transaksis()
    {
        return $this->belongsToMany(Transaksi::class, 'additional_transaksi', 'additional_id', 'transaksi_id')
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }
}
