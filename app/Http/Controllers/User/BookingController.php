<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Packet;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Additional;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;


class BookingController extends Controller
{

    private function generateAllSlots(): array
    {
        $slots = [];
        for ($h = 10; $h <= 18; $h++) {
            foreach ([0, 30] as $m) {
                if ($h === 18 && $m === 30)
                    break;
                $slots[] = sprintf('%02d:%02d', $h, $m);
            }
        }
        return $slots; // 17 slot: 10:00 s.d. 18:00
    }

    private function getOccupiedSlots(string $startTime, int $durationMinutes): array
    {
        $slots = [];
        // $start = Carbon::createFromFormat('H:i', $startTime);
        $start = Carbon::parse($startTime);
        $slotCount = (int) ceil($durationMinutes / 30); // misal 60 menit → 2 slot

        for ($i = 0; $i < $slotCount; $i++) {
            $slots[] = $start->copy()->addMinutes($i * 30)->format('H:i');
        }

        return $slots;
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER — Ambil semua booking aktif di 1 tanggal
    // Return: [ '10:00' => 2, '10:30' => 1, ... ]  (hitung per slot)
    //
    // Setiap transaksi aktif memblokir SEMUA slot dalam range durasinya.
    // Misal booking 10:00 paket 90 menit → memblokir 10:00, 10:30, 11:00.
    // ═══════════════════════════════════════════════════════════════════
    private function getBookedCountPerSlot(string $date): array
    {
        $booked = [];

        // Ambil semua transaksi aktif di tanggal ini beserta durasi paketnya
        $transaksis = Gateway::whereDate('session_date', $date)
            ->whereNotIn('status', ['gagal'])
            ->with('packet:id,duration_minutes')
            ->get(['session_time', 'packet_id']);

        foreach ($transaksis as $t) {
            $duration = $t->packet->duration_minutes ?? 60; // fallback 60 menit
            $occupied = $this->getOccupiedSlots($t->session_time, $duration);

            foreach ($occupied as $slot) {
                $booked[$slot] = ($booked[$slot] ?? 0) + 1;
            }
        }

        return $booked;
    }

    // CREATE SNAP TOKEN
    public function createSnapToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:50',
            'phone_number' => 'required|string|max:20',
            'product_id' => 'required|exists:products,id',
            'packet_id' => 'required|exists:packets,id',
            'session_date' => 'required|date|after_or_equal:today',
            'session_time' => 'required|string',
            'payment_option' => 'required|in:full,dp,onLocation',
            'dp_amount' => 'required_if:payment_option,dp|nullable|numeric|min:50000',
            'additionals' => 'nullable|array',
            'additionals.*.quantity' => 'required_with:additionals|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $packet = Packet::with('product')->findOrFail($validated['packet_id']);

        if ($packet->product_id != $validated['product_id']) {
            return response()->json(['message' => 'Paket tidak sesuai dengan produk.'], 422);
        }

        // ── Ambil & validasi additional dari DB (bukan dari input) ─────
        $additionalsInput = $validated['additionals'] ?? [];
        $additionalIds = array_keys($additionalsInput);
        $additionalModels = Additional::whereIn('id', $additionalIds)->get()->keyBy('id');

        if (count($additionalIds) !== $additionalModels->count()) {
            return response()->json(['message' => 'Salah satu item tambahan tidak ditemukan.'], 422);
        }

        $additionalsTotal = 0;
        foreach ($additionalsInput as $id => $details) {
            $additionalsTotal += $additionalModels[$id]->price * $details['quantity'];
        }

        // ── Validasi skema pembayaran ──────────────────────────────────
        $paymentOption = $validated['payment_option'];
        $dpAmountInput = $paymentOption === 'dp' ? (float) $validated['dp_amount'] : null;
        $grandTotal = (float) $packet->price + $additionalsTotal;

        if ($paymentOption === 'dp' && $dpAmountInput > $grandTotal) {
            return response()->json([
                'message' => 'Jumlah DP tidak boleh melebihi total harga (paket + tambahan).',
                'errors' => ['dp_amount' => ['DP melebihi total harga.']],
            ], 422);
        }

        $grossAmount = $paymentOption === 'dp' ? (int) $dpAmountInput : (int) $grandTotal;
        // ─────────────────────────────────────────────────────────────

        // ── Cek konflik slot berdasarkan RANGE DURASI ─────────────────
        $slotsNeeded = $this->getOccupiedSlots($validated['session_time'], $packet->duration_minutes);
        $bookedCounts = $this->getBookedCountPerSlot($validated['session_date']);

        $conflictSlots = [];
        foreach ($slotsNeeded as $slot) {
            if (($bookedCounts[$slot] ?? 0) >= 2) {
                $conflictSlots[] = $slot;
            }
        }

        $allSlots = $this->generateAllSlots();
        foreach ($slotsNeeded as $slot) {
            if (!in_array($slot, $allSlots)) {
                return response()->json([
                    'message' => 'Paket dengan durasi ' . $packet->duration_minutes . ' menit tidak muat jika dimulai pukul '
                        . $validated['session_time'] . '. Silakan pilih waktu lebih awal.',
                    'errors' => ['session_time' => ['Slot melewati jam operasional (maks 18:00).']],
                ], 422);
            }
        }

        if (!empty($conflictSlots)) {
            return response()->json([
                'message' => 'Slot ' . implode(', ', $conflictSlots) . ' pada tanggal '
                    . $validated['session_date'] . ' sudah penuh. Silakan pilih waktu lain.',
                'errors' => ['session_time' => ['Slot sudah penuh.']],
            ], 422);
        }
        // ─────────────────────────────────────────────────────────────
        $paymentOption = $validated['payment_option'];
        $isOnLocation  = $paymentOption === 'onLocation'; 
        DB::beginTransaction();
        try {
            $user = User::firstOrCreate(
                ['username' => $validated['phone_number']],
                [
                    'name' => $validated['customer_name'],
                    'password' => Hash::make($validated['phone_number']),
                    'role_id' => Role::where('name', 'User')->first()->id ?? 3,
                ]
            );

            $transaksi = Gateway::create([
                'user_id' => $user->id,
                'customer_name' => $validated['customer_name'],
                'phone_number' => $validated['phone_number'],
                'packet_id' => $validated['packet_id'],
                'session_date' => $validated['session_date'],
                'session_time' => $validated['session_time'],
                'total_price' => $grandTotal,
                'discount' => 0,
                'status' => 'belum dibayar',
                'payment_type' => $isOnLocation ? 'Cash' : 'none',
                'dp_amount' => null,
                'process_status' => 'Pelanggan Belum Foto',
                'note' => 'Booking online — ' . $validated['session_date'] . ' ' . $validated['session_time']
                    . ($paymentOption === 'dp'
                       ? ' (Rencana DP: Rp ' . number_format($dpAmountInput, 0, ',', '.') . ')'
                        : ($isOnLocation ? ' (Bayar di Lokasi)' : ' (Rencana Bayar Penuh)')),
                'receipt_code' => 'TEMP-' . uniqid(),

            ]);

            $receiptCode = 'INV-' . Carbon::now()->format('Ymd') . '-' . $transaksi->transaction_id;
            $transaksi->receipt_code = $receiptCode;
            $transaksi->order_id = $receiptCode;
            $transaksi->save();

            // ── Sync additional ke pivot, harga diambil dari DB ─────────
            if (!empty($additionalsInput)) {
                $syncData = [];
                foreach ($additionalsInput as $id => $details) {
                    $syncData[$id] = [
                        'quantity' => $details['quantity'],
                        'price' => $additionalModels[$id]->price,
                    ];
                }
                $transaksi->additionals()->sync($syncData);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal memproses: ' . $e->getMessage()
            ], 500);
        }

