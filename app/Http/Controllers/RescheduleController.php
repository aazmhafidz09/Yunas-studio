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
        $requests = RescheduleRequest::with('transaction')
            ->where('status', 'pending')
            ->paginate(10);

        return view('reschedule.index', compact('requests'));
    }

    public function approve($id)
    {
        $rescheduleRequest = RescheduleRequest::with('transaction')->findOrFail($id);

        // Cegah approve ganda kalau request sudah diproses sebelumnya
        if ($rescheduleRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Request ini sudah diproses sebelumnya.');
        }

        DB::transaction(function () use ($rescheduleRequest) {
            // ── Update status request ──
            $rescheduleRequest->status = 'approved';
            $rescheduleRequest->save();

            // ── Update jadwal booking asli ke jadwal baru ──
            $transaksi = $rescheduleRequest->transaction;
            if ($transaksi) {
                $transaksi->session_date = $rescheduleRequest->new_schedule_date;
                $transaksi->session_time = \Carbon\Carbon::parse($rescheduleRequest->new_schedule_time)->format('H:i');
                $transaksi->save();
            }
        });

        Log::info("Reschedule request #{$rescheduleRequest->id} disetujui — jadwal transaksi {$rescheduleRequest->transaction->order_id} diperbarui.");

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