<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_monthly_default'
    ];

    protected $casts = [
        'is_monthly_default' => 'boolean',
    ];

    /**
     * Get the badge color based on type
     */
    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'income' => 'success',
            'expense' => 'danger', 
            'debt' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get the type label in Indonesian
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'debt' => 'Hutang',
            default => 'Tidak Diketahui'
        };
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