        // Bayar di lokasi: booking sudah tersimpan, tidak perlu Xendit
        if ($paymentOption === 'onLocation') {
    return response()->json([
        'success'      => true,
        'message'      => 'Booking berhasil dibuat. Pembayaran dilakukan di lokasi.',
        'redirect_url' => route('payment.success', ['order_id' => $receiptCode]),
    ]);
}
        // ── Bagian Xendit dipisah DI LUAR DB transaction ──────────────
        // (transaksi sudah ter-commit di atas; kalau Xendit gagal di sini,
        // transaksi tetap tersimpan dengan status 'belum dibayar' — user
        // bisa retry pembayaran nanti, tidak perlu rollback data booking)
        try {
            Configuration::setXenditKey(config('xendit.secret_key'));

            $invoiceApi = new InvoiceApi();
            $itemName = $packet->product->name . ' - ' . $packet->name;
            if ($paymentOption === 'dp') {
                $itemName .= ' (DP)';
            }

            $itemDetails = [
                [
                    'name' => $itemName,
                    'quantity' => 1,
                    'price' => $paymentOption === 'full' ? (int) $packet->price : $grossAmount,
                    'category' => 'Service',
                ],
            ];

            if ($paymentOption === 'full') {
                foreach ($additionalsInput as $id => $details) {
                    $model = $additionalModels[$id];
                    $itemDetails[] = [
                        'name' => $model->name,
                        'quantity' => (int) $details['quantity'],
                        'price' => (int) $model->price,
                        'category' => 'Additional',
                    ];
                }
            }

            $invoiceApi = new InvoiceApi();
            $createInvoiceRequest = new \Xendit\Invoice\CreateInvoiceRequest([
                'external_id' => $receiptCode,
                'amount' => $grossAmount,
                'payer_email' => null,
                'description' => $itemName,
                'invoice_duration' => 3600, // 1 jam = 3600 detik
                'customer' => [
                    'given_names' => $validated['customer_name'],
                    'mobile_number' => $validated['phone_number'],
                ],
                'items' => $itemDetails,
                'currency' => 'IDR',
                'success_redirect_url' => route('payment.success') . '?order_id=' . $receiptCode,
                'failure_redirect_url' => route('payment.failed'),
            ]);

            $invoice = $invoiceApi->createInvoice($createInvoiceRequest);

            return response()->json([
                'invoice_url' => $invoice['invoice_url'],
                'order_id' => $receiptCode,
            ]);

        } catch (\Throwable $e) {

    Log::error('=== XENDIT ERROR ===');
    Log::error($e->getMessage());
    Log::error($e->getFile());
    Log::error($e->getLine());

    $transaksi->status = 'gagal';
    $transaksi->note   = ($transaksi->note ?? '') . ' [Gagal membuat invoice Xendit: ' . $e->getMessage() . ']';
    $transaksi->save();

    return response()->json([
        'message' => $e->getMessage()
    ], 500);
}
    }



    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'packet_id' => 'nullable|exists:packets,id',
        ]);

        $date = $request->date;
        $allSlots = $this->generateAllSlots();

        // Durasi paket yang sedang dilihat user (untuk cek apakah slot cukup)
        $viewDuration = 30; // default: 1 slot = 30 menit
        if ($request->packet_id) {
            $packet = Packet::find($request->packet_id);
            if ($packet) {
                $viewDuration = $packet->duration_minutes;
            }
        }

        // Hitung berapa booking aktif yang "menyentuh" setiap slot
        $bookedCounts = $this->getBookedCountPerSlot($date);

        $slots = [];
        foreach ($allSlots as $time) {
            // Slot yang akan dipakai jika user memilih waktu ini
            $slotsNeeded = $this->getOccupiedSlots($time, $viewDuration);

            // Cek apakah semua slot dalam range tersedia (tidak ada yang penuh)
            // dan semua slot dalam range masuk dalam jam operasional
            $rangeAvailable = true;
            $maxBookedInRange = 0;

            foreach ($slotsNeeded as $s) {
                // Slot di luar jam operasional → tidak bisa dipilih
                if (!in_array($s, $allSlots)) {
                    $rangeAvailable = false;
                    break;
                }
                $count = $bookedCounts[$s] ?? 0;
                if ($count >= 2) {
                    $rangeAvailable = false;
                    break;
                }
                $maxBookedInRange = max($maxBookedInRange, $count);
            }

            $slots[] = [
                'time' => $time,
                'booked' => $maxBookedInRange,
                'max' => 2,
                'available' => $rangeAvailable,
            ];
        }

        return response()->json(['slots' => $slots]);
    }


    public function downloadPdf($order_id)
    {
        $booking = Gateway::with([
            'packet.product',
            'packet.printOptions',
            'additionals',
        ])
            ->where('order_id', $order_id)
            ->firstOrFail();

        $transaksi = $booking;

        $pdf = Pdf::loadView('invoices.template', compact('transaksi'))
            ->setPaper('a4', 'portrait');

        $fileName = str_replace('/', '-', $booking->order_id);

        return $pdf->download('invoice-booking-' . $fileName . '.pdf');
    }
    public function success(Request $request)
{
    $orderId = $request->query('order_id');

    if (!$orderId) {
        abort(404, 'Order ID not found.');
    }

    $transaksi = Gateway::with(['packet.product'])
        ->where('order_id', $orderId)
        ->first();

    if (!$transaksi) {
        // Order tidak ditemukan — tetap tampilkan halaman gagal/fallback
        return view('userPage.layouts.failed');
    }

    $message   = $this->buildWhatsappMessage($transaksi);
    $adminPhone = config('services.whatsapp.admin_number');
    $waUrl     = "https://wa.me/{$adminPhone}?text=" . rawurlencode($message);

    return redirect()->away($waUrl);
}

    private function buildWhatsappMessage($transaksi)
    {
        $message = "Halo Admin, saya ingin mengonfirmasi pembayaran saya.\n\n";
        $message .= "Detail Booking:\n";
        $message .= "Nama: {$transaksi->customer_name}\n";
        $message .= "No. HP: {$transaksi->phone_number}\n";
        $message .= "Produk: {$transaksi->packet->product->name}\n";
        $message .= "Paket: {$transaksi->packet->name}\n";
        $message .= "Tanggal Sesi: {$transaksi->session_date}\n";
        $message .= "Waktu Sesi: {$transaksi->session_time}\n";
        $message .= "Total Harga: Rp " . number_format($transaksi->total_price, 0, ',', '.') . "\n";
        $message .= "Status Pembayaran: {$transaksi->status}\n\n";
        $message .= "Terima kasih!";

        return $message;
    }
}