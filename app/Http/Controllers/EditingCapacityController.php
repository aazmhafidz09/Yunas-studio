<?php

namespace App\Http\Controllers;

use App\Models\EditingCapacity;
use App\Services\EditingQueueService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EditingCapacityController extends Controller
{
    public function __construct(protected EditingQueueService $editingQueueService)
    {
    }

    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isKasir()), 403, 'AKSES DITOLAK: Halaman ini hanya untuk admin.');
    }

    /**
     * Display daily editing capacity settings and the live editing queue schedule.
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $schedule = $this->editingQueueService->buildSchedule(null, true);

        $defaultRecord = EditingCapacity::where('date_key', EditingCapacity::DEFAULT_KEY)
            ->with('updater')
            ->first();

        $dateOverrides = EditingCapacity::where('date_key', '!=', EditingCapacity::DEFAULT_KEY)
            ->whereNotNull('date')
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->with('updater')
            ->orderBy('date', 'asc')
            ->get();

        // Optional filtering on the queued transactions table
        $filterDate = trim((string) $request->input('date', ''));
        $search = trim((string) $request->input('q', ''));

        $queueItems = $schedule['items'];

        if ($filterDate !== '') {
            $queueItems = $queueItems->filter(fn ($item) => $item['estimated_date_string'] === $filterDate);
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $queueItems = $queueItems->filter(function ($item) use ($needle) {
                $trx = $item['transaksi'];
                return str_contains(mb_strtolower((string) $trx->receipt_code), $needle)
                    || str_contains(mb_strtolower((string) $trx->customer_name), $needle)
                    || str_contains(mb_strtolower((string) $trx->phone_number), $needle);
            });
        }

        $queueItems = $queueItems->values();

        return view('admin.editing-capacity.index', [
            'schedule' => $schedule,
            'defaultRecord' => $defaultRecord,
            'dateOverrides' => $dateOverrides,
            'queueItems' => $queueItems,
            'filterDate' => $filterDate,
            'search' => $search,
        ]);
    }

    /**
     * Update the global default daily editing capacity.
     */
    public function updateDefault(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'capacity' => 'required|integer|min:1|max:500',
            'note' => 'nullable|string|max:255',
        ], [
            'capacity.required' => 'Kapasitas editing per hari wajib diisi.',
            'capacity.integer' => 'Kapasitas editing harus berupa angka bulat.',
            'capacity.min' => 'Kapasitas default minimal 1 antrian per hari.',
            'capacity.max' => 'Kapasitas maksimal 500 antrian per hari.',
        ]);

        EditingCapacity::updateOrCreate(
            ['date_key' => EditingCapacity::DEFAULT_KEY],
            [
                'date' => null,
                'capacity' => (int) $validated['capacity'],
                'note' => $validated['note'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()
            ->route('editing-capacity.index')
            ->with('success', "Kapasitas editing default berhasil diperbarui menjadi {$validated['capacity']} antrian per hari.");
    }

    /**
     * Create or update a date-specific editing capacity override.
     */
    public function storeDateOverride(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'capacity' => 'required|integer|min:0|max:500',
            'note' => 'nullable|string|max:255',
        ], [
            'date.required' => 'Tanggal wajib dipilih.',
            'date.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
            'capacity.required' => 'Kapasitas wajib diisi.',
            'capacity.integer' => 'Kapasitas harus berupa angka bulat.',
            'capacity.min' => 'Kapasitas minimal 0 (untuk hari libur/tutup editing).',
            'capacity.max' => 'Kapasitas maksimal 500 antrian per hari.',
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $dateKey = $date->toDateString();

        EditingCapacity::updateOrCreate(
            ['date_key' => $dateKey],
            [
                'date' => $dateKey,
                'capacity' => (int) $validated['capacity'],
                'note' => $validated['note'] ?? null,
                'updated_by' => auth()->id(),
            ]
        );

        $dateLabel = $this->editingQueueService->formatDateLabel($date);
        $capLabel = ((int) $validated['capacity'] === 0)
            ? '0 (Libur / Tidak ada editing)'
            : "{$validated['capacity']} antrian";

        return redirect()
            ->route('editing-capacity.index')
            ->with('success', "Kapasitas editing untuk {$dateLabel} diatur menjadi {$capLabel}.");
    }

    /**
     * Remove a date-specific override so that date falls back to the default daily capacity.
     */
    public function destroyDateOverride(EditingCapacity $editingCapacity)
    {
        $this->authorizeStaff();

        if ($editingCapacity->isDefault()) {
            return redirect()
                ->route('editing-capacity.index')
                ->with('error', 'Kapasitas default tidak dapat dihapus, silakan ubah nilainya saja.');
        }

        $dateLabel = $editingCapacity->date
            ? $this->editingQueueService->formatDateLabel($editingCapacity->date)
            : $editingCapacity->date_key;

        $editingCapacity->delete();

        return redirect()
            ->route('editing-capacity.index')
            ->with('success', "Pengaturan khusus tanggal {$dateLabel} dihapus. Kembali mengikuti kapasitas default.");
    }
}
