<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\Transaksi;
use App\Models\Additional;
use App\Models\Balance;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        return $this->root();
    }

    public function root()
    {
        if(Auth::user()->isUser() || Auth::user()->isKasir()){
            return redirect('transaksi');
        }

        $isKasir = auth()->user()->isKasir();
        $currentDate = Carbon::now();

        // --- 1. Basic Stats Cards ---
        
        // Total Income (Accumulated - All Time)
        $totalIncome = Expense::where('type', 'income')->sum('amount');

        // Total Income (Monthly - Current Month)
        $totalIncomeMonthly = Expense::where('type', 'income')
            ->whereYear('expense_date', $currentDate->year)
            ->whereMonth('expense_date', $currentDate->month)
            ->sum('amount');
        
        // Total Expense (Accumulated)
        $totalExpenses = Expense::where('type', 'expense')->sum('amount');

        // Profit (Income - Expense)
        $profit = $totalIncome - $totalExpenses;

        // Transaction Counts
        $total_transactions = Transaksi::count();
        
        // Completed Transactions (Process Status = Selesai)
        $completed_transactions = Transaksi::where('process_status', 'Selesai')->count();
        
        // Pending Transactions (Status = belum dibayar OR dp)
        // PERBAIKAN: Menggunakan whereIn untuk menghitung 'belum dibayar' dan 'dp'
        $pending_transactions = Transaksi::whereIn('status', ['belum dibayar', 'dp'])->count();

        // --- 2. Charts Data (Pre-calculate all periods) ---
        $dailyData = $this->getChartData('daily', $isKasir);
        $monthlyData = $this->getChartData('monthly', $isKasir);
        $yearlyData = $this->getChartData('yearly', $isKasir);

        // --- 3. Top Selling Packets ---
        $top_selling_packets = Transaksi::select('packet_id', DB::raw('count(*) as total'))
            ->with(['packet.product'])
            ->groupBy('packet_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // --- 4. Recent Transactions ---
        $recent_transactions = Transaksi::with(['packet.product', 'additionals'])
            ->latest()
            ->limit(10)
            ->get();

        return view('index', compact(
            'totalIncome',
            'totalIncomeMonthly',
            'totalExpenses',
            'profit',
            'total_transactions',
            'completed_transactions',
            'pending_transactions',
            'top_selling_packets',
            'recent_transactions',
            'dailyData', 'monthlyData', 'yearlyData'
        ))
        ->with('chart_daily_labels', $dailyData['labels'])
        ->with('chart_daily_data', $dailyData['income'])
        ->with('chart_monthly_labels', $monthlyData['labels'])
        ->with('chart_monthly_data', $monthlyData['income'])
        ->with('chart_yearly_labels', $yearlyData['labels'])
        ->with('chart_yearly_data', $yearlyData['income']);
    }

    /**
     * Helper to get chart data
     */
    private function getChartData($period, $isKasir)
    {
        $labels = [];
        $income = [];
        $currentYear = date('Y');

        if ($period == 'daily') {
            for ($i = 6; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $labels[] = $day->format('d M');
                $incomeAmount = Expense::where('type', 'income')
                    ->whereDate('expense_date', $day)
                    ->sum('amount');
                $income[] = $incomeAmount;
            }
        } elseif ($period == 'yearly') {
            for ($i = 4; $i >= 0; $i--) {
                $year = Carbon::now()->subYears($i)->year;
                $labels[] = (string)$year;
                $incomeAmount = Expense::where('type', 'income')
                    ->whereYear('expense_date', $year)
                    ->sum('amount');
                $income[] = $incomeAmount;
            }
        } else {
            for ($i = 1; $i <= 12; $i++) {
                $month = Carbon::create($currentYear, $i, 1);
                $labels[] = $month->format('M');
                $incomeAmount = Expense::where('type', 'income')
                    ->whereYear('expense_date', $currentYear)
                    ->whereMonth('expense_date', $i)
                    ->sum('amount');
                $income[] = $incomeAmount;
            }
        }

        return [
            'labels' => $labels,
            'income' => $income
        ];
    }

    public function lang($locale)
    {
        if ($locale) {
            App::setLocale($locale);
            Session::put('lang', $locale);
            Session::save();
            return redirect()->back()->with('locale', $locale);
        } else {
            return redirect()->back();
        }
    }

    public function FormSubmit(Request $request)
    {
        return view('form-repeater');
    }
}