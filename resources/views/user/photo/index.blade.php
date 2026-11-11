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

                                {{-- TOGGLE TIPE FOTO (HASIL EDIT vs RAW) --}}
                                <div class="col-12 col-md-4">
                                    <label class="form-label font-size-12 fw-semibold text-muted mb-1">
                                        <i class="uil-images me-1"></i>KATEGORI FOTO
                                    </label>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        <button type="button" 
                                                class="btn {{ $type === 'result' ? 'btn-primary' : 'btn-outline-primary' }} {{ empty($selectedTransaksi?->url_photos_result) ? 'disabled' : '' }}"
                                                onclick="switchType('result')"
                                                title="{{ empty($selectedTransaksi?->url_photos_result) ? 'Link Hasil Foto belum tersedia' : 'Lihat Hasil Foto Edit' }}">
                                            <i class="uil-check-circle me-1"></i>Hasil Foto (Final)
                                        </button>
                                        <button type="button" 
                                                class="btn {{ $type === 'raw' ? 'btn-primary' : 'btn-outline-primary' }} {{ empty($selectedTransaksi?->url_images) ? 'disabled' : '' }}"
                                                onclick="switchType('raw')"
                                                title="{{ empty($selectedTransaksi?->url_images) ? 'Link Foto Mentah belum tersedia' : 'Lihat Foto Mentah / RAW' }}">
                                            <i class="uil-camera me-1"></i>Foto Mentah (RAW)
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
                            <h5 class="mb-2">Link Foto Belum Tersedia</h5>
                            <p class="text-muted mb-3" style="max-width: 550px; margin: 0 auto;">
                                Foto untuk kategori <strong>{{ $type === 'result' ? 'Hasil Foto (Final)' : 'Foto Mentah (RAW)' }}</strong> 
                                pada transaksi <strong>#{{ $selectedTransaksi->receipt_code }}</strong> belum diunggah atau belum disematkan oleh admin studio.
                            </p>
                            @if($hasBothTypes || ($type === 'result' && $hasRaw) || ($type === 'raw' && $hasResult))
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="switchType('{{ $type === 'result' ? 'raw' : 'result' }}')">
                                    <i class="uil-exchange me-1"></i>Lihat {{ $type === 'result' ? 'Foto Mentah (RAW)' : 'Hasil Foto (Final)' }}
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
                    {{-- GRID FOTO BERHASIL DI-FETCH --}}
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted font-size-13">
                            <i class="uil-check-circle text-success me-1"></i>Menampilkan <strong>{{ $photosData['count'] }} foto</strong>
                        </span>
                        <a href="{{ $activeUrl }}" target="_blank" class="text-muted small text-decoration-none">
                            <i class="mdi mdi-open-in-new me-1"></i>Lihat Semua di Google Drive
                        </a>
                    </div>

                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                        @foreach($photosData['files'] as $index => $file)
                            <div class="col">
                                <div class="card photo-card h-100">
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
                                                    onclick="openPreviewModal('{{ $file['preview_url'] }}', '{{ addslashes($file['name']) }}', '{{ $file['download_url'] }}', '{{ $file['web_view_url'] }}')">
                                                <i class="uil-eye me-1"></i>Lihat
                                            </button>
                                            <a href="{{ $file['download_url'] }}" 
                                               class="btn btn-outline-success btn-sm w-100 py-1 font-size-12"
                                               title="Unduh resolusi penuh"
                                               download>
                                                <i class="uil-download-alt me-1"></i>Unduh
                                            </a>
                                        </div>
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

