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

    /** Ukuran 1 slot jadwal (menit). */
    public const SLOT_MINUTES = 30;

    /** Sisa menit di atas kelipatan slot yang masih dibulatkan ke bawah. */
    public const ROUND_THRESHOLD = 15;

    /**
     * Bulatkan durasi ke kelipatan 30 menit dengan threshold 15 menit:
     * sisa ≤ 15 menit dibulatkan ke bawah, sisa > 15 menit dibulatkan ke
     * atas, dan minimal 1 slot (30 menit).
     *
     *   15 → 30, 40 → 30, 45 → 30, 50 → 60, 60 → 60, 85 → 90
     */
    public static function roundToSlotMinutes(?int $minutes): int
    {
        $minutes = max(0, (int) $minutes);
        $blocks = intdiv($minutes, self::SLOT_MINUTES);
        if ($minutes % self::SLOT_MINUTES > self::ROUND_THRESHOLD) {
            $blocks++;
        }
        return max(1, $blocks) * self::SLOT_MINUTES;
    }

    /** Jumlah slot 30 menit yang ditempati sebuah booking. */
    public static function slotCount(?int $minutes): int
    {
        return intdiv(self::roundToSlotMinutes($minutes), self::SLOT_MINUTES);
    }

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
     * Maximum number of photos the customer may pick for editing (retouch).
     *
     * Taken from the "Retouch N Photo" default additional of this packet (N × quantity).
     * Falls back to max_photos_for_edit when the packet has no retouch additional.
     * 0 means unlimited.
     */
    public function getRetouchLimitAttribute(): int
    {
        $this->loadMissing('additionalDefaults.additional');

        $limit = 0;
        $found = false;

        foreach ($this->additionalDefaults as $default) {
            $name = $default->additional->name ?? '';
            if (preg_match('/retouch\D*(\d+)/i', $name, $m)) {
                $limit += (int) $m[1] * max(1, (int) $default->quantity);
                $found = true;
            }
        }

        return $found ? $limit : (int) ($this->max_photos_for_edit ?? 0);
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