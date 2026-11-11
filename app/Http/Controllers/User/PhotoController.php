<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SelectedPhoto;
use App\Models\Transaksi;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // 1. Get all transactions for this user, ordered by latest first
        $transactions = $this->userTransactionsQuery()
            ->with(['packet.product', 'packet.additionalDefaults.additional', 'additionals'])
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
            // Default to 'raw' (Foto Mentah) — the first category, where photos are picked for editing
            $type = 'raw';
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

        // 5. Photo selection for editing (retouch) — done on the RAW photos
        $retouchLimit = $selectedTransaksi?->retouch_limit ?? 0;
        $selectedPhotoIds = $selectedTransaksi
            ? $selectedTransaksi->selectedPhotos()->where('is_print', false)->pluck('file_url')
                ->map(fn ($url) => $this->extractFileId($url))
                ->filter()->values()->all()
            : [];
        $canSelect = $selectedTransaksi
            && $type === 'raw'
            && $selectedTransaksi->canCustomerEditSelection();

        return view('user.photo.index', [
            'transactions' => $transactions,
            'selectedTransaksi' => $selectedTransaksi,
            'type' => $type,
            'activeUrl' => $activeUrl,
            'hasBothTypes' => $hasBothTypes,
            'hasResult' => $hasResult,
            'hasRaw' => $hasRaw,
            'photosData' => $photosData,
            'retouchLimit' => $retouchLimit,
            'selectedPhotoIds' => $selectedPhotoIds,
            'canSelect' => $canSelect,
        ]);
    }

    /**
     * Select (action=select) or cancel (action=cancel) a single RAW photo for editing.
     * Every click is saved immediately, so the photo is submitted / withdrawn right away.
     */
    public function toggleEditSelection(Request $request, Transaksi $transaksi)
    {
        // Only the owner of the transaction may select photos
        $owned = $this->userTransactionsQuery()
            ->where('transaction_id', $transaksi->transaction_id)
            ->exists();
        if (!$owned) {
            return response()->json(['message' => 'Anda tidak memiliki izin untuk transaksi ini.'], 403);
        }

        if (!$transaksi->canCustomerEditSelection()) {
            return response()->json(['message' => $this->lockedMessage($transaksi)], 422);
        }

        $data = $request->validate([
            'photo_id' => 'required|string|max:100',
            'action' => 'required|in:select,cancel',
        ]);

        $transaksi->loadMissing(['packet.additionalDefaults.additional', 'additionals']);
        $limit = $transaksi->retouch_limit;
        $fileUrl = "https://drive.google.com/file/d/{$data['photo_id']}/view";

        // The photo must really exist in this transaction's RAW folder (also used for file names)
        $photosData = $this->googleDriveService->getPhotosFromUrl($transaksi->url_images);
        if (!$photosData['success']) {
            return response()->json(['message' => 'Gagal memverifikasi foto dari Google Drive. Silakan coba lagi.'], 422);
        }
        $filesById = collect($photosData['files'])->keyBy('id');

        if ($data['action'] === 'select' && !$filesById->has($data['photo_id'])) {
            return response()->json(['message' => 'Foto tidak ditemukan. Silakan refresh halaman.'], 422);
        }

        $error = null;

        DB::transaction(function () use ($transaksi, $data, $limit, $fileUrl, $filesById, &$error) {
            // Lock the transaction row so concurrent clicks cannot exceed the limit,
            // and re-check the lock state in case the admin approved/submitted meanwhile
            $fresh = Transaksi::whereKey($transaksi->getKey())->lockForUpdate()->first();
            if (!$fresh->canCustomerEditSelection()) {
                $error = $this->lockedMessage($fresh);
                return;
            }
            $editPhotos = $transaksi->selectedPhotos()->where('is_print', false);

            if ($data['action'] === 'select') {
                if ((clone $editPhotos)->where('file_url', $fileUrl)->exists()) {
                    return; // already selected
                }
                if ($limit > 0 && (clone $editPhotos)->count() >= $limit) {
                    $error = "Maksimal {$limit} foto yang dapat dipilih untuk diedit.";
                    return;
                }
                SelectedPhoto::create([
                    'transaction_id' => $transaksi->transaction_id,
                    'file_url' => $fileUrl,
                    'is_print' => false,
                ]);
            } else {
                (clone $editPhotos)->where('file_url', $fileUrl)->delete();
            }

            // Keep the admin's existing text-based selection view (select_edit_photo) in sync
            $names = (clone $editPhotos)->orderBy('id')->pluck('file_url')
                ->map(fn ($url) => $filesById[$this->extractFileId($url)]['name'] ?? $url)
                ->values();
            $transaksi->select_edit_photo = $names->isEmpty()
                ? null
                : $names->map(fn ($name, $i) => ($i + 1) . '. ' . $name)->implode("\n");

            if ($transaksi->process_status === 'Pelanggan Belum Foto') {
                $transaksi->process_status = 'Pelanggan Pilih Foto';
            }

            $transaksi->save();
        });

        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json([
            'selected' => $data['action'] === 'select',
            'count' => $transaksi->selectedPhotos()->where('is_print', false)->count(),
            'limit' => $limit,
            'message' => $data['action'] === 'select'
                ? 'Foto dipilih untuk diedit.'
                : 'Pilihan foto dibatalkan.',
        ]);
    }

    /**
     * Submit the current edit selection to the admin for review.
     */
    public function submitEditSelection(Transaksi $transaksi)
    {
        $this->authorizeOwner($transaksi);
        $redirect = $this->backToRaw($transaksi);

        if (!$transaksi->canCustomerEditSelection()) {
            return $redirect->with('error', $this->lockedMessage($transaksi));
        }

        $count = $transaksi->selectedPhotos()->where('is_print', false)->count();
        if ($count === 0) {
            return $redirect->with('error', 'Pilih minimal 1 foto sebelum mengajukan ke admin.');
        }

        $transaksi->loadMissing(['packet.additionalDefaults.additional', 'additionals']);
        $limit = $transaksi->retouch_limit;
        if ($limit > 0 && $count > $limit) {
            return $redirect->with('error', "Jumlah foto melebihi batas paket (maksimal {$limit} foto).");
        }

        $transaksi->edit_selection_status = Transaksi::EDIT_SELECTION_SUBMITTED;
        $transaksi->edit_selection_submitted_at = now();
        $transaksi->edit_selection_note = null;
        $transaksi->save();

        return $redirect->with('success', "{$count} foto berhasil diajukan ke admin. Mohon tunggu persetujuan admin.");
    }

    /**
     * Withdraw a submitted (not yet reviewed) selection so the customer can change it.
     */
    public function withdrawEditSelection(Transaksi $transaksi)
    {
        $this->authorizeOwner($transaksi);
        $redirect = $this->backToRaw($transaksi);

        if ($transaksi->edit_selection_status !== Transaksi::EDIT_SELECTION_SUBMITTED) {
            return $redirect->with('error', 'Pengajuan tidak dapat ditarik karena sudah diproses admin.');
        }

        $transaksi->edit_selection_status = Transaksi::EDIT_SELECTION_DRAFT;
        $transaksi->edit_selection_submitted_at = null;
        $transaksi->save();

        return $redirect->with('success', 'Pengajuan ditarik. Silakan ubah pilihan foto Anda lalu ajukan kembali.');
    }

    private function authorizeOwner(Transaksi $transaksi): void
    {
        $owned = $this->userTransactionsQuery()
            ->where('transaction_id', $transaksi->transaction_id)
            ->exists();
        abort_unless($owned, 403, 'AKSES DITOLAK: Anda tidak memiliki izin untuk transaksi ini.');
    }

    private function backToRaw(Transaksi $transaksi)
    {
        return redirect()->route('user.foto.index', [
            'transaksi_id' => $transaksi->transaction_id,
            'type' => 'raw',
        ]);
    }

    private function lockedMessage(Transaksi $transaksi): string
    {
        return match ($transaksi->edit_selection_status) {
            Transaksi::EDIT_SELECTION_SUBMITTED => 'Pilihan foto sedang direview admin. Tarik pengajuan terlebih dahulu jika ingin mengubahnya.',
            Transaksi::EDIT_SELECTION_APPROVED => 'Pilihan foto sudah disetujui admin dan tidak dapat diubah. Hubungi admin jika ingin mengubahnya.',
            default => 'Pilihan foto sudah dikunci karena foto Anda sedang/sudah diproses.',
        };
    }

    /**
     * Base query for the transactions belonging to the authenticated user.
     */
    private function userTransactionsQuery()
    {
        $user = auth()->user();

        return Transaksi::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('phone_number', $user->username);
        });
    }

    private function extractFileId(?string $url): ?string
    {
        return ($url && preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $url, $m)) ? $m[1] : null;
    }
}
