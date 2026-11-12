@extends('layouts.master')
@section('title', 'Galeri Foto')

@section('css')
<style>
    .photo-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        background-color: #fff;
        overflow: hidden;
    }
    .photo-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        border-color: #5b73e8;
    }
    .image-container {
        position: relative;
        width: 100%;
        padding-top: 75%; /* 4:3 Aspect Ratio */
        background-color: #f1f3f7;
        overflow: hidden;
    }
    .image-container img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.3s ease, opacity 0.3s ease;
        opacity: 0;
    }
    .image-container img.loaded {
        opacity: 1;
    }
    .image-container .spinner-placeholder {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #adb5bd;
    }
    .photo-card:hover .image-container img {
        transform: scale(1.04);
    }
    .photo-card.is-selected {
        border-color: #5b73e8;
        box-shadow: 0 0 0 3px rgba(91, 115, 232, 0.35);
    }
    .selected-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 2;
        font-size: 11px;
        padding: 5px 8px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    }
    /* Sorot tombol WA admin tepat setelah pengajuan */
    .wa-admin-highlight {
        animation: waPulse 1.4s ease-in-out 4;
    }
    @keyframes waPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(52, 195, 143, 0.6); }
        50% { box-shadow: 0 0 0 8px rgba(52, 195, 143, 0); }
    }
    .btn-select-edit, .btn-cancel-edit {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Penghitung melayang untuk layar kecil / PWA */
    .selection-float {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        z-index: 1030;
        background: #5b73e8;
        color: #fff;
        border-radius: 999px;
        padding: 8px 18px;
        font-size: 13px;
        box-shadow: 0 6px 18px rgba(91, 115, 232, 0.45);
        white-space: nowrap;
    }
    @media (max-width: 767.98px) {
        /* Ruang di bawah grid agar kartu terakhir tidak tertutup penghitung melayang */
        .photo-grid { padding-bottom: 64px; }
        .photo-card .card-body { padding: 0.5rem !important; }
        .photo-card h6 { font-size: 12px !important; }
        .selected-badge { font-size: 10px; padding: 4px 6px; top: 6px; left: 6px; }
    }
    @media (max-width: 399.98px) {
        /* Layar sangat sempit: tombol Lihat/Unduh cukup ikon */
        .photo-card .btn-label { display: none; }
    }
    .modal-preview-img {
        max-height: 75vh;
        width: auto;
        max-width: 100%;
        display: block;
        margin: 0 auto;
        border-radius: 0.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Galeri Foto',
        'pagetitle' => 'Yunas Studio',
        'breadcrumbs' => [
            ['text' => 'Dashboard', 'url' => route('transaksi.index')],
            ['text' => 'Foto', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="row">
        <div class="col-12">
            {{-- ALERT NOTIFIKASI --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-all me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-block-helper me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-block-helper me-2"></i>{{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- JIKA USER BELUM PUNYA TRANSAKSI SAMA SEKALI --}}
            @if($transactions->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24">
                                <i class="uil-camera-slash"></i>
                            </span>
                        </div>
                        <h4 class="mb-2">Belum Ada Transaksi</h4>
                        <p class="text-muted mb-4">Anda belum memiliki riwayat transaksi atau sesi foto di Yunas Studio.</p>
                        <a href="{{ route('userPage.home') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="uil-plus me-1"></i> Booking Sesi Foto Sekarang
                        </a>
                    </div>
                </div>
            @else
                {{-- HEADER FILTER & CONTROLS --}}
                <div class="card mb-3">
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('user.foto.index') }}" id="filterForm">
                            <input type="hidden" name="type" value="{{ $type }}" id="photoTypeInput">
                            
                            <div class="row g-2 align-items-center">
                                {{-- DROPDOWN FILTER TRANSAKSI --}}
                                <div class="col-12 col-md-5">
                                    <label class="form-label font-size-12 fw-semibold text-muted mb-1">
                                        <i class="uil-invoice me-1"></i>PILIH TRANSAKSI
                                    </label>
                                    <select name="transaksi_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit();">
                                        @foreach($transactions as $index => $trx)
                                            <option value="{{ $trx->transaction_id }}" {{ $selectedTransaksi && $selectedTransaksi->transaction_id == $trx->transaction_id ? 'selected' : '' }}>
                                                #{{ $trx->receipt_code }} — {{ $trx->packet->name ?? 'Paket' }}
                                                ({{ $trx->session_date ? \Carbon\Carbon::parse($trx->session_date)->format('d M Y') : $trx->created_at->format('d M Y') }})
                                                @if($index === 0) [Terbaru] @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- TOGGLE TIPE FOTO (1. FOTO MENTAH, 2. HASIL FOTO EDIT) --}}
                                <div class="col-12 col-md-4">
                                    <label class="form-label font-size-12 fw-semibold text-muted mb-1">
                                        <i class="uil-images me-1"></i>KATEGORI FOTO
                                    </label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button"
                                                class="btn {{ $type === 'raw' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                onclick="switchType('raw')"
                                                title="Semua foto yang belum diedit — pilih foto yang ingin diedit di sini">
                                            <i class="uil-camera me-1"></i>Foto Mentah
                                        </button>
                                        <button type="button"
                                                class="btn {{ $type === 'result' ? 'btn-primary' : 'btn-outline-primary' }}"
                                                onclick="switchType('result')"
                                                title="Foto yang sudah selesai diedit">
                                            <i class="uil-check-circle me-1"></i>Hasil Foto Edit
                                        </button>
                                    </div>
                                </div>

                                {{-- TOMBOL REFRESH & GOOGLE DRIVE --}}
                                <div class="col-12 col-md-3 text-md-end mt-2 mt-md-4">
                                    @if(!empty($activeUrl))
                                        <a href="{{ route('user.foto.index', ['transaksi_id' => $selectedTransaksi->transaction_id, 'type' => $type, 'refresh' => 1]) }}" 
                                           class="btn btn-sm btn-outline-secondary me-1" 
                                           title="Refresh dan muat ulang foto dari Google Drive">
                                            <i class="uil-refresh me-1"></i>Refresh
                                        </a>
                                        <a href="{{ $activeUrl }}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-success" 
                                           title="Buka folder langsung di Google Drive">
                                            <i class="mdi mdi-google-drive me-1"></i>Buka Drive
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- DETAIL INFO TRANSAKSI TERPILIH --}}
                    @if($selectedTransaksi)
                        <div class="card-footer bg-light py-2 px-3 border-top">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 font-size-12">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge bg-primary">{{ $selectedTransaksi->packet->name ?? 'Paket' }}</span>
                                    @if($selectedTransaksi->packet && $selectedTransaksi->packet->product)
                                        <span class="text-muted"><i class="uil-store me-1"></i>{{ $selectedTransaksi->packet->product->name }}</span>
                                    @endif
                                    <span class="text-muted"><i class="uil-calendar-alt me-1"></i>{{ $selectedTransaksi->session_date ? \Carbon\Carbon::parse($selectedTransaksi->session_date)->format('d F Y') : '-' }}</span>
                                    <span class="text-muted"><i class="uil-clock me-1"></i>{{ $selectedTransaksi->session_time ?? '-' }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $selectedTransaksi->status == 'sudah dibayar' ? 'bg-success' : ($selectedTransaksi->status == 'dp' ? 'bg-info' : 'bg-warning') }}">
                                        {{ ucwords($selectedTransaksi->status) }}
                                    </span>
                                    <span class="badge bg-secondary">{{ $selectedTransaksi->process_status }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- INFO: AJAKAN MEMILIH FOTO UNTUK DIEDIT (SAAT SEDANG DI TAB HASIL) --}}
                @if($type !== 'raw' && $hasRaw && $selectedTransaksi->canCustomerEditSelection())
                    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                        <span class="font-size-13">
                            <i class="uil-edit me-1"></i>Anda dapat memilih
                            <strong>{{ $retouchLimit > 0 ? 'maksimal ' . $retouchLimit : '' }} foto</strong> untuk diedit di kategori Foto Mentah.
                        </span>
                        <button type="button" class="btn btn-sm btn-primary" onclick="switchType('raw')">
                            <i class="uil-check-square me-1"></i>Pilih Foto untuk Diedit
                        </button>
                    </div>
                @endif

                {{-- KONTEN FOTO DARI GOOGLE DRIVE --}}
                @if(empty($activeUrl))
                    {{-- STATE: BELUM ADA LINK GDRIVE --}}
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-24">
                                    <i class="mdi mdi-google-drive"></i>
                                </span>
                            </div>
                            @if($type === 'result')
                                <h5 class="mb-2">Hasil Foto Edit Belum Tersedia</h5>
                                <p class="text-muted mb-3" style="max-width: 550px; margin: 0 auto;">
                                    Foto pilihan Anda untuk transaksi <strong>#{{ $selectedTransaksi->receipt_code }}</strong> belum selesai diedit.
                                    Hasilnya akan tampil di sini setelah proses editing selesai.
                                </p>
                            @else
                                <h5 class="mb-2">Foto Mentah Belum Tersedia</h5>
                                <p class="text-muted mb-3" style="max-width: 550px; margin: 0 auto;">
                                    Foto mentah untuk transaksi <strong>#{{ $selectedTransaksi->receipt_code }}</strong> belum diunggah oleh admin studio.
                                </p>
                            @endif
                            @if(($type === 'result' && $hasRaw) || ($type === 'raw' && $hasResult))
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="switchType('{{ $type === 'result' ? 'raw' : 'result' }}')">
                                    <i class="uil-exchange me-1"></i>Lihat {{ $type === 'result' ? 'Foto Mentah' : 'Hasil Foto Edit' }}
                                </button>
                            @endif
                        </div>
                    </div>

                @elseif(!$photosData['success'])
                    {{-- STATE: ERROR FETCH GDRIVE --}}
                    <div class="card border-warning">
                        <div class="card-body py-4">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar-md flex-shrink-0">
                                    <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-22">
                                        <i class="uil-exclamation-triangle"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title text-warning mb-1">Informasi Google Drive API</h5>
                                    <p class="text-muted mb-3">{{ $photosData['message'] ?? 'Tidak dapat memuat foto dari Google Drive.' }}</p>
                                    
                                    @if(($photosData['error'] ?? '') === 'API_KEY_NOT_CONFIGURED')
                                        <div class="alert alert-info py-2 font-size-13 mb-3">
                                            <strong>Petunjuk Admin:</strong> Masukkan API Key Google Drive Anda pada file <code>.env</code> dengan variabel:
                                            <code>GOOGLE_DRIVE_API_KEY=AIzaSy...</code>
                                        </div>
                                    @endif

                                    <div class="d-flex gap-2">
                                        <a href="{{ $activeUrl }}" target="_blank" class="btn btn-success btn-sm">
                                            <i class="mdi mdi-google-drive me-1"></i>Buka Folder di Google Drive Langsung
                                        </a>
                                        <a href="{{ route('user.foto.index', ['transaksi_id' => $selectedTransaksi->transaction_id, 'type' => $type, 'refresh' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="uil-refresh me-1"></i>Coba Lagi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @elseif($photosData['count'] === 0)
                    {{-- STATE: FOLDER KOSONG --}}
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title rounded-circle bg-soft-info text-info font-size-24">
                                    <i class="uil-images"></i>
                                </span>
                            </div>
                            <h5 class="mb-2">Tidak Ada Foto Ditemukan</h5>
                            <p class="text-muted mb-3">Folder Google Drive terhubung namun belum ada file gambar di dalamnya.</p>
                            <a href="{{ $activeUrl }}" target="_blank" class="btn btn-outline-success btn-sm me-2">
                                <i class="mdi mdi-google-drive me-1"></i>Buka Google Drive
                            </a>
                            <a href="{{ route('user.foto.index', ['transaksi_id' => $selectedTransaksi->transaction_id, 'type' => $type, 'refresh' => 1]) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="uil-refresh me-1"></i>Refresh
                            </a>
                        </div>
                    </div>

                @else
                    {{-- INFO JUMLAH FOTO --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-2">
                        <span class="text-muted font-size-13">
                            <i class="uil-check-circle text-success me-1"></i>Menampilkan <strong>{{ $photosData['count'] }} foto</strong>
                        </span>
                        <a href="{{ $activeUrl }}" target="_blank" class="text-muted small text-decoration-none">
                            <i class="mdi mdi-open-in-new me-1"></i>Lihat Semua di Google Drive
                        </a>
                    </div>

                    {{-- PANEL PEMILIHAN FOTO UNTUK DIEDIT (HANYA DI FOTO MENTAH) --}}
                    @if($type === 'raw')
                        @php $selStatus = $selectedTransaksi->edit_selection_status; @endphp

                        {{-- ALASAN PENOLAKAN DARI ADMIN --}}
                        @if($selStatus === 'rejected' && $canSelect)
                            <div class="alert alert-danger py-2 font-size-13">
                                <i class="uil-times-circle me-1"></i><strong>Pilihan foto sebelumnya ditolak admin</strong> dan telah direset. Silakan pilih foto kembali.
                                @if($selectedTransaksi->edit_selection_note)
                                    <div class="mt-1"><strong>Alasan:</strong> {{ $selectedTransaksi->edit_selection_note }}</div>
                                @endif
                            </div>
                        @endif

                        @if($canSelect)
                            <div class="card selection-info border-primary mb-3">
                                <div class="card-body py-2 px-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                    <div class="font-size-13">
                                        <i class="uil-edit text-primary me-1"></i>
                                        Tekan tombol <strong>Pilih untuk Diedit</strong> pada foto yang ingin diedit
                                        @if($retouchLimit > 0)
                                            <span class="d-block d-sm-inline">
                                                (maksimal <strong>{{ $retouchLimit }} foto</strong>
                                                @if(($selectedTransaksi->extra_edit_photos_count ?? 0) > 0)
                                                    &mdash; {{ $selectedTransaksi->packet?->retouch_limit ?? 0 }} paket + {{ $selectedTransaksi->extra_edit_photos_count }} tambahan
                                                @elseif($selectedTransaksi->packet?->name)
                                                    sesuai paket {{ $selectedTransaksi->packet->name }}
                                                @endif)
                                            </span>
                                        @endif
                                        <div class="text-muted font-size-12 mt-1">Setelah selesai memilih, tekan <strong>Ajukan ke Admin</strong> untuk diproses.</div>
                                    </div>
                                    <div class="flex-shrink-0 d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                                        <div>
                                            <span class="badge bg-soft-primary text-primary font-size-13 px-3 py-2">
                                                Terpilih: <strong class="selected-count">{{ count($selectedPhotoIds) }}</strong>{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }} foto
                                            </span>
                                            <div class="limit-warning text-danger font-size-12 mt-1 {{ $retouchLimit > 0 && count($selectedPhotoIds) >= $retouchLimit ? '' : 'd-none' }}">
                                                Batas maksimal tercapai
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('user.foto.submit-edit', $selectedTransaksi->transaction_id) }}"
                                              onsubmit="return confirm('Ajukan ' + currentSelectedCount() + ' foto ke admin? Selama direview, pilihan tidak dapat diubah.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm w-100 btn-submit-selection" {{ count($selectedPhotoIds) === 0 ? 'disabled' : '' }}>
                                                <i class="uil-message me-1"></i>Ajukan ke Admin
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- PENGHITUNG MELAYANG (HANYA LAYAR KECIL / PWA) --}}
                            <div class="selection-float d-md-none">
                                <i class="uil-edit me-1"></i>Terpilih <strong class="selected-count">{{ count($selectedPhotoIds) }}</strong>{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }}
                            </div>
                        @elseif($selStatus === 'submitted')
                            <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-2 font-size-13">
                                <div>
                                    <i class="uil-clock me-1"></i><strong>Menunggu persetujuan admin.</strong>
                                    {{ count($selectedPhotoIds) }}{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }} foto telah diajukan
                                    @if($selectedTransaksi->edit_selection_submitted_at)
                                        pada {{ $selectedTransaksi->edit_selection_submitted_at->format('d M Y H:i') }}
                                    @endif.
                                    <div class="font-size-12">
                                        @if($waAdminLink)
                                            Kirim daftar foto ke WhatsApp admin agar segera diproses.
                                        @endif
                                        Ingin mengubah pilihan? Tarik pengajuan terlebih dahulu.
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    @if($waAdminLink)
                                        <a href="{{ $waAdminLink }}" target="_blank" rel="noopener"
                                           class="btn btn-success btn-sm text-nowrap {{ session('just_submitted') ? 'wa-admin-highlight' : '' }}">
                                            <i class="mdi mdi-whatsapp me-1"></i>Kirim ke WhatsApp Admin
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('user.foto.withdraw-edit', $selectedTransaksi->transaction_id) }}"
                                          onsubmit="return confirm('Tarik pengajuan untuk mengubah pilihan foto?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-dark btn-sm text-nowrap w-100">
                                            <i class="uil-redo me-1"></i>Tarik Pengajuan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @elseif($selStatus === 'approved')
                            <div class="alert alert-success d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-2 font-size-13">
                                <div>
                                    <i class="uil-check-circle me-1"></i><strong>Pilihan foto Anda telah disetujui admin.</strong>
                                    {{ count($selectedPhotoIds) }}{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }} foto akan diedit.
                                    Hubungi admin studio jika ingin mengubah pilihan.
                                </div>
                                @if($waAdminLink)
                                    <a href="{{ $waAdminLink }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm text-nowrap">
                                        <i class="mdi mdi-whatsapp me-1"></i>Hubungi Admin
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-secondary py-2 font-size-13">
                                <i class="uil-lock me-1"></i>Pemilihan foto untuk diedit sudah ditutup karena status transaksi ini
                                <strong>{{ $selectedTransaksi->process_status }}</strong>.
                                @if(count($selectedPhotoIds) > 0)
                                    Foto terpilih: <strong>{{ count($selectedPhotoIds) }}</strong>{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }} foto.
                                @endif
                                Hubungi admin studio jika ingin mengubah pilihan foto.
                            </div>
                        @endif
                    @endif

                    {{-- GRID FOTO BERHASIL DI-FETCH --}}
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-2 g-md-3 photo-grid">
                        @foreach($photosData['files'] as $index => $file)
                            @php $isSelected = $type === 'raw' && in_array($file['id'], $selectedPhotoIds, true); @endphp
                            <div class="col">
                                <div class="card photo-card h-100 position-relative {{ $isSelected ? 'is-selected' : '' }}" data-photo-id="{{ $file['id'] }}">
                                    @if($type === 'raw')
                                        <span class="badge bg-primary selected-badge {{ $isSelected ? '' : 'd-none' }}">
                                            <i class="uil-check-circle me-1"></i>Dipilih untuk diedit
                                        </span>
                                    @endif
                                    {{-- CONTAINER GAMBAR --}}
                                    <div class="image-container cursor-pointer" onclick="openPreviewModal('{{ $file['preview_url'] }}', '{{ addslashes($file['name']) }}', '{{ $file['download_url'] }}', '{{ $file['web_view_url'] }}')">
                                        <div class="spinner-placeholder">
                                            <div class="spinner-border spinner-border-sm" role="status"></div>
                                        </div>
                                        <img src="{{ $file['thumbnail_url'] }}" 
                                             alt="{{ $file['name'] }}" 
                                             loading="lazy"
                                             referrerpolicy="no-referrer"
                                             onload="this.classList.add('loaded'); this.previousElementSibling.style.display='none';"
                                             onerror="handleImgError(this, '{{ $file['fallback_thumbnail'] ?? '' }}');">
                                    </div>

                                    {{-- CARD BODY / DETAIL --}}
                                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                                        <div class="mb-2">
                                            <h6 class="font-size-13 text-truncate mb-1" title="{{ $file['name'] }}">
                                                {{ $file['name'] }}
                                            </h6>
                                            <span class="text-muted font-size-11">
                                                <i class="uil-file-alt me-1"></i>{{ $file['size_formatted'] }}
                                            </span>
                                        </div>

                                        {{-- TOMBOL AKSI --}}
                                        <div class="d-flex gap-1">
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm w-100 py-1 font-size-12"
                                                    title="Lihat foto"
                                                    onclick="openPreviewModal('{{ $file['preview_url'] }}', '{{ addslashes($file['name']) }}', '{{ $file['download_url'] }}', '{{ $file['web_view_url'] }}')">
                                                <i class="uil-eye"></i><span class="btn-label ms-1">Lihat</span>
                                            </button>
                                            <a href="{{ $file['download_url'] }}"
                                               class="btn btn-outline-success btn-sm w-100 py-1 font-size-12"
                                               title="Unduh resolusi penuh"
                                               download>
                                                <i class="uil-download-alt"></i><span class="btn-label ms-1">Unduh</span>
                                            </a>
                                        </div>

                                        {{-- TOMBOL PILIH / BATAL UNTUK DIEDIT --}}
                                        @if($type === 'raw' && $canSelect)
                                            <button type="button"
                                                    class="btn btn-primary btn-sm w-100 py-1 font-size-12 mt-1 btn-select-edit {{ $isSelected ? 'd-none' : '' }}"
                                                    onclick="toggleEditPhoto(this, '{{ $file['id'] }}', 'select')">
                                                <i class="uil-edit me-1"></i>Pilih untuk Diedit
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm w-100 py-1 font-size-12 mt-1 btn-cancel-edit {{ $isSelected ? '' : 'd-none' }}"
                                                    onclick="toggleEditPhoto(this, '{{ $file['id'] }}', 'cancel')">
                                                <i class="uil-times me-1"></i>Batal Pilih
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- MODAL PREVIEW FOTO --}}
    <div class="modal fade" id="photoPreviewModal" tabindex="-1" aria-labelledby="photoPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h6 class="modal-title font-size-14 text-truncate" id="photoPreviewModalLabel">Preview Foto</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-3 bg-light position-relative">
                    <div id="modalLoadingSpinner" class="py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <img id="modalPreviewImage" 
                         src="" 
                         alt="" 
                         referrerpolicy="no-referrer"
                         class="modal-preview-img d-none"
                         onload="document.getElementById('modalLoadingSpinner').classList.add('d-none'); this.classList.remove('d-none');">
                </div>
                <div class="modal-footer py-2 px-3 justify-content-between">
                    <a id="modalDriveBtn" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="mdi mdi-google-drive me-1"></i>Buka di Drive
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Tutup</button>
                        <a id="modalDownloadBtn" href="#" class="btn btn-sm btn-success" download>
                            <i class="uil-download-alt me-1"></i>Unduh Resolusi Asli
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    function handleImgError(img, fallbackUrl) {
        if (fallbackUrl && img.dataset.triedFallback !== '1') {
            img.dataset.triedFallback = '1';
            img.src = fallbackUrl;
            return;
        }
        // Jika tetap gagal, tampilkan placeholder icon bersih, BUKAN foto kuda demo
        img.style.display = 'none';
        const container = img.parentElement;
        if (container) {
            const spinner = container.querySelector('.spinner-placeholder');
            if (spinner) {
                spinner.innerHTML = '<div class="text-center text-muted"><i class="uil-image-alt font-size-24"></i><div class="font-size-11 mt-1">Preview Foto</div></div>';
                spinner.style.display = 'block';
            }
        }
    }

    // --- PEMILIHAN FOTO UNTUK DIEDIT ---
    const RETOUCH_LIMIT = {{ (int) ($retouchLimit ?? 0) }};

    const SELECT_EDIT_URL = @json($selectedTransaksi ? route('user.foto.select-edit', $selectedTransaksi->transaction_id) : null);
    const CSRF_TOKEN = @json(csrf_token());

    // Nonaktifkan semua tombol "Pilih" ketika batas maksimal tercapai
    function refreshSelectButtons(count) {
        const limitReached = RETOUCH_LIMIT > 0 && count >= RETOUCH_LIMIT;
        document.querySelectorAll('.selected-count').forEach(el => el.innerText = count);
        document.querySelectorAll('.limit-warning').forEach(el => el.classList.toggle('d-none', !limitReached));
        document.querySelectorAll('.btn-submit-selection').forEach(btn => btn.disabled = count === 0);
        document.querySelectorAll('.btn-select-edit').forEach(btn => {
            btn.disabled = limitReached;
            btn.title = limitReached ? 'Batas maksimal foto edit tercapai' : '';
        });
    }

    async function toggleEditPhoto(btn, photoId, action) {
        const card = btn.closest('.photo-card');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

        try {
            const res = await fetch(SELECT_EDIT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({ photo_id: photoId, action: action }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                alert(data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                return;
            }

            const selected = data.selected;
            card.classList.toggle('is-selected', selected);
            card.querySelector('.selected-badge').classList.toggle('d-none', !selected);
            card.querySelector('.btn-select-edit').classList.toggle('d-none', selected);
            card.querySelector('.btn-cancel-edit').classList.toggle('d-none', !selected);
            refreshSelectButtons(data.count);
        } catch (e) {
            alert('Gagal terhubung ke server. Periksa koneksi Anda lalu coba lagi.');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            refreshSelectButtons(currentSelectedCount());
        }
    }

    function currentSelectedCount() {
        const el = document.querySelector('.selected-count');
        return el ? (parseInt(el.innerText, 10) || 0) : 0;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('.selected-count')) refreshSelectButtons(currentSelectedCount());
    });

    function switchType(type) {
        document.getElementById('photoTypeInput').value = type;
        document.getElementById('filterForm').submit();
    }

    function openPreviewModal(previewUrl, fileName, downloadUrl, webViewUrl) {
        const modalElement = document.getElementById('photoPreviewModal');
        const modal = new bootstrap.Modal(modalElement);
        
        document.getElementById('photoPreviewModalLabel').innerText = fileName;
        
        const modalImg = document.getElementById('modalPreviewImage');
        const spinner = document.getElementById('modalLoadingSpinner');
        
        modalImg.classList.add('d-none');
        spinner.classList.remove('d-none');
        modalImg.src = previewUrl;
        modalImg.alt = fileName;
        
        document.getElementById('modalDownloadBtn').href = downloadUrl;
        document.getElementById('modalDriveBtn').href = webViewUrl;
        
        modal.show();
    }
</script>
@endsection

