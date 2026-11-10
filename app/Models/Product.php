<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'image'
    ];
    
    /**
     * Get the packets for the product.
     */
    public function packets(): HasMany
    {
        return $this->hasMany(Packet::class, 'product_id');
    }
}
