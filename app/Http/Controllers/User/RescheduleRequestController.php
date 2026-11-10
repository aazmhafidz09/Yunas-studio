<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Packet;
use App\Models\RescheduleRequest;
use App\Models\Transaksi;
use App\Models\Gateway;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Additional;


class RescheduleRequestController extends Controller
{
    // ── Status transaksi yang dianggap "aktif" / berhak reschedule ──────
    // 'belum dibayar' → belum bayar sama sekali, tapi slot sudah terkunci
    // 'dp'            → sudah bayar sebagian (DP), sisa belum lunas
    // 'sudah dibayar' → sudah lunas penuh
    // 'gagal'         → SENGAJA tidak dimasukkan, karena slot sudah dilepas
    private const ACTIVE_STATUSES = ['belum dibayar', 'dp', 'sudah dibayar'];

    public function index()
    {
        $packets = Packet::with('product')
            ->where('is_active', 1)
            ->get()
            ->groupBy('product.name');

        $all_additionals = Additional::where('price', '>', 0)
            ->orderBy('name')
            ->get();
        return view('userPage.schedule', compact('packets', 'all_additionals'));
    }

    // ═══════════════════════════════════════════════════════════════════
    // API — SLOT TERSEDIA PER HARI
    // GET /jadwal/slots?date=YYYY-MM-DD&packet_id=X (packet_id opsional)
    //
    // Sama persis dengan BookingController@availableSlots,
    // namun dipisah agar route /jadwal berdiri sendiri.
    // ═══════════════════════════════════════════════════════════════════
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'packet_id' => 'nullable|exists:packets,id',
        ]);

        $date = $request->date;
        $allSlots = $this->generateAllSlots();

        $viewDuration = 30;
        if ($request->packet_id) {
            $packet = Packet::find($request->packet_id);
            if ($packet)
                $viewDuration = $packet->duration_minutes;
        }

        $bookedCounts = $this->getBookedCountPerSlot($date);

        $slots = [];
        foreach ($allSlots as $time) {
            $slotsNeeded = $this->getOccupiedSlots($time, $viewDuration);
            $rangeAvailable = true;
            $maxBookedInRange = 0;

            foreach ($slotsNeeded as $s) {
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

        return response()->json(['slots' => $slots, 'date' => $date]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // API — CARI BOOKING UNTUK RESCHEDULE
    // POST /jadwal/cari-booking
    // Body: { identifier: "08xxx..." atau "INV/..." }
    //
    // Mengembalikan semua booking aktif (belum dibayar / DP / sudah dibayar)
    // milik nomor HP atau order ID tersebut.
    // ═══════════════════════════════════════════════════════════════════
    public function cariBooking(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string|max:100',
        ]);

        $identifier = trim($request->identifier);

        // Cari berdasarkan nomor HP atau order_id
        $query = Gateway::with('packet:id,name,duration_minutes,price')
            ->whereIn('status', self::ACTIVE_STATUSES)
            // Hanya booking yang tanggalnya hari ini atau ke depan
            ->whereDate('session_date', '>=', now()->toDateString())
            ->where(function ($q) use ($identifier) {
                $q->where('phone_number', $identifier)
                    ->orWhere('order_id', $identifier);
            })
            ->orderBy('session_date')
            ->orderBy('session_time');

        $bookings = $query->get()->map(function ($t) {
            return [
                'transaction_id' => $t->transaction_id,
                'order_id' => $t->order_id,
                'customer_name' => $t->customer_name,
                'phone_number' => $t->phone_number,
                'packet_name' => $t->packet->name ?? '-',
                'duration' => $t->packet->duration_minutes ?? 60,
                'session_date' => $t->session_date,
                'session_time' => $t->session_time,
                'status' => $t->status,
                'total_price' => $t->total_price,
                'dp_amount' => $t->dp_amount,
                'remaining' => $t->status === 'dp'
                    ? $t->total_price - $t->dp_amount
                    : 0,
            ];
        });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada booking aktif yang ditemukan untuk nomor HP atau Order ID tersebut.',
                'bookings' => [],
            ], 404);
        }

        return response()->json(['bookings' => $bookings]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // API — LAKUKAN RESCHEDULE
    // POST /jadwal/reschedule
    // Body: { transaction_id, new_date, new_time, identifier, reason? }
    //
    // Validasi:
    // 1. Transaksi milik identifier (HP / order_id)
    // 2. Status masih 'belum dibayar', 'dp', atau 'sudah dibayar'
    // 3. Slot baru tidak penuh (mempertimbangkan durasi paket)
    // 4. Slot baru tidak sama dengan slot lama
    // 5. Tanggal baru tidak di masa lalu
    //
    // Setelah berhasil disimpan, response menyertakan 'whatsapp_url' agar
    // frontend bisa langsung redirect customer ke WhatsApp admin dengan
    // pesan konfirmasi otomatis (nama, order id, jadwal lama & baru).
    // ═══════════════════════════════════════════════════════════════════
    public function rescheduleRequest(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:reschedule_request,transaction_id',
            'new_date' => 'required|date|after_or_equal:today',
            'new_time' => 'required|string',
            'identifier' => 'required|string|max:100',
            'reason' => 'nullable|string|max:500',
        ]);

        $identifier = trim($request->identifier);

        // Cari transaksi & pastikan milik identifier ini
        // Eager load packet.product agar tersedia untuk pesan WhatsApp
        $transaksi = Gateway::with('packet.product')
            ->where('transaction_id', $request->transaction_id)
            ->where(function ($q) use ($identifier) {
                $q->where('phone_number', $identifier)
                    ->orWhere('order_id', $identifier);
            })
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->first();

        if (!$transaksi) {
            return response()->json([
                'message' => 'Booking tidak ditemukan atau tidak bisa di-reschedule.',
            ], 404);
        }

        // FIX: session_time dari DB berformat "10:00:00", sedangkan new_time
        // berformat "10:00". Samakan dulu ke "HH:MM" sebelum dibandingkan.
        $oldDate = $transaksi->session_date;
        $oldTime = substr($transaksi->session_time, 0, 5);

        // Cek tidak sama dengan jadwal lama
        if (
            $oldDate == $request->new_date &&
            $oldTime == $request->new_time
        ) {
            return response()->json([
                'message' => 'Jadwal baru sama dengan jadwal saat ini.',
            ], 422);
        }

        $duration = $transaksi->packet->duration_minutes ?? 60;
        $allSlots = $this->generateAllSlots();

        // Cek slot baru masuk jam operasional
        $slotsNeeded = $this->getOccupiedSlots($request->new_time, $duration);
        foreach ($slotsNeeded as $s) {
            if (!in_array($s, $allSlots)) {
                return response()->json([
                    'message' => 'Waktu ' . $request->new_time . ' dengan durasi ' .
                        $duration . ' menit melewati jam operasional (maks 18:00).',
                ], 422);
            }
        }

        // Hitung slot terpakai di tanggal baru (EXCLUDE transaksi ini sendiri)
        $bookedCounts = $this->getBookedCountPerSlot($request->new_date, $transaksi->transaction_id);

        foreach ($slotsNeeded as $slot) {
            if (($bookedCounts[$slot] ?? 0) >= 2) {
                return response()->json([
                    'message' => 'Slot ' . $request->new_time . ' pada tanggal ' .
                        $request->new_date . ' sudah penuh. Silakan pilih waktu lain.',
                ], 422);
            }
        }

        // Cek apakah sudah pernah request reschedule sebelumnya dan hanya dapat 1 request per transaksi
        $alreadyRequested = RescheduleRequest::where(
            'transaction_id',
            $transaksi->transaction_id
        )->exists();

        if ($alreadyRequested) {
            return response()->json([
                'message' => 'Booking ini sudah pernah mengajukan reschedule.'
            ], 422);
        }

        // Simpan permintaan reschedule (status pending, menunggu persetujuan admin)
        RescheduleRequest::create([
            'transaction_id' => $transaksi->transaction_id,
            'old_schedule_date' => $oldDate,
            'old_schedule_time' => $oldTime,
            'new_schedule_date' => $request->new_date,
            'new_schedule_time' => $request->new_time,
            'status' => 'pending',
            'reason' => $request->reason,
        ]);

        Log::info("Reschedule: {$transaksi->order_id} dari {$oldDate} {$oldTime} ke {$request->new_date} {$request->new_time}");

        // ── Susun pesan & URL WhatsApp untuk redirect customer ke admin ──
        $waMessage = $this->buildWhatsappMessage(
            $transaksi,
            $oldDate,
            $oldTime,
            $request->new_date,
            $request->new_time,
            $request->reason
        );
        $adminPhone = config('services.whatsapp.admin_number');
        $waUrl = "https://wa.me/{$adminPhone}?text=" . rawurlencode($waMessage);

        return response()->json([
            'message' => 'Permintaan reschedule berhasil dikirim dan sedang menunggu persetujuan admin.',
            'new_date' => $request->new_date,
            'new_time' => $request->new_time,
            'old_date' => $oldDate,
            'old_time' => $oldTime,
            'order_id' => $transaksi->order_id,
            'packet_name' => $transaksi->packet->name ?? '-',
            'whatsapp_url' => $waUrl,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPERS (sama dengan BookingController — bisa diextract ke trait)
    // ═══════════════════════════════════════════════════════════════════
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
        return $slots;
    }

    private function getOccupiedSlots(string $startTime, int $durationMinutes): array
    {
        $slots = [];
        // FIX: Carbon::parse menerima "10:00" maupun "10:00:00".
        // createFromFormat('H:i') gagal dengan "Trailing data" untuk "10:00:00".
        $start = Carbon::parse($startTime);
        $slotCount = (int) ceil($durationMinutes / 30);
        for ($i = 0; $i < $slotCount; $i++) {
            $slots[] = $start->copy()->addMinutes($i * 30)->format('H:i');
        }
        return $slots;
    }

    /**
     * Hitung berapa booking aktif yang "menyentuh" setiap slot di 1 hari.
     * $excludeTransactionId: abaikan transaksi ini (dipakai saat reschedule
     * agar slot lama milik booking tersebut tidak dihitung sebagai konflik).
     */
    private function getBookedCountPerSlot(string $date, ?int $excludeTransactionId = null): array
    {
        $booked = [];

        // FIX: sebelumnya `whereNotIn('status', self:ACTIVE_STATUSES)`:
        // (1) syntax error karena `self:` kurang satu titik dua, dan
        // (2) logikanya terbalik, yang dihitung justru transaksi di LUAR
        //     status aktif. Dikembalikan ke aturan yang sama dengan
        //     BookingController: semua status kecuali 'gagal' menahan slot.
        $query = Gateway::whereDate('session_date', $date)
            ->whereNotIn('status', ['gagal'])
            ->with('packet:id,duration_minutes');

        if ($excludeTransactionId) {
            $query->where('transaction_id', '!=', $excludeTransactionId);
        }

        $transaksis = $query->get(['session_time', 'packet_id', 'transaction_id']);

        foreach ($transaksis as $t) {
            $duration = $t->packet->duration_minutes ?? 60;
            $occupied = $this->getOccupiedSlots($t->session_time, $duration);
            foreach ($occupied as $slot) {
                $booked[$slot] = ($booked[$slot] ?? 0) + 1;
            }
        }

        return $booked;
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER — Susun pesan WhatsApp otomatis untuk admin (reschedule)
    // Menampilkan perbandingan jadwal lama vs baru, bukan hanya satu
    // jadwal tunggal, agar admin langsung paham perubahan yang diajukan.
    // ═══════════════════════════════════════════════════════════════════
    private function buildWhatsappMessage(
        Gateway $transaksi, // FIX: sebelumnya Transaksi, padahal objek yang dikirim adalah Gateway (TypeError)
        string $oldDate,
        string $oldTime,
        string $newDate,
        string $newTime,
        ?string $reason = null
    ): string {
        $productName = $transaksi->packet->product->name ?? '-';
        $packetName  = $transaksi->packet->name ?? '-';
        $total       = number_format($transaksi->total_price, 0, ',', '.');

        $lines = [
            "Halo Admin Yunas Studio, saya mengajukan reschedule booking:",
            "",
            "Order ID: {$transaksi->order_id}",
            "Nama: {$transaksi->customer_name}",
            "No. WhatsApp: {$transaksi->phone_number}",
            "Produk: {$productName}",
            "Paket: {$packetName}",
            "Total Tagihan: Rp{$total}",
            "",
            "Jadwal Lama: {$oldDate} pukul {$oldTime} WIB",
            "Jadwal Baru: {$newDate} pukul {$newTime} WIB",
        ];

        if ($reason) {
            $lines[] = "";
            $lines[] = "Alasan: {$reason}";
        }

        $lines[] = "";
        $lines[] = "Mohon konfirmasi/approve ya, terima kasih!";

        return implode("\n", $lines);
    }
}