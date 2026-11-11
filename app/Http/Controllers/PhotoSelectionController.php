<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin review of the photos customers picked for editing (retouch).
 */
class PhotoSelectionController extends Controller
{
    public function __construct(protected GoogleDriveService $googleDriveService)
    {
    }

    private function authorizeStaff(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isAdmin() || $user->isKasir()), 403, 'AKSES DITOLAK: Halaman ini hanya untuk admin.');
    }

    /**
     * List of customer selections, filtered by review status.
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $status = $request->input('status', 'submitted');
        if (!in_array($status, ['submitted', 'approved', 'rejected', 'draft'], true)) {
            $status = 'submitted';
        }

        $base = Transaksi::query()->where(function ($q) {
            // Only transactions that have (or had) an edit selection
            $q->whereNotNull('edit_selection_status')
              ->orWhereHas('selectedPhotos', fn ($s) => $s->where('is_print', false));
        });

        $counts = [
            'submitted' => (clone $base)->where('edit_selection_status', 'submitted')->count(),
            'approved' => (clone $base)->where('edit_selection_status', 'approved')->count(),
            'rejected' => (clone $base)->where('edit_selection_status', 'rejected')->count(),
            'draft' => (clone $base)->whereNull('edit_selection_status')->count(),
        ];

        $query = (clone $base)
            ->with(['packet.product', 'packet.additionalDefaults.additional'])
            ->withCount(['selectedPhotos as edit_photos_count' => fn ($s) => $s->where('is_print', false)]);

        $query = $status === 'draft'
            ? $query->whereNull('edit_selection_status')->orderByDesc('updated_at')
            : $query->where('edit_selection_status', $status)
                ->orderBy($status === 'submitted' ? 'edit_selection_submitted_at' : 'edit_selection_reviewed_at', $status === 'submitted' ? 'asc' : 'desc');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('admin.photo-selection.index', compact('transactions', 'status', 'counts'));
    }

    /**
     * Show the photos a customer picked for editing.
     */
    public function show(Transaksi $transaksi, Request $request)
    {
        $this->authorizeStaff();

        $transaksi->load(['packet.product', 'packet.additionalDefaults.additional']);

        $selected = $transaksi->selectedPhotos()->where('is_print', false)->orderBy('id')->get();

        // Use the Drive listing for thumbnails & file names (falls back to plain Drive URLs)
        $driveFiles = collect();
        $driveError = null;
        if ($transaksi->url_images) {
            $photosData = $this->googleDriveService->getPhotosFromUrl($transaksi->url_images, $request->boolean('refresh'));
            if ($photosData['success']) {
                $driveFiles = collect($photosData['files'])->keyBy('id');
            } else {
                $driveError = $photosData['message'];
            }
        }

        $photos = $selected->map(function ($row) use ($driveFiles) {
            $id = preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $row->file_url, $m) ? $m[1] : null;
            $file = $id ? $driveFiles->get($id) : null;

            return [
                'id' => $id,
                'name' => $file['name'] ?? ($id ?? 'Foto'),
                'thumbnail_url' => $file['thumbnail_url'] ?? ($id ? "https://drive.google.com/thumbnail?id={$id}&sz=w500" : null),
                'fallback_thumbnail' => $id ? "https://drive.google.com/thumbnail?id={$id}&sz=w500" : null,
                'preview_url' => $file['preview_url'] ?? ($id ? "https://lh3.googleusercontent.com/d/{$id}=s1600" : null),
                'web_view_url' => $row->file_url,
                'download_url' => $file['download_url'] ?? ($id ? "https://drive.google.com/uc?export=download&id={$id}" : $row->file_url),
                'missing' => $id && $driveFiles->isNotEmpty() && !$file,
                'selected_at' => $row->created_at,
            ];
        });

        $retouchLimit = $transaksi->packet?->retouch_limit ?? 0;

        return view('admin.photo-selection.show', compact('transaksi', 'photos', 'retouchLimit', 'driveError'));
    }

    /**
     * Approve: the selection is locked and the customer can no longer change it.
     */
    public function approve(Transaksi $transaksi)
    {
        $this->authorizeStaff();

        if ($transaksi->edit_selection_status !== Transaksi::EDIT_SELECTION_SUBMITTED) {
            return back()->with('error', 'Hanya pengajuan yang berstatus "Menunggu Review" yang dapat disetujui.');
        }
        if (!$transaksi->selectedPhotos()->where('is_print', false)->exists()) {
            return back()->with('error', 'Tidak ada foto yang dipilih pelanggan.');
        }

        $transaksi->edit_selection_status = Transaksi::EDIT_SELECTION_APPROVED;
        $transaksi->edit_selection_reviewed_at = now();
        $transaksi->edit_selection_note = null;

        // Selection is final → move the order on to editing
        if (in_array($transaksi->process_status, Transaksi::EDIT_SELECTABLE_PROCESS_STATUSES, true)) {
            $transaksi->process_status = 'Proses Edit';
        }

        $transaksi->save();

        return back()->with('success', "Pilihan foto #{$transaksi->receipt_code} disetujui. Pelanggan tidak dapat mengubah pilihan lagi.");
    }

    /**
     * Reject: the selection is reset and the customer has to pick again.
     */
    public function reject(Request $request, Transaksi $transaksi)
    {
        $this->authorizeStaff();

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        if ($transaksi->edit_selection_status !== Transaksi::EDIT_SELECTION_SUBMITTED) {
            return back()->with('error', 'Hanya pengajuan yang berstatus "Menunggu Review" yang dapat ditolak.');
        }

        DB::transaction(function () use ($transaksi, $data) {
            $transaksi->selectedPhotos()->where('is_print', false)->delete();

            $transaksi->select_edit_photo = null;
            $transaksi->edit_selection_status = Transaksi::EDIT_SELECTION_REJECTED;
            $transaksi->edit_selection_note = $data['note'] ?? null;
            $transaksi->edit_selection_reviewed_at = now();
            $transaksi->edit_selection_submitted_at = null;

            if (!in_array($transaksi->process_status, Transaksi::EDIT_SELECTABLE_PROCESS_STATUSES, true)) {
                $transaksi->process_status = 'Pelanggan Pilih Foto';
            }

            $transaksi->save();
        });

        return back()->with('success', "Pilihan foto #{$transaksi->receipt_code} ditolak dan direset. Pelanggan dapat memilih foto kembali.");
    }

    /**
     * Re-open an approved selection so the customer can change the chosen photos (selection is kept).
     */
    public function reopen(Transaksi $transaksi)
    {
        $this->authorizeStaff();

        if ($transaksi->edit_selection_status !== Transaksi::EDIT_SELECTION_APPROVED) {
            return back()->with('error', 'Hanya pilihan yang sudah disetujui yang dapat dibuka kembali.');
        }

        $transaksi->edit_selection_status = Transaksi::EDIT_SELECTION_DRAFT;
        $transaksi->edit_selection_reviewed_at = null;
        $transaksi->edit_selection_submitted_at = null;
        $transaksi->edit_selection_note = null;

        // The customer can only pick photos while the order is in a "choosing" process status
        if (!in_array($transaksi->process_status, Transaksi::EDIT_SELECTABLE_PROCESS_STATUSES, true)) {
            $transaksi->process_status = 'Pelanggan Pilih Foto';
        }

        $transaksi->save();

        return back()->with('success', "Pemilihan foto #{$transaksi->receipt_code} dibuka kembali. Pelanggan dapat mengubah pilihan lalu mengajukan ulang.");
    }
}
