<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    protected GoogleDriveService $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display the photo gallery page for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Get all transactions for this user, ordered by latest first
        $transactions = Transaksi::with(['packet.product'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('phone_number', $user->username);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Determine selected transaction (default to latest)
        $selectedTransaksiId = $request->input('transaksi_id');
        $selectedTransaksi = null;

        if ($selectedTransaksiId) {
            $selectedTransaksi = $transactions->firstWhere('transaction_id', $selectedTransaksiId);
        }

        // Fallback to latest transaction if not selected or not found
        if (!$selectedTransaksi && $transactions->isNotEmpty()) {
            $selectedTransaksi = $transactions->first();
        }

        // 3. Determine photo source type ('result' for final edits, 'raw' for original gallery)
        $hasResult = !empty($selectedTransaksi?->url_photos_result);
        $hasRaw = !empty($selectedTransaksi?->url_images);
        $hasBothTypes = $hasResult && $hasRaw;

        $type = $request->input('type');
        if (!in_array($type, ['result', 'raw'])) {
            // Default to 'result' if available, otherwise 'raw'
            $type = $hasResult ? 'result' : 'raw';
        }

        $activeUrl = ($type === 'result')
            ? $selectedTransaksi?->url_photos_result
            : $selectedTransaksi?->url_images;

        // 4. Fetch photos from Google Drive via service
        $forceRefresh = $request->boolean('refresh');
        $photosData = [
            'success' => false,
            'files' => [],
            'count' => 0,
            'error' => null,
            'message' => null,
        ];

        if ($selectedTransaksi && !empty($activeUrl)) {
            $photosData = $this->googleDriveService->getPhotosFromUrl($activeUrl, $forceRefresh);
        } elseif ($selectedTransaksi && empty($activeUrl)) {
            $photosData = [
                'success' => false,
                'files' => [],
                'count' => 0,
                'error' => 'NO_LINK',
                'message' => 'Link Google Drive belum diinput oleh admin untuk transaksi ini.',
            ];
        }

        return view('user.photo.index', [
            'transactions' => $transactions,
            'selectedTransaksi' => $selectedTransaksi,
            'type' => $type,
            'activeUrl' => $activeUrl,
            'hasBothTypes' => $hasBothTypes,
            'hasResult' => $hasResult,
            'hasRaw' => $hasRaw,
            'photosData' => $photosData,
        ]);
    }
}

