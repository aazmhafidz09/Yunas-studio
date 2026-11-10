<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\Transaksi;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Balance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════
    // WEBHOOK — Dipanggil otomatis Xendit setelah status invoice berubah
    // Ini satu-satunya tempat status transaksi diupdate dari Xendit
    // PENTING: Tambahkan 'payment/webhook' ke VerifyCsrfToken@$except
    //
    // Alur status transaksi:
    //   'belum dibayar' → dibuat di BookingController, slot sudah terkunci
    //   'gagal'         → gagal/expired dari Xendit, ATAU auto-expired oleh
    //                      scheduled command (transaksi:expire-stale) karena
    //                      tidak dibayar dalam batas waktu, slot KEMBALI tersedia
    //   'dp'            → dibayar sebagian (nominal < total_price)
    //   'sudah dibayar' → dibayar lunas (nominal >= total_price)
    //   'perlu_review'  → pembayaran diterima TAPI transaksi sudah lebih dulu
    //                      di-auto-expire dan slotnya sudah diambil transaksi
    //                      lain. Butuh keputusan manual admin (refund/reschedule).
    //
    // Status ('dp' vs 'sudah dibayar') ditentukan dengan MEMBANDINGKAN
    // nominal yang benar-benar disettle Xendit (paid_amount notifikasi)
    // terhadap total_price transaksi — bukan dari flag terpisah.
    // Ini membuat perilaku konsisten dengan alur input manual admin
    // di TransaksiController.
    // ═══════════════════════════════════════════════════════════════════
    public function handleWebhook(Request $request): JsonResponse
    {
        // ── Verifikasi callback token 
        $callbackToken = $request->header('x-callback-token');
        if ($callbackToken !== config('xendit.callback_token')) {
            Log::warning('Xendit Webhook: Invalid callback token');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $payload = $request->all();

            $orderId       = $payload['external_id'] ?? null;   // setara order_id
            $status        = $payload['status'] ?? null;        // PAID, EXPIRED, PENDING, dll
            $paymentMethod = $payload['payment_method'] ?? ($payload['payment_channel'] ?? 'unknown');
            $paidAmount    = (float) ($payload['paid_amount'] ?? $payload['amount'] ?? 0);

            Log::info("Xendit Webhook: {$orderId} - {$status} - Rp{$paidAmount}");

            $transaksi = Gateway::where('order_id', $orderId)->first();

            if (!$transaksi) {
                Log::warning("Transaksi tidak ditemukan di database: {$orderId}");
                return response()->json(['message' => 'Transaction not found.'], 404);
            }

            // ── Idempoten ──
            if (in_array($transaksi->status, ['sudah dibayar', 'dp', 'perlu_review'])) {
                return response()->json(['message' => 'Already processed.']);
            }

            // ── Status PENDING ── Xendit invoice baru dibuat = PENDING
            if ($status === 'PENDING') {
                return response()->json(['message' => 'Payment pending.']);
            }

            // ── Status GAGAL / EXPIRED ──
            if (in_array($status, ['EXPIRED'])) {
                // Idempoten juga di sini — kalau sudah 'gagal' (mis. sudah
                // di-auto-expire duluan oleh scheduled command), tidak perlu
                // diproses ulang.
                if ($transaksi->status !== 'gagal') {
                    $transaksi->status = 'gagal';
                    $transaksi->save();
                    Log::info("Transaksi {$orderId} dibatalkan — slot dikembalikan.");
                }
                return response()->json(['message' => 'Payment cancelled.']);
            }

            // ── Cek keberhasilan bayar ──
            if ($status !== 'PAID' && $status !== 'SETTLED') {
                return response()->json(['message' => 'Not paid.']);
            }

            // ── Pembayaran BERHASIL ──
            DB::transaction(function () use ($transaksi, $paymentMethod, $paidAmount, $orderId) {

                // ── Deteksi pembayaran yang datang TERLAMBAT ──
                // Kasus: transaksi ini sudah lebih dulu ditandai 'gagal'
                // (baik oleh webhook EXPIRED maupun oleh scheduled command
                // transaksi:expire-stale karena melewati batas waktu bayar),
                // TAPI ternyata customer sempat membayar sebelum expired dan
                // notifikasinya baru sampai sekarang (delay/retry Xendit).
                //
                // Kalau slot yang sama sudah diambil transaksi lain yang
                // masih aktif, kita TIDAK BOLEH otomatis menimpa jadi
                // 'sudah dibayar' — itu akan membuat dua booking lunas di
                // slot yang sama. Uangnya sudah masuk, jadi harus ditandai
                // untuk direview manual oleh admin (refund atau reschedule),
                // bukan diproses otomatis.
                if ($transaksi->status === 'gagal') {
                    $conflict = Gateway::where('id', '!=', $transaksi->id)
                        ->where('session_date', $transaksi->session_date)
                        ->where('session_time', $transaksi->session_time)
                        ->whereNotIn('status', ['gagal'])
                        ->exists();

                    if ($conflict) {
                        $transaksi->status = 'perlu_review';
                        $transaksi->note = ($transaksi->note ?? '')
                            . " [PENTING: Pembayaran Rp{$paidAmount} diterima setelah transaksi"
                            . " {$orderId} expired & slotnya sudah diambil transaksi lain."
                            . " Perlu review manual admin — kemungkinan refund/reschedule.]";
                        $transaksi->save();

                        Log::warning("Transaksi {$orderId}: pembayaran telat, slot sudah diambil pihak lain. Butuh review manual admin.");
                        return; // stop — jangan lanjut ke logic normal di bawah
                    }
                }

                // ── Logic normal ──
                if ($paidAmount >= (float) $transaksi->total_price) {
                    $transaksi->status = 'sudah dibayar';
                } else {
                    $transaksi->status    = 'dp';
                    $transaksi->dp_amount = $paidAmount;
                }

                $transaksi->payment_type   = $this->mapPaymentType($paymentMethod);
                $transaksi->process_status = 'Pelanggan Belum Foto';
                $transaksi->save();

                if ($transaksi->status === 'sudah dibayar') {
                    $this->recordIncome($transaksi);
                } else {
                    $this->deleteIncome($transaksi);
                }
            });

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Xendit Webhook Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // REDIRECT PAGE — Halaman setelah user selesai dari popup Xendit
    // Xendit redirect ke sini dengan query param ?order_id=INV/...
    // ═══════════════════════════════════════════════════════════════════
    // public function paymentSuccess(Request $request)
    // {
    //     $orderId = $request->query('order_id');

    //     if (!$orderId) {
    //         abort(404, 'Order ID not found.');
    //     }

    //     // Eager load packet & product agar tidak N+1 query di Blade
    //     $transaksi = Transaksi::with(['packet.product'])
    //         ->where('order_id', $orderId)
    //         ->first();

    //     // Transaksi tidak ditemukan — tampilkan halaman sukses tanpa detail
    //     if (!$transaksi) {
    //         return view('userPage.layouts.success', ['booking' => null]);
    //     }

    //     $booking = (object) [
    //         'order_id'      => $transaksi->order_id,
    //         'customer_name' => $transaksi->customer_name,
    //         'phone_number'  => $transaksi->phone_number,
    //         'session_date'  => $transaksi->session_date,
    //         'session_time'  => $transaksi->session_time,
    //         'total_price'   => $transaksi->total_price,
    //         'status'        => $transaksi->status,
    //         'dp_amount'     => $transaksi->dp_amount,
    //         'remaining'     => $transaksi->status === 'dp'
    //                             ? $transaksi->total_price - $transaksi->dp_amount
    //                             : 0,
    //         'payment_type'  => $transaksi->payment_type,
    //         'receipt_code'  => $transaksi->receipt_code,
    //         'packet'        => $transaksi->packet,
    //     ];

    //     return view('userPage.layouts.success', compact('booking'));
    // }

    public function paymentSuccess(Request $request)
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

// ═══════════════════════════════════════════════════════════════════
// HELPER — Susun pesan WhatsApp otomatis untuk admin
// ═══════════════════════════════════════════════════════════════════
private function buildWhatsappMessage(Gateway $transaksi): string
{
    $productName = $transaksi->packet->product->name ?? '-';
    $packetName  = $transaksi->packet->name ?? '-';
    $total       = number_format($transaksi->total_price, 0, ',', '.');
    $isOnLocation = str_contains($transaksi->note ?? '', 'Bayar di Lokasi');

    $lines = [
        $isOnLocation
            ? "Halo Admin Yunas Studio, saya ingin mengonfirmasi booking jadwal sesi foto (pembayaran di lokasi):"
            : "Halo Admin Yunas Studio, saya baru saja melakukan pembayaran booking:",
        "",
        "Order ID: {$transaksi->order_id}",
        "Nama: {$transaksi->customer_name}",
        "No. WhatsApp: {$transaksi->phone_number}",
        "Produk: {$productName}",
        "Paket: {$packetName}",
        "Tanggal Sesi: {$transaksi->session_date}",
        "Jam Sesi: {$transaksi->session_time}",
        "Total Tagihan: Rp{$total}",
    ];

    if ($isOnLocation) {
        $lines[] = "Metode Pembayaran: Bayar di lokasi";
    } elseif ($transaksi->status === 'dp' && $transaksi->dp_amount) {
        $sisa = $transaksi->total_price - $transaksi->dp_amount;
        $lines[] = "DP Dibayar: Rp" . number_format($transaksi->dp_amount, 0, ',', '.');
        $lines[] = "Sisa Pembayaran: Rp" . number_format($sisa, 0, ',', '.');
    }

    $lines[] = "";
    $lines[] = "Mohon konfirmasi ya, terima kasih!";

    return implode("\n", $lines);
}

    public function paymentFailed(Request $request)
    {
        return view('userPage.layouts.failed');
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER — Catat pemasukan ke tabel expenses
    // Dipanggil HANYA saat status transaksi 'sudah dibayar' (lunas penuh).
    // Disamakan persis dengan TransaksiController@recordIncome agar
    // pembukuan konsisten baik dari alur web maupun input manual admin.
    // Menggunakan receipt_code sebagai nama unik agar tidak duplikat.
    // ═══════════════════════════════════════════════════════════════════
    private function recordIncome(Gateway $transaksi): void
    {
        $description = "Billed To:\nName: {$transaksi->customer_name}\n"
                     . "Phone: {$transaksi->phone_number}\n"
                     . "Invoice Details:\nTransaction Date: "
                     . $transaksi->created_at->format('d-m-Y');

        $categoryId = ExpenseCategory::where('name', 'Transaction')->first()->id ?? 1;
        $balance    = Balance::first();

        // Cek apakah income untuk transaksi ini sudah pernah dicatat sebelumnya
        // (misalnya webhook dikirim dua kali oleh Xendit)
        $existing = Expense::where('name', $transaksi->receipt_code)
            ->where('type', 'income')
            ->first();

        if ($existing) {
            // Jika sudah ada, update selisih ke balance agar tidak dobel hitung
            $diff = $transaksi->total_price - $existing->amount;
            if ($diff != 0 && $balance) {
                $balance->amount += $diff;
                $balance->save();
            }
            $existing->update([
                'description'      => $description,
                'amount'           => $transaksi->total_price,
                'paid_amount'      => $transaksi->total_price,
                'remaining_amount' => 0,
                'expense_date'     => now(),
            ]);
        } else {
            // Income belum ada → buat baru dan tambahkan ke balance
            Expense::create([
                'name'             => $transaksi->receipt_code,
                'type'             => 'income',
                'description'      => $description,
                'amount'           => $transaksi->total_price,
                'paid_amount'      => $transaksi->total_price,
                'remaining_amount' => 0,
                'expense_date'     => now(),
                'category_id'      => $categoryId,
                'is_paid'          => true,
            ]);
            if ($balance) {
                $balance->amount += $transaksi->total_price;
                $balance->save();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER — Hapus pencatatan pemasukan
    // Dipanggil saat status BUKAN 'sudah dibayar' (misal 'dp', 'gagal',
    // atau 'belum dibayar'). Disamakan persis dengan
    // TransaksiController@deleteIncome.
    // ═══════════════════════════════════════════════════════════════════
    private function deleteIncome(Gateway $transaksi): void
    {
        $expense = Expense::where('name', $transaksi->receipt_code)
               ->where('type', 'income')
               ->first();

        if ($expense) {
            $expense->delete();
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER — Normalisasi nama metode pembayaran dari Xendit
    // Xendit mengembalikan string seperti 'RETAIL_OUTLET', 'BANK_TRANSFER',
    // 'EWALLET', 'QRIS', dsb. Disederhanakan menjadi dua kategori.
    // ═══════════════════════════════════════════════════════════════════
    private function mapPaymentType(string $paymentMethod): string
    {
        // Xendit: 'RETAIL_OUTLET' untuk Alfamart/Indomaret, sisanya VA/EWALLET/QRIS
        $cashMethods = ['RETAIL_OUTLET', 'ALFAMART', 'INDOMARET'];

        return in_array(strtoupper($paymentMethod), $cashMethods)
            ? 'Cash'
            : 'Transfer/Qris';
    }
}