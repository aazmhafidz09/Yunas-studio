<?php

namespace App\Http\Controllers;

use App\Models\Packet;
use App\Models\Promo;
use App\Models\PromoShare;
use App\Models\Transaksi;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    private array $processStatuses = [
        'Pelanggan Belum Foto',
        'Pelanggan Pilih Foto',
        'Proses Edit',
        'Proses Cetak',
        'Selesai',
    ];

    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isKasir()), 403, 'AKSES DITOLAK: Halaman ini hanya untuk admin.');
    }

    /**
     * Display Bagikan Promo page with saved promos and transaction customer list (including previous transactions).
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $promos = Promo::with('creator')
            ->withCount('shares')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // Load transactions from all time by default so previous transactions are included
        $query = Transaksi::with(['packet.product']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($sub) use ($search) {
                $sub->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('receipt_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }
        if ($request->filled('packet_id')) {
            $query->where('packet_id', $request->input('packet_id'));
        }
        if ($request->filled('payment_status')) {
            $query->where('status', $request->input('payment_status'));
        }
        if ($request->filled('process_status')) {
            $query->where('process_status', $request->input('process_status'));
        }

        // Deduplicate by phone number (keep latest transaction per phone) when toggled
        if ($request->boolean('unique_phone')) {
            $latestIds = Transaksi::query()
                ->selectRaw('MAX(transaction_id) as max_id')
                ->whereNotNull('phone_number')
                ->where('phone_number', '!=', '')
                ->groupBy('phone_number');

            $query->whereIn('transaction_id', $latestIds);
        }

        $totalMatchedTransactions = (clone $query)->count();
        $totalWithPhone = (clone $query)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->count();

        $perPageOptions = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $transactions = $query
            ->orderByDesc('created_at')
            ->orderByDesc('transaction_id')
            ->paginate($perPage)
            ->withQueryString();

        $sharedMap = [];
        $pageTransactionIds = $transactions->getCollection()->pluck('transaction_id')->all();
        if (!empty($pageTransactionIds) && $promos->isNotEmpty()) {
            $shares = PromoShare::whereIn('transaction_id', $pageTransactionIds)
                ->whereIn('promo_id', $promos->pluck('id')->all())
                ->orderByDesc('shared_at')
                ->get();

            foreach ($shares as $share) {
                if (!isset($sharedMap[$share->transaction_id][$share->promo_id])) {
                    $sharedMap[$share->transaction_id][$share->promo_id] = $share->shared_at;
                }
            }
        }

        $packetsForFilter = Packet::with('product')
            ->whereHas('product')
            ->orderBy('name')
            ->get()
            ->groupBy('product.name');

        return view('admin.promo.index', [
            'promos' => $promos,
            'transactions' => $transactions,
            'sharedMap' => $sharedMap,
            'totalMatchedTransactions' => $totalMatchedTransactions,
            'totalWithPhone' => $totalWithPhone,
            'packetsForFilter' => $packetsForFilter,
            'paymentStatuses' => ['belum dibayar', 'dp', 'sudah dibayar'],
            'processStatuses' => $this->processStatuses,
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Store a new promo template.
     */
    public function store(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ], [
            'name.required' => 'Nama promo wajib diisi.',
            'content.required' => 'Isi promo wajib diisi.',
        ]);

        $promo = Promo::create([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('promos.index')
            ->with('success', "Promo \"{$promo->name}\" berhasil ditambahkan dan tombol WhatsApp-nya langsung tersedia di tabel pelanggan.");
    }

    /**
     * Update an existing promo template.
     */
    public function update(Request $request, Promo $promo)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ], [
            'name.required' => 'Nama promo wajib diisi.',
            'content.required' => 'Isi promo wajib diisi.',
        ]);

        $promo->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
        ]);

        return redirect()
            ->route('promos.index')
            ->with('success', "Promo \"{$promo->name}\" berhasil diperbarui.");
    }

    /**
     * Delete a saved promo template.
     */
    public function destroy(Promo $promo)
    {
        $this->authorizeStaff();

        $name = $promo->name;
        $promo->delete();

        return redirect()
            ->route('promos.index')
            ->with('success', "Promo \"{$name}\" berhasil dihapus.");
    }

    /**
     * Record when an admin clicks the WhatsApp share button for a promo & transaction.
     */
    public function logShare(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'promo_id' => 'required|exists:promos,id',
            'transaction_id' => 'required|exists:transaksi,transaction_id',
        ]);

        $transaksi = Transaksi::findOrFail($validated['transaction_id']);

        $share = PromoShare::create([
            'promo_id' => $validated['promo_id'],
            'transaction_id' => $transaksi->transaction_id,
            'phone_number' => $transaksi->phone_number,
            'shared_by' => auth()->id(),
            'shared_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'shared_at' => $share->shared_at->format('d M Y H:i'),
        ]);
    }
}
