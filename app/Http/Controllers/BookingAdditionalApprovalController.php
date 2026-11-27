<?php

namespace App\Http\Controllers;

use App\Models\BookingAdditionalRequest;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingAdditionalApprovalController extends Controller
{
    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isKasir()), 403, 'AKSES DITOLAK: Halaman ini hanya untuk admin.');
    }

    /**
     * List all customer booking additional requests filtered by approval status.
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $status = $request->input('status', BookingAdditionalRequest::STATUS_PENDING);
        if (!in_array($status, [
            BookingAdditionalRequest::STATUS_PENDING,
            BookingAdditionalRequest::STATUS_APPROVED,
            BookingAdditionalRequest::STATUS_REJECTED,
        ], true)) {
            $status = BookingAdditionalRequest::STATUS_PENDING;
        }

        $counts = [
            'pending' => BookingAdditionalRequest::where('status', BookingAdditionalRequest::STATUS_PENDING)->count(),
            'approved' => BookingAdditionalRequest::where('status', BookingAdditionalRequest::STATUS_APPROVED)->count(),
            'rejected' => BookingAdditionalRequest::where('status', BookingAdditionalRequest::STATUS_REJECTED)->count(),
        ];

        $query = BookingAdditionalRequest::with(['gateway.packet.product', 'additional', 'reviewer'])
            ->where('status', $status);

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('gateway', function ($g) use ($search) {
                    $g->where('customer_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('receipt_code', 'like', "%{$search}%")
                      ->orWhere('order_id', 'like', "%{$search}%");
                })->orWhereHas('additional', function ($a) use ($search) {
                    $a->where('name', 'like', "%{$search}%");
                });
            });
        }

        $requests = $status === BookingAdditionalRequest::STATUS_PENDING
            ? $query->orderBy('created_at', 'asc')->paginate(15)->withQueryString()
            : $query->orderByDesc('reviewed_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.booking-additionals.index', compact('requests', 'status', 'counts'));
    }

    /**
     * Approve a pending booking additional request and add its cost to the gateway booking total_price.
     */
    public function approve(BookingAdditionalRequest $bookingAdditionalRequest)
    {
        $this->authorizeStaff();

        if ($bookingAdditionalRequest->status !== BookingAdditionalRequest::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan tambahan ini sudah diproses sebelumnya.');
        }

        $itemName = $bookingAdditionalRequest->additional?->name ?? 'Tambahan';
        $subtotal = 0;

        $error = null;
        DB::transaction(function () use ($bookingAdditionalRequest, &$subtotal, &$error) {
            $locked = BookingAdditionalRequest::whereKey($bookingAdditionalRequest->getKey())
                ->lockForUpdate()
                ->first();

            if (!$locked || $locked->status !== BookingAdditionalRequest::STATUS_PENDING) {
                $error = 'Pengajuan tambahan ini sudah diproses sebelumnya.';
                return;
            }

            $subtotal = (float) $locked->price * (int) $locked->quantity;

            $locked->status = BookingAdditionalRequest::STATUS_APPROVED;
            $locked->reviewed_by = auth()->id();
            $locked->reviewed_at = now();
            $locked->admin_note = null;
            $locked->save();

            $gateway = Gateway::where('transaction_id', $locked->transaction_id)->lockForUpdate()->first();
            if ($gateway) {
                $gateway->total_price = (float) $gateway->total_price + $subtotal;
                $gateway->save();
            }
        });

        if ($error) {
            return back()->with('error', $error);
        }

        $formattedSubtotal = 'Rp ' . number_format($subtotal, 0, ',', '.');

        return back()->with('success', "Tambahan \"{$itemName}\" disetujui. Total tagihan booking ditambah {$formattedSubtotal}.");
    }

    /**
     * Reject a pending booking additional request (does not add cost to the booking).
     */
    public function reject(Request $request, BookingAdditionalRequest $bookingAdditionalRequest)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($bookingAdditionalRequest->status !== BookingAdditionalRequest::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan tambahan ini sudah diproses sebelumnya.');
        }

        $itemName = $bookingAdditionalRequest->additional?->name ?? 'Tambahan';

        $bookingAdditionalRequest->status = BookingAdditionalRequest::STATUS_REJECTED;
        $bookingAdditionalRequest->admin_note = $validated['admin_note'] ?? null;
        $bookingAdditionalRequest->reviewed_by = auth()->id();
        $bookingAdditionalRequest->reviewed_at = now();
        $bookingAdditionalRequest->save();

        return back()->with('success', "Tambahan \"{$itemName}\" ditolak. Total tagihan booking tetap tidak berubah.");
    }
}
