<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name',
        'description',
        'keterangan',
        'amount',
        'paid_amount',
        'remaining_amount',
        'expense_date',
        'category_id', // Ubah dari 'category' menjadi 'category_id'
        'type',
        'is_paid',
        'receipt_image',
        'is_monthly_generated',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'is_paid' => 'boolean',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * Get the row color based on type
     */
    public function getRowColorAttribute()
    {
        // Jika expense adalah monthly generated, gunakan warna info
        if ($this->is_monthly_generated) {
            return 'table-info';
        }
        
        // Jika bukan monthly generated, gunakan warna berdasarkan tipe
        return match($this->type) {
            'income' => 'table-success',
            'expense' => 'table-danger',
            'debt' => 'table-warning',
            default => ''
        };
    }

    /**
     * Get the type label in Indonesian
     */
    public function getTypeLabelAttribute()
    {
        $baseLabel = match($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran', 
            'debt' => 'Hutang',
            default => 'Tidak Diketahui'
        };
        
        // Jika expense adalah monthly generated, tambahkan label Bulanan
        if ($this->is_monthly_generated) {
            return $baseLabel . ' (Bulanan)';
        }
        
        return $baseLabel;
    }

    /**
     * Get the payment status label
     */
    public function getPaymentStatusLabelAttribute()
    {
        if ($this->type !== 'debt') {
            return '-';
        }
        return $this->is_paid ? 'Sudah Dibayar' : 'Belum Dibayar';
    }

    /**
     * Get the payment status color
     */
    public function getPaymentStatusColorAttribute()
    {
        if ($this->type !== 'debt') {
            return 'secondary';
        }
        return $this->is_paid ? 'success' : 'danger';
    }
    
    // Boot method moved and combined with the one below

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getMonthYearAttribute()
    {
        return Carbon::parse($this->expense_date)->format('F Y');
    }

    public static function getMonthlyExpenses($year = null)
    {
        $year = $year ?? date('Y');
        
        $expenses = self::whereYear('expense_date', $year)
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        $result = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $month = Carbon::create($year, $i, 1)->format('F');
            $expense = $expenses->firstWhere('month', $i);
            $result[$month] = $expense ? $expense->total : 0;
        }
        
        return $result;
    }
    
    // Tambahkan relasi ke kategori
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get debt payments for this expense
     */
    public function debtPayments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Update balance when expense is created/updated/deleted
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($expense) {
            $lastExpense = self::orderBy('number', 'desc')->first();
            $expense->number = $lastExpense ? $lastExpense->number + 1 : 1;
            
            // Set remaining amount for debts
            if ($expense->type === 'debt') {
                $expense->remaining_amount = $expense->amount;
            }
        });

        static::created(function ($expense) {
            $expense->updateBalance('created');
        });

        static::updated(function ($expense) {
            $expense->updateBalance('updated');
        });

        static::deleted(function ($expense) {
            $expense->updateBalance('deleted');
        });
    }

    /**
     * Update balance based on expense type and action
     */
    public function updateBalance($action)
    {
        $amount = 0;
        $description = '';

        switch ($action) {
            case 'created':
                switch ($this->type) {
                    case 'income':
                        $amount = $this->amount;
                        $description = "Income added: {$this->name}";
                        break;
                    case 'expense':
                        $amount = -$this->amount;
                        $description = "Expense added: {$this->name}";
                        break;
                    case 'debt':
                        $amount = -$this->amount;
                        $description = "Debt created: {$this->name}";
                        break;
                }
                break;

            case 'updated':
                // For updates, we need to calculate the difference
                $original = $this->getOriginal();
                if ($original) {
                    $oldAmount = $original['amount'] ?? 0;
                    $oldType = $original['type'] ?? $this->type;
                    
                    // Reverse old effect
                    switch ($oldType) {
                        case 'income':
                            $amount -= $oldAmount;
                            break;
                        case 'expense':
                        case 'debt':
                            $amount += $oldAmount;
                            break;
                    }
                    
                    // Apply new effect
                    switch ($this->type) {
                        case 'income':
                            $amount += $this->amount;
                            break;
                        case 'expense':
                        case 'debt':
                            $amount -= $this->amount;
                            break;
                    }
                    
                    $description = "Expense updated: {$this->name}";
                }
                break;

            case 'deleted':
                // Reverse the effect of the expense
                switch ($this->type) {
                    case 'income':
                        $amount = -$this->amount;
                        $description = "Income deleted: {$this->name}";
                        break;
                    case 'expense':
                        $amount = $this->amount;
                        $description = "Expense deleted: {$this->name}";
                        break;
                    case 'debt':
                        $amount = $this->amount;
                        $description = "Debt deleted: {$this->name}";
                        break;
                }
                break;
        }

        if ($amount != 0) {
            Balance::updateBalance($amount, $description);
        }
    }

    /**
     * Make partial payment for debt
     */
    public function makePartialPayment($amount, $notes = null)
    {
        if ($this->type !== 'debt') {
            throw new \Exception('Partial payments can only be made for debt expenses.');
        }

        if ($amount > $this->remaining_amount) {
            throw new \Exception('Payment amount cannot exceed remaining debt amount.');
        }

        // Create debt payment record
        $this->debtPayments()->create([
            'amount' => $amount,
            'payment_date' => now()->toDateString(),
            'notes' => $notes
        ]);

        // Update paid and remaining amounts
        $this->paid_amount += $amount;
        $this->remaining_amount -= $amount;
        
        // Update is_paid status if fully paid
        if ($this->remaining_amount <= 0) {
            $this->is_paid = true;
            $this->remaining_amount = 0;
        }
        
        $this->save();

        // Update balance (add money back)
        Balance::updateBalance($amount, "Partial debt payment: {$this->name}");

        return $this;
    }

    /**
     * Get payment progress percentage
     */
    public function getPaymentProgressAttribute()
    {
        if ($this->type !== 'debt' || $this->amount <= 0) {
            return 0;
        }
        
        return ($this->paid_amount / $this->amount) * 100;
    }

    /**
     * Get formatted remaining amount
     */
    public function getFormattedRemainingAmountAttribute()
    {
        return 'Rp ' . number_format($this->remaining_amount, 0, ',', '.');
    }

    /**
     * Get formatted paid amount
     */
    public function getFormattedPaidAmountAttribute()
    {
        return 'Rp ' . number_format($this->paid_amount, 0, ',', '.');
    }
}
