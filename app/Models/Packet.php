<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Packet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'price',
        'max_photos_for_edit',
        'product_id',
        'image',
        'is_active',
        'duration_minutes'
    ];

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
    
    /**
     * Get the product that owns the packet.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the default additionals for this packet.
     */
    public function additionalDefaults(): HasMany
    {
        return $this->hasMany(AdditionalDefault::class);
    }
    
    /**
     * Get the print options included with this packet.
     */
    public function printOptions()
    {
        return $this->belongsToMany(PrintSize::class, 'packet_print_options')->withPivot('quantity');
    }

    /**
     * Get all the additional items included with this packet (your original method).
     */
    public function additionals()
    {
        return $this->hasManyThrough(
            Additional::class,
            AdditionalDefault::class,
            'packet_id', // Foreign key on AdditionalDefault table
            'id', // Foreign key on Additional table
            'id', // Local key on Packet table
            'additional_id' // Local key on AdditionalDefault table
        );
    }

    /**
     * NEW ACCESSOR: Combines regular defaults and print options into a single collection for display.
     */
    public function getCombinedDefaultsAttribute()
    {
        // Eager load the relationships to prevent N+1 issues
        $this->loadMissing('additionalDefaults.additional', 'printOptions');

        $defaults = $this->additionalDefaults->map(function ($default) {
            return (object)[
                'name' => $default->additional->name,
                'quantity' => $default->quantity,
                'note' => $default->note
            ];
        });

        $prints = $this->printOptions->map(function ($print) {
            return (object)[
                'name' => 'Cetak ' . $print->name,
                'quantity' => $print->pivot->quantity,
                'note' => null
            ];
        });

        return $defaults->merge($prints);
    }
}