<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Additional;
use App\Models\Packet;
use App\Models\Transaksi;
use App\Models\Gateway;
use App\Models\User;
use App\Models\Role;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Balance; 
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class dataGatewayController extends Controller
{

private $processStatuses = [
        'Pelanggan Belum Foto',
        'Pelanggan Pilih Foto',
        'Proses Edit',
        'Proses Cetak',
        'Selesai'
    ];
    
   public function index(Request $request)
    {
        // Eager load relationships termasuk detail paket dan additional
        $query = Gateway::with([
            'packet.product', 
            'packet.printOptions', 
            'packet.additionalDefaults.additional', 
            'user', 
            'additionals'
        ]);
        
        $user = auth()->user();
        
        // Filter user biasa
        if ($user->isUser()) {
            $query->where('phone_number', $user->username);
        }
        
        // Filter Search
        $search = $request->input('search');
        $query->when($search, function ($q) use ($search) {
            $q->where(function($sub) use ($search) {
                $sub->where('receipt_code', 'like', "%$search%")
                    ->orWhere('customer_name', 'like', "%$search%");
            });
        })
        ->when($request->filled('payment_status'), function ($q) use ($request) {
            $q->where('status', $request->payment_status);
        })
        // ->when(true, function ($q) { // Gunakan 'true' jika ingin selalu difilter, atau buat kondisi dari request
        //     // Sesuaikan 'payment_type' dengan nama kolom di database Anda
        //     // Dan sesuaikan 'xendit' atau 'xendit_transfer' dengan isi data di database
        //     $q->where('payment_type', 'Xendit');
        // })
        ->when($request->filled('process_status'), function ($q) use ($request) {
            $q->where('process_status', $request->process_status);
        })
        ->when($request->filled('packet_id'), function ($q) use ($request) {
            $q->where('packet_id', $request->packet_id);
        });
        

        // 2. Clone queries for separate Logic (List vs Cards)
        $listQuery = clone $query;
        // NOTE: $cardQuery will be redefined for Kasir to ensure "Do Not Sync" logic
        $cardQuery = clone $query; 

        // === SERVER SIDE FILTER FOR 'SEMBUNYIKAN SELESAI' ===
        // This fixes the pagination bug by filtering DB results before fetching
        if ($request->input('hide_completed') == '1') {
            $listQuery->where('process_status', '!=', 'Selesai');
        }
        // ====================================================

        $filterLabel = ""; 

        // 3. Role-Based Date Logic
        if ($user->isAdmin()) {
            // --- ADMIN LOGIC (Syncs Card & List) ---
            
            // Check if user manually clicked filter button (flag sent from view)
            $isManualFilter = $request->has('date_filter_applied');

            // Default to Current Month ONLY IF no manual filter applied
            if (!$isManualFilter && !$request->filled('start_date') && !$request->filled('end_date')) {
                $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');

                // Apply to Cards & List
                $cardQuery->whereDate('created_at', '>=', $startOfMonth)
                          ->whereDate('created_at', '<=', $endOfMonth);
                $listQuery->whereDate('created_at', '>=', $startOfMonth)
                          ->whereDate('created_at', '<=', $endOfMonth);

                // Inject defaults into Request so the View's DatePicker is filled
                $request->merge(['start_date' => $startOfMonth, 'end_date' => $endOfMonth]);
                $filterLabel = "Bulan Ini";
            } else {
                // If Admin manually filters
                if ($request->filled('start_date')) {
                    $listQuery->whereDate('created_at', '>=', $request->start_date);
                    $cardQuery->whereDate('created_at', '>=', $request->start_date);
                }
                if ($request->filled('end_date')) {
                    $listQuery->whereDate('created_at', '<=', $request->end_date);
                    $cardQuery->whereDate('created_at', '<=', $request->end_date);
                }

                // Determine Label dynamically
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = Carbon::parse($request->start_date);
                    $end = Carbon::parse($request->end_date);
                    
                    if ($start->isToday() && $end->isToday()) {
                        $filterLabel = "Hari Ini";
                    } elseif ($start->isSameDay($start->copy()->startOfMonth()) && $end->isSameDay($end->copy()->endOfMonth()) && $start->isSameMonth($end)) {
                        $filterLabel = "Bulan " . $start->format('F');
                    } elseif ($start->isSameDay($start->copy()->startOfYear()) && $end->isSameDay($end->copy()->endOfYear()) && $start->isSameYear($end)) {
                        $filterLabel = "Tahun " . $start->format('Y');
                    } else {
                        $filterLabel = "Terfilter";
                    }
                } elseif (!$request->filled('start_date') && !$request->filled('end_date')) {
                    $filterLabel = "Semua Waktu";
                } else {
                    $filterLabel = "Terfilter";
                }
            }
        } 
        elseif ($user->isKasir()) {
            // --- KASIR LOGIC (Cards Locked to Today, List Flexible) ---
            
            // 1. CARDS: Strictly Today's Stats & IGNORE all filters (Search/Packet/Status)
            // We create a fresh query instance for cards
            $cardQuery = Transaksi::query(); 
            $cardQuery->whereDate('created_at', Carbon::today());
            
            $filterLabel = "Hari Ini"; // Fixed label for cards

            // 2. LIST: Standard filtering behavior (Search/Status/Packet inherited from $listQuery clone)
            // Apply Manual Date filter if present
            if ($request->filled('start_date')) $listQuery->whereDate('created_at', '>=', $request->start_date);
            if ($request->filled('end_date'))   $listQuery->whereDate('created_at', '<=', $request->end_date);
        }
        else {
            // USER LOGIC
             if ($request->filled('start_date')) {
                $listQuery->whereDate('created_at', '>=', $request->start_date);
                $cardQuery->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $listQuery->whereDate('created_at', '<=', $request->end_date);
                $cardQuery->whereDate('created_at', '<=', $request->end_date);
            }
        }

        // 4. Calculate Card Stats (Using the specialized $cardQuery)
        // Note: Card stats show financial overview, so usually we don't hide 'Selesai' from these cards
        // unless explicitly requested to match the list exactly. For now, kept independent.
        $unpaidFull = (clone $cardQuery)->where('status', 'belum dibayar')->sum('total_price');
        $dpRemaining = (clone $cardQuery)->where('status', 'dp')->sum(DB::raw('total_price - COALESCE(dp_amount, 0)'));
        
        $totalBelumDibayar = $unpaidFull + $dpRemaining;
        $countBelumDibayar = (clone $cardQuery)->where('status', 'belum dibayar')->count();
        
        $totalDpPaid = (clone $cardQuery)->where('status', 'dp')->sum('dp_amount');
        $countDp = (clone $cardQuery)->where('status', 'dp')->count();
        
        $totalSudahDibayar = (clone $cardQuery)->where('status', 'sudah dibayar')->sum('total_price');
        $countSudahDibayar = (clone $cardQuery)->where('status', 'sudah dibayar')->count();
        
        $totalProfit = $totalDpPaid + $totalSudahDibayar;
        $totalOverallProfit = Transaksi::sum('total_price');

        // 5. Get List Data
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortBy, ['total_price', 'created_at'])) {
            $listQuery->orderBy($sortBy, $sortDirection);
        } else {
            $listQuery->orderBy('created_at', 'desc');
        }

        $perPageOptions = [5, 10, 20, 50, 100];
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, $perPageOptions)) $perPage = 10;
        
        $transactions = $listQuery->paginate($perPage)->withQueryString();
        
        if ($user->isUser()) {
             $userPacketIds = (clone $query)->distinct()->pluck('packet_id');
             $packetsForFilter = Packet::with('product')->whereIn('id', $userPacketIds)->orderBy('name')->get()->groupBy('product.name');
             return view('user.transaction.transaksi', compact('transactions', 'packetsForFilter', 'paymentStatuses', 'processStatuses', 'perPageOptions'));
        }

        $packetsForFilter = Packet::with('product')->whereHas('product')->orderBy('name')->get()->groupBy('product.name');
        $processStatuses = $this->processStatuses;
        $paymentStatuses = ['belum dibayar', 'dp', 'sudah dibayar'];

        return view('admin.transaction_gateway.transaksiXendit', compact(
            'transactions', 'perPageOptions', 
            'totalBelumDibayar', 'countBelumDibayar',
            'totalDpPaid', 'countDp', 
            'totalSudahDibayar', 'countSudahDibayar',
            'totalProfit', 'totalOverallProfit', 
            'packetsForFilter', 'paymentStatuses', 'processStatuses',
            'filterLabel'
        ));
    }
}
