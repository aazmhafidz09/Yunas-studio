<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditingCapacity extends Model
{
    use HasFactory;

    public const DEFAULT_KEY = 'default';
    public const FALLBACK_DAILY_CAPACITY = 20;

    protected $table = 'editing_capacities';

    protected $fillable = [
        'date_key',
        'date',
        'capacity',
        'note',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'capacity' => 'integer',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isDefault(): bool
    {
        return $this->date_key === self::DEFAULT_KEY;
    }
}
