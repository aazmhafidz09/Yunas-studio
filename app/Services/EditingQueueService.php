<?php

namespace App\Services;

use App\Models\EditingCapacity;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EditingQueueService
{
    /**
     * Maximum days to look ahead when assigning queue slots across daily capacities.
     */
    private const MAX_LOOKAHEAD_DAYS = 365;

    /**
     * Get global default daily editing capacity (minimum 1).
     */
    public function getDefaultCapacity(): int
    {
        $record = EditingCapacity::where('date_key', EditingCapacity::DEFAULT_KEY)->first();

        return max(1, (int) ($record?->capacity ?? EditingCapacity::FALLBACK_DAILY_CAPACITY));
    }

    /**
     * Get effective editing capacity for a specific date (date override or global default).
     */
    public function getCapacityForDate(Carbon $date, ?array $overrides = null, ?int $defaultCapacity = null): int
    {
        $dateKey = $date->toDateString();

        if ($overrides !== null) {
            if (array_key_exists($dateKey, $overrides)) {
                return max(0, (int) $overrides[$dateKey]);
            }

            return max(1, $defaultCapacity ?? $this->getDefaultCapacity());
        }

        $override = EditingCapacity::where('date_key', $dateKey)->first();
        if ($override !== null) {
            return max(0, (int) $override->capacity);
        }

        return max(1, $defaultCapacity ?? $this->getDefaultCapacity());
    }

    /**
     * Load all date-specific capacity overrides from a start date onwards as ['YYYY-MM-DD' => capacity].
     */
    public function getOverridesFrom(Carbon $startDate): array
    {
        return EditingCapacity::where('date_key', '!=', EditingCapacity::DEFAULT_KEY)
            ->whereNotNull('date')
            ->whereDate('date', '>=', $startDate->toDateString())
            ->pluck('capacity', 'date_key')
            ->map(fn ($cap) => max(0, (int) $cap))
            ->all();
    }

    /**
     * Base query for transactions currently waiting in the active editing queue.
     */
    public function activeQueueQuery(): Builder
    {
        return Transaksi::query()
            ->where(function ($q) {
                $q->whereNull('url_photos_result')
                  ->orWhere('url_photos_result', '');
            })
            ->whereNotIn('process_status', ['Proses Cetak', 'Selesai'])
            ->where(function ($q) {
                $q->whereIn('edit_selection_status', [
                    Transaksi::EDIT_SELECTION_SUBMITTED,
                    Transaksi::EDIT_SELECTION_APPROVED,
                ])->orWhere('process_status', 'Proses Edit');
            });
    }

    /**
     * Count editing jobs already completed on a specific date (consumes that day's capacity).
     */
    public function getCompletedCountForDate(Carbon $date): int
    {
        return Transaksi::query()
            ->whereDate('editing_completed_at', $date->toDateString())
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('url_photos_result')
                        ->where('url_photos_result', '!=', '');
                })->orWhereIn('process_status', ['Proses Cetak', 'Selesai']);
            })
            ->count();
    }

    /**
     * Build the full active queue schedule, assigning each queued transaction:
     *  - queue_number (1-based overall active queue position)
     *  - estimated_date (Carbon date when it fits within daily capacity)
     *  - estimated_date_string ('YYYY-MM-DD')
     *  - estimated_date_label (human-readable Indonesian date label, e.g. 'Hari Ini (27 Nov 2026)')
     *  - day_slot (1-based slot number within that day's capacity, accounting for completed jobs today)
     *  - day_capacity (capacity configured for that estimated date)
     *  - is_today (bool)
     *
     * @return array{
     *     default_capacity: int,
     *     today_capacity: int,
     *     today_completed: int,
     *     today_queued: int,
     *     today_used: int,
     *     today_remaining: int,
     *     total_queued: int,
     *     next_queue_number: int,
     *     next_estimated_date: Carbon,
     *     next_estimated_date_label: string,
     *     next_day_slot: int,
     *     next_day_capacity: int,
     *     by_transaction_id: array<int, array<string, mixed>>,
     *     items: Collection,
     *     daily_breakdown: array<string, array<string, mixed>>
     * }
     */
    public function buildSchedule(?Carbon $today = null, bool $withRelations = false): array
    {
        $today = ($today ? $today->copy() : Carbon::today())->startOfDay();
        $defaultCapacity = $this->getDefaultCapacity();
        $overrides = $this->getOverridesFrom($today);
        $todayCompleted = $this->getCompletedCountForDate($today);

        $query = $this->activeQueueQuery();
        if ($withRelations) {
            $query->with(['packet.product', 'packet.additionalDefaults.additional', 'additionals'])
                  ->withCount(['selectedPhotos as edit_photos_count' => fn ($s) => $s->where('is_print', false)]);
        }

        $queuedTransactions = $query
            ->orderByRaw('COALESCE(editing_queued_at, edit_selection_submitted_at, edit_selection_reviewed_at, updated_at, created_at) ASC')
            ->orderBy('transaction_id', 'ASC')
            ->get();

        $cursorDate = $today->copy();
        $cursorUsed = $todayCompleted;
        $cursorCapacity = $this->getCapacityForDate($cursorDate, $overrides, $defaultCapacity);

        $advanceToAvailableSlot = function () use (&$cursorDate, &$cursorUsed, &$cursorCapacity, $overrides, $defaultCapacity) {
            $steps = 0;
            while ($cursorUsed >= $cursorCapacity && $steps < self::MAX_LOOKAHEAD_DAYS) {
                $cursorDate = $cursorDate->copy()->addDay();
                $cursorUsed = 0;
                $cursorCapacity = $this->getCapacityForDate($cursorDate, $overrides, $defaultCapacity);
                $steps++;
            }
        };

        $byTransactionId = [];
        $items = collect();
        $dailyQueuedCounts = [];

        foreach ($queuedTransactions as $index => $trx) {
            $advanceToAvailableSlot();

            $queueNumber = $index + 1;
            $cursorUsed++;
            $daySlot = $cursorUsed;
            $dateStr = $cursorDate->toDateString();
            $dailyQueuedCounts[$dateStr] = ($dailyQueuedCounts[$dateStr] ?? 0) + 1;

            $entry = [
                'transaction_id' => $trx->transaction_id,
                'transaksi' => $trx,
                'queue_number' => $queueNumber,
                'estimated_date' => $cursorDate->copy(),
                'estimated_date_string' => $dateStr,
                'estimated_date_label' => $this->formatDateLabel($cursorDate, $today),
                'day_slot' => $daySlot,
                'day_capacity' => $cursorCapacity,
                'is_today' => $cursorDate->isSameDay($today),
                'queued_at' => $trx->editing_queued_at
                    ?? $trx->edit_selection_submitted_at
                    ?? $trx->edit_selection_reviewed_at
                    ?? $trx->updated_at,
            ];

            $byTransactionId[$trx->transaction_id] = $entry;
            $items->push($entry);
        }

        // Compute the next available slot if a new transaction enters right now
        $advanceToAvailableSlot();
        $nextQueueNumber = $queuedTransactions->count() + 1;
        $nextDaySlot = $cursorUsed + 1;
        $nextEstimatedDate = $cursorDate->copy();
        $nextDayCapacity = $cursorCapacity;

        $todayStr = $today->toDateString();
        $todayCapacity = $this->getCapacityForDate($today, $overrides, $defaultCapacity);
        $todayQueued = $dailyQueuedCounts[$todayStr] ?? 0;
        $todayUsed = $todayCompleted + $todayQueued;
        $todayRemaining = max(0, $todayCapacity - $todayUsed);

        // Build 7-day rolling forecast for the admin dashboard
        $dailyBreakdown = [];
        for ($d = 0; $d < 7; $d++) {
            $day = $today->copy()->addDays($d);
            $dayStr = $day->toDateString();
            $dayCap = $this->getCapacityForDate($day, $overrides, $defaultCapacity);
            $completed = $d === 0 ? $todayCompleted : 0;
            $queued = $dailyQueuedCounts[$dayStr] ?? 0;
            $used = $completed + $queued;

            $dailyBreakdown[$dayStr] = [
                'date' => $day,
                'date_string' => $dayStr,
                'label' => $this->formatDateLabel($day, $today),
                'day_name' => $this->indonesianDayName($day),
                'capacity' => $dayCap,
                'is_override' => array_key_exists($dayStr, $overrides),
                'completed' => $completed,
                'queued' => $queued,
                'used' => $used,
                'remaining' => max(0, $dayCap - $used),
                'is_today' => $d === 0,
            ];
        }

        return [
            'default_capacity' => $defaultCapacity,
            'today_capacity' => $todayCapacity,
            'today_is_override' => array_key_exists($todayStr, $overrides),
            'today_completed' => $todayCompleted,
            'today_queued' => $todayQueued,
            'today_used' => $todayUsed,
            'today_remaining' => $todayRemaining,
            'total_queued' => $queuedTransactions->count(),
            'next_queue_number' => $nextQueueNumber,
            'next_estimated_date' => $nextEstimatedDate,
            'next_estimated_date_label' => $this->formatDateLabel($nextEstimatedDate, $today),
            'next_day_slot' => $nextDaySlot,
            'next_day_capacity' => $nextDayCapacity,
            'by_transaction_id' => $byTransactionId,
            'items' => $items,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Get queue info for a single transaction (returns null if not in the active editing queue).
     */
    public function getQueueInfoForTransaction(Transaksi $transaksi, ?Carbon $today = null): ?array
    {
        $schedule = $this->buildSchedule($today);

        return $schedule['by_transaction_id'][$transaksi->transaction_id] ?? null;
    }

    public function formatDateLabel(Carbon $date, ?Carbon $today = null): string
    {
        $today = ($today ? $today->copy() : Carbon::today())->startOfDay();
        $formatted = $date->format('d') . ' ' . $this->indonesianMonthShort($date) . ' ' . $date->format('Y');

        if ($date->isSameDay($today)) {
            return "Hari Ini ({$formatted})";
        }

        if ($date->isSameDay($today->copy()->addDay())) {
            return "Besok ({$formatted})";
        }

        return $this->indonesianDayName($date) . ", {$formatted}";
    }

    private function indonesianDayName(Carbon $date): string
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ][$date->dayOfWeek] ?? $date->format('l');
    }

    private function indonesianMonthShort(Carbon $date): string
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ][$date->month] ?? $date->format('M');
    }
}
