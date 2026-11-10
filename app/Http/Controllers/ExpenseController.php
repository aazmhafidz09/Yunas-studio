<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ExpenseCategory;
use App\Models\Balance;
use App\Models\DebtPayment;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResource(Expense::class);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get filter parameters from request
        $month = $request->input('month');
        $year = $request->input('year');
        $category_id = $request->input('category_id');
        $type = $request->input('type');
        $sort_amount = $request->input('sort_amount');
        
        // Get current balance
        $currentBalance = $this->getCurrentBalance();
        
        // Build the main query for expenses
        $query = Expense::query();
        
        // Apply filters
        if ($month) {
            $query->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $query->whereYear('expense_date', $year);
        }
        
        if ($category_id) {
            $query->where('category_id', $category_id);
        }
        
        if ($type) {
            $query->where('expenses.type', $type);
        }
        
        // Apply sorting
        if ($sort_amount) {
            $query->orderBy('amount', $sort_amount);
        } else {
            $query->orderBy('expense_date', 'desc');
        }
        
        // Get paginated results with query string preserved for pagination links
        $expenses = $query->paginate(10)->withQueryString();
        
        // Calculate totals based on the current filter
        $filteredQuery = Expense::query();
        
        // Apply the same filters to the total calculation query
        if ($month) {
            $filteredQuery->whereMonth('expense_date', $month);
        }
        
        if ($year) {
            $filteredQuery->whereYear('expense_date', $year);
        }
        
        if ($category_id) {
            $filteredQuery->where('category_id', $category_id);
        }
        
        if ($type) {
            $filteredQuery->where('expenses.type', $type);
        }
        
        // Calculate total expenses and income
        $totalExpenses = (clone $filteredQuery)->where('expenses.type', 'expense')->sum('amount');
        $totalIncome = (clone $filteredQuery)->where('expenses.type', 'income')->sum('amount');
        
        // Get expenses by category for the filtered data
        $expensesByCategory = (clone $filteredQuery)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category_name, SUM(expenses.amount) as total')
            ->whereNotNull('expenses.category_id')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('total', 'desc')
            ->get();
        
        // Get all months for the dropdown
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create(null, $i, 1)->format('F');
        }
        
        // Get years for the dropdown (from 2020 to 2028)
        $years = range(2020, 2028);
        
        // Get all categories for the dropdown
        $categories = ExpenseCategory::orderBy('name')->get();
        
        return view('admin.expenses.index', compact(
            'expenses', 
            'totalExpenses',
            'totalIncome', 
            'month', 
            'year', 
            'type',
            'months', 
            'years',
            'expensesByCategory',
            'categories',
            'category_id',
            'sort_amount',
            'currentBalance'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('admin.expenses.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'type' => 'required|in:income,expense,debt',
            'description' => 'nullable|string',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ]);
        
        $data = $request->except('receipt_image');
        
        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $data['receipt_image'] = $path;
        }
        
        Expense::create($data);
        
        return redirect()->route('expenses.index')
            ->with('success', 'Data berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('admin.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|exists:expense_categories,id',
            'type' => 'required|in:income,expense,debt',
            'description' => 'nullable|string',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg|max:15360',
        ]);
        
        $data = $request->except('receipt_image');
        
        if ($request->hasFile('receipt_image')) {
            // Delete old image if exists
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            
            $path = $request->file('receipt_image')->store('receipts', 'public');
            $data['receipt_image'] = $path;
        }
        
        $expense->update($data);
        
        return redirect()->route('expenses.index')
            ->with('success', 'Data berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }
        
        $expense->delete();
        
        return redirect()->route('expenses.index')
            ->with('success', 'Data berhasil dihapus');
    }

    /**
     * Toggle payment status for debt expenses.
     */
    public function togglePayment(Expense $expense)
    {
        // Only allow toggle for debt type expenses
        if ($expense->type !== 'debt') {
            return redirect()->route('expenses.index')
                ->with('error', 'Status pembayaran hanya dapat diubah untuk jenis hutang.');
        }
        
        // Get current status before update
        $wasAlreadyPaid = $expense->is_paid;
        
        // Update payment status
        $expense->update([
            'is_paid' => !$expense->is_paid
        ]);
        
        // Update balance based on payment status change
        if ($expense->is_paid && !$wasAlreadyPaid) {
            // If changing from unpaid to paid, add the remaining amount to balance
            $remainingAmount = $expense->remaining_amount;
            if ($remainingAmount > 0) {
                // Update remaining amount to 0 and paid amount to full amount
                $expense->update([
                    'paid_amount' => $expense->amount,
                    'remaining_amount' => 0
                ]);
                
                // Update balance
                Balance::updateBalance($remainingAmount, "Debt fully paid: {$expense->name}");
            }
        } else if (!$expense->is_paid && $wasAlreadyPaid) {
            // If changing from paid to unpaid, subtract the amount from balance
            // Only if there are no partial payments
            if ($expense->debtPayments()->count() == 0) {
                // Reset paid and remaining amounts
                $expense->update([
                    'paid_amount' => 0,
                    'remaining_amount' => $expense->amount
                ]);
                
                // Update balance
                Balance::updateBalance(-$expense->amount, "Debt payment cancelled: {$expense->name}");
            } else {
                // If there are partial payments, calculate the difference
                $paidAmount = $expense->paid_amount;
                $remainingAmount = $expense->amount - $paidAmount;
                
                // Update remaining amount
                $expense->update([
                    'remaining_amount' => $remainingAmount
                ]);
                
                // No need to update balance as partial payments already updated it
            }
        }
        
        $status = $expense->is_paid ? 'lunas' : 'belum lunas';
        
        return redirect()->route('expenses.index')
            ->with('success', "Status pembayaran berhasil diubah menjadi {$status}.");
    }

    // Tambahkan method ini ke dalam class ExpenseController
    /**
     * Generate monthly default expenses.
     */
    public function generateMonthlyExpenses(Request $request)
    {
        // Authorize the action
        $this->authorize('create', Expense::class);
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);
    
        $month = $request->month;
        $year = $request->year;
        
        // Get the first day of the month
        $date = Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        
        // Check if monthly expenses have already been generated for this month and year
        // Hanya cek berdasarkan is_monthly_generated, bukan berdasarkan tipe
        // Hapus pengecekan ini agar bisa generate ulang untuk bulan yang sama
        // $monthlyGeneratedExists = Expense::where('is_monthly_generated', true)
        //     ->whereYear('expense_date', $year)
        //     ->whereMonth('expense_date', $month)
        //     ->exists();
            
        // if ($monthlyGeneratedExists) {
        //     return redirect()->route('expenses.index', ['month' => $month, 'year' => $year])
        //         ->with('info', 'Biaya bulanan sudah pernah di-generate untuk bulan dan tahun ini.');
        // }
        
        // Get all monthly default categories
        $defaultCategories = ExpenseCategory::where('is_monthly_default', true)->get();
        
        // Log semua kategori monthly default yang ditemukan
        \Log::info('Found monthly default categories:', [
            'count' => $defaultCategories->count(),
            'categories' => $defaultCategories->pluck('name', 'id')->toArray()
        ]);
        
        $count = 0;
        foreach ($defaultCategories as $category) {
            // Cek apakah kategori ini sudah memiliki expense untuk bulan ini (baik manual atau monthly generated)
            $existingExpense = Expense::where('category_id', $category->id)
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $month)
                ->first();
                
            // Jika sudah ada expense untuk kategori ini di bulan yang sama, skip kategori ini
            if ($existingExpense) {
                // Log untuk debugging
                \Log::info('Skipping category, expense already exists:', [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'month' => $month,
                    'year' => $year,
                    'existing_expense_id' => $existingExpense->id
                ]);
                continue;
            }
            
            // Gunakan tipe asli dari kategori
            $originalType = $category->type;
            
            // Create monthly expense for each category
            Expense::create([
                'name' => 'Monthly Expense: ' . date('F Y', strtotime($date)),
                'description' => 'Auto-generated monthly expense for ' . $category->name,
                'amount' => 0, // Default amount, to be filled by user
                'expense_date' => $date,
                'category_id' => $category->id,
                'type' => $originalType, // Gunakan tipe asli dari kategori
                'is_monthly_generated' => true, // Mark as monthly generated
            ]);
            $count++;
            
            // Log untuk debugging
            \Log::info('Generated monthly expense for category:', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'month' => $month,
                'year' => $year
            ]);
        }
        
        if ($count > 0) {
            return redirect()->route('expenses.index', ['month' => $month, 'year' => $year])
                ->with('success', "$count biaya bulanan default berhasil dibuat.");
        } else {
            return redirect()->route('expenses.index', ['month' => $month, 'year' => $year])
                ->with('info', 'Tidak ada kategori biaya bulanan default yang ditemukan.');
        }
    }

    /**
     * Make partial payment for debt
     */
    public function makePartialPayment(Request $request, Expense $expense)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $expense->remaining_amount,
            'notes' => 'nullable|string|max:255'
        ]);

        try {
            $expense->makePartialPayment($request->amount, $request->notes);
            
            return redirect()->route('expenses.index')
                ->with('success', 'Pembayaran sebagian berhasil dicatat. Sisa hutang: ' . $expense->formatted_remaining_amount);
        } catch (\Exception $e) {
            return redirect()->route('expenses.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show debt payment history
     */
    public function showDebtPayments(Expense $expense)
    {
        if ($expense->type !== 'debt') {
            return redirect()->route('expenses.index')
                ->with('error', 'Riwayat pembayaran hanya tersedia untuk hutang.');
        }

        $payments = $expense->debtPayments()->orderBy('payment_date', 'desc')->get();
        
        return view('admin.expenses.debt-payments', compact('expense', 'payments'));
    }

    /**
     * Get current balance
     */
    public function getCurrentBalance()
    {
        return Balance::getCurrentBalance();
    }
}
