<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\Models\RescheduleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RescheduleController extends Controller
{
    public function index()
    {
        $requests = RescheduleRequest::with(['transaction', 'gatewayTransaction'])
            ->where('status', 'pending')
            ->paginate(10);

        return view('reschedule.index', compact('requests'));
    }

    public function approve($id)
    {
        $rescheduleRequest = RescheduleRequest::with(['transaction', 'gatewayTransaction'])->findOrFail($id);

        // Cegah approve ganda kalau request sudah diproses sebelumnya
        if ($rescheduleRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($rescheduleRequest) {
            // ── Update status request ──
            $rescheduleRequest->status = 'approved';
            $rescheduleRequest->approved_by = auth()->id();
            $rescheduleRequest->approved_at = now();
            $rescheduleRequest->save();

            $newTime = \Carbon\Carbon::parse($rescheduleRequest->new_schedule_time)->format('H:i');

            // ── Update jadwal booking di transaksi_gateway maupun transaksi ──
            if ($rescheduleRequest->gatewayTransaction) {
                $rescheduleRequest->gatewayTransaction->session_date = $rescheduleRequest->new_schedule_date;
                $rescheduleRequest->gatewayTransaction->session_time = $newTime;
                $rescheduleRequest->gatewayTransaction->save();
            }
            if ($rescheduleRequest->transaction) {
                $rescheduleRequest->transaction->session_date = $rescheduleRequest->new_schedule_date;
                $rescheduleRequest->transaction->session_time = $newTime;
                $rescheduleRequest->transaction->save();
            }
        });

        $refCode = $rescheduleRequest->gatewayTransaction?->receipt_code
            ?? $rescheduleRequest->gatewayTransaction?->order_id
            ?? $rescheduleRequest->transaction?->order_id
            ?? $rescheduleRequest->transaction_id;

        Log::info("Reschedule request #{$rescheduleRequest->id} disetujui — jadwal transaksi {$refCode} diperbarui.");

        return redirect()->back()->with('success', 'Reschedule request approved successfully.');
    }

    public function reject($id)
    {
        $rescheduleRequest = RescheduleRequest::findOrFail($id);

        if ($rescheduleRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        $rescheduleRequest->status = 'rejected';
        $rescheduleRequest->save();

        return redirect()->back()->with('success', 'Reschedule request rejected successfully.');
    }
}