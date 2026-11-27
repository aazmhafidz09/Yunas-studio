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
        $dayBookings = $this->getDayBookings($date);
        $bookingsPerSlot = $this->groupBookingsPerSlot($dayBookings);

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
                // Detail booking yang menempati slot ini (nama sudah disensor).
                'bookings' => $bookingsPerSlot[$time] ?? [],
            ];
        }

        // Daftar booking hari ini untuk tabel di bawah grid
        $bookings = array_map(function ($b) {
            unset($b['slots'], $b['dp_amount']);
            return $b;
        }, $dayBookings);

        return response()->json(['slots' => $slots, 'bookings' => $bookings, 'date' => $date]);
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

        // Cari berdasarkan nomor HP, nomor Invoice (receipt_code), atau order_id
        $query = Gateway::with('packet:id,name,duration_minutes,price')
            ->whereIn('status', self::ACTIVE_STATUSES)
            // Hanya booking yang tanggalnya hari ini atau ke depan
            ->whereDate('session_date', '>=', now()->toDateString());

        $this->applyIdentifierFilter($query, $identifier);

        $query->orderBy('session_date')
            ->orderBy('session_time');

        $bookings = $query->get()->map(function ($t) {
            return [
                'transaction_id' => $t->transaction_id,
                'order_id' => $t->receipt_code ?: $t->order_id,
                'receipt_code' => $t->receipt_code ?: $t->order_id,
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
                'message' => 'Tidak ada booking aktif yang ditemukan untuk nomor WhatsApp atau nomor Invoice tersebut.',
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
            'transaction_id' => 'required|exists:transaksi_gateway,transaction_id',
            'new_date' => 'required|date|after_or_equal:today',
            'new_time' => 'required|string',
            'identifier' => 'required|string|max:100',
            'reason' => 'nullable|string|max:500',
        ]);

        $identifier = trim($request->identifier);

        // Cari transaksi & pastikan milik identifier ini (No. WA / No. Invoice / Order ID)
        // Eager load packet.product agar tersedia untuk pesan WhatsApp
        $transaksiQuery = Gateway::with('packet.product')
            ->where('transaction_id', $request->transaction_id)
            ->whereIn('status', self::ACTIVE_STATUSES);

        $this->applyIdentifierFilter($transaksiQuery, $identifier);

        $transaksi = $transaksiQuery->first();

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

    /**
     * Filter query Gateway berdasarkan No. WhatsApp, No. Invoice (receipt_code), atau Order ID.
     * Mendukung awalan '#', huruf besar/kecil, format INV-... maupun INV/..., serta 08... ↔ 628...
     */
    private function applyIdentifierFilter($query, string $rawIdentifier): void
    {
        $cleaned = ltrim(trim($rawIdentifier), '#');
        $invoiceVariants = array_values(array_unique(array_filter([
            mb_strtolower($cleaned),
            mb_strtolower(str_replace('/', '-', $cleaned)),
            mb_strtolower(str_replace('-', '/', $cleaned)),
        ])));

        $digits = preg_replace('/\D/', '', $cleaned);
        $phoneVariants = array_filter([$cleaned]);
        if ($digits !== '') {
            $phoneVariants[] = $digits;
            if (str_starts_with($digits, '62')) {
                $phoneVariants[] = '0' . substr($digits, 2);
            } elseif (str_starts_with($digits, '0')) {
                $phoneVariants[] = '62' . substr($digits, 1);
            }
        }
        $phoneVariants = array_values(array_unique($phoneVariants));

        $query->where(function ($q) use ($phoneVariants, $invoiceVariants) {
            if (!empty($phoneVariants)) {
                $q->whereIn('phone_number', $phoneVariants);
            }
            foreach ($invoiceVariants as $inv) {
                $q->orWhereRaw('LOWER(receipt_code) = ?', [$inv])
                  ->orWhereRaw('LOWER(order_id) = ?', [$inv]);
            }
        });
    }

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
        // Durasi dibulatkan ke kelipatan 30 menit (threshold 15 menit),
        // mis. 40/45 menit → 1 slot, 50 menit → 2 slot. Lihat Packet::roundToSlotMinutes().
        $slotCount = Packet::slotCount($durationMinutes);
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

    /**
     * Daftar booking aktif di 1 hari, masing-masing sudah diberi studio
     * (A / B). Nama customer disensor di server agar nama lengkap tidak
     * pernah terkirim ke browser.
     *
     * Penugasan studio: booking diurutkan per jam mulai, lalu ditempatkan di
     * studio pertama yang tidak bentrok di seluruh rentang durasinya, sehingga
     * satu booking selalu berada di studio yang sama di setiap slot-nya.
     */
    private function getDayBookings(string $date): array
    {
        $transaksis = Gateway::whereDate('session_date', $date)
            ->whereNotIn('status', ['gagal'])
            ->with('packet:id,name,duration_minutes,product_id', 'packet.product:id,name')
            ->orderBy('session_time')
            ->orderBy('transaction_id')
            ->get(['transaction_id', 'customer_name', 'packet_id', 'status', 'dp_amount',
                   'session_date', 'session_time', 'created_at']);

        $studios = ['A' => [], 'B' => []]; // slot yang sudah terpakai per studio
        $bookings = [];

        foreach ($transaksis as $t) {
            $duration = $t->packet->duration_minutes ?? 60;
            $start = Carbon::parse($t->session_time);
            $occupied = $this->getOccupiedSlots($t->session_time, $duration);

            $studio = null;
            foreach ($studios as $key => $used) {
                if (!array_intersect($occupied, $used)) {
                    $studio = $key;
                    break;
                }
            }
            if ($studio) {
                $studios[$studio] = array_merge($studios[$studio], $occupied);
            }

            $bookings[] = [
                'name' => $this->maskName($t->customer_name),
                'product_name' => $t->packet->product->name ?? '-',
                'packet_name' => $t->packet->name ?? '-',
                'start' => $start->format('H:i'),
                'end' => $start->copy()->addMinutes(Packet::roundToSlotMinutes($duration))->format('H:i'),
                'session_date' => Carbon::parse($t->session_date)->toDateString(),
                'created_at' => optional($t->created_at)->toDateString(),
                'studio' => $studio, // null = di luar kapasitas (data bentrok)
                'status' => $t->status,
                'dp_amount' => $t->status === 'dp' ? (int) $t->dp_amount : null,
                'slots' => $occupied,
            ];
        }

        return $bookings;
    }

    /**
     * Kelompokkan booking harian per slot untuk popover di grid.
     */
    private function groupBookingsPerSlot(array $dayBookings): array
    {
        $perSlot = [];
        foreach ($dayBookings as $b) {
            $info = $b;
            unset($info['slots']);
            foreach ($b['slots'] as $slot) {
                $perSlot[$slot][] = $info;
            }
        }
        return $perSlot;
    }

    /**
     * "Relingga Aditya" → "R******* A*****"
     */
    private function maskName(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return '-';
        }

        return implode(' ', array_map(function ($w) {
            $len = mb_strlen($w);
            return mb_substr($w, 0, 1) . str_repeat('*', max($len - 1, 1));
        }, $words));
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

        $invoiceCode = $transaksi->receipt_code ?: $transaksi->order_id;

        $lines = [
            "Halo Admin Yunas Studio, saya mengajukan reschedule booking:",
            "",
            "No. Invoice: {$invoiceCode}",
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