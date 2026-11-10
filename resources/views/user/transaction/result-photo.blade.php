@extends('layouts.master')
@section('title', 'Photo Gallery')

@section('css')
    <style>
        .photo-card {
            border: 1px solid rgba(0,0,0,0.125);
            border-radius: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            background-color: #fff;
        }

        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #b4c6fc;
        }

        .image-container {
            background-color: #f8f9fa;
            overflow: hidden;
            position: relative;
            border-top-left-radius: calc(0.5rem - 1px);
            border-top-right-radius: calc(0.5rem - 1px);
        }
        
        /* Placeholder loading animation */
        .image-container::before {
            content: "";
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 50%, #f8f9fa 100%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            z-index: 1;
        }
        .image-container.loaded::before {
            display: none;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .fixed-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
            position: relative;
            z-index: 2;
            opacity: 0;
        }
        .fixed-image.show {
            opacity: 1;
        }

        .photo-card:hover .fixed-image {
            transform: scale(1.05);
        }
        
        /* Disabled Link Style */
        a.disabled-link {
            pointer-events: none;
            cursor: default;
            color: #ccc !important;
            border-color: #eee !important;
            background-color: #f8f9fa;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Photo Gallery',
        'pagetitle' => 'Gallery',
        'breadcrumbs' => [
            ['text' => 'My Transactions', 'url' => route('transaksi.index')],
            ['text' => 'View Photos', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="container-fluid bg-white py-4">
        <div class="container">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- HEADER INFORMATION --}}
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
                <div style="flex: 1; min-width: 300px;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <a href="{{ route('transaksi.index') }}" class="btn btn-secondary btn-sm"><i class="bx bx-arrow-back"></i> Back</a>
                        <h2 class="mb-0 fs-4">Photos for #{{ $transaksi->receipt_code }}</h2>
                    </div>
                    
                    {{-- INFORMASI PAKET --}}
                    @if($transaksi->packet)
                        <div class="mt-3 ms-1">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="badge bg-primary font-size-12">{{ $transaksi->packet->name }}</span>
                                @if($transaksi->packet->product)
                                    <span class="text-muted small"><i class="bx bx-category me-1"></i>{{ $transaksi->packet->product->name }}</span>
                                @endif
                                
                                {{-- Toggle Detail Paket --}}
                                <a class="text-primary small text-decoration-none cursor-pointer ms-2" data-bs-toggle="collapse" href="#packetDetails" role="button" aria-expanded="false">
                                    <i class="bx bx-info-circle me-1"></i>View Package Details
                                </a>
                            </div>

                            {{-- TABEL DETAIL PAKET (COLLAPSIBLE) --}}
                            <div class="collapse mt-2" id="packetDetails">
                                <div class="card border shadow-none mb-0 bg-light" style="max-width: 600px;">
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-striped mb-0 font-size-13">
                                            <tbody>
                                                {{-- Included Prints --}}
                                                @if($transaksi->packet->printOptions->isNotEmpty())
                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small bg-soft-light">Included Prints</td></tr>
                                                    @foreach($transaksi->packet->printOptions as $print)
                                                        <tr>
                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-printer me-2 text-secondary"></i> Cetak {{ $print->name }}</td>
                                                            <td class="text-center py-1 fw-bold" width="50">x{{ $print->pivot->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                {{-- Additional Defaults --}}
                                                @if($transaksi->packet->additionalDefaults->isNotEmpty())
                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small bg-soft-light">Included Items</td></tr>
                                                    @foreach($transaksi->packet->additionalDefaults as $default)
                                                        <tr>
                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-check-circle me-2 text-secondary"></i> {{ $default->additional->name }}</td>
                                                            <td class="text-center py-1 fw-bold" width="50">x{{ $default->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif

                                                {{-- Paid Additionals --}}
                                                @if($transaksi->additionals->isNotEmpty())
                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-warning small bg-soft-warning">Extra Add-ons</td></tr>
                                                    @foreach($transaksi->additionals as $additional)
                                                        <tr>
                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-plus me-2 text-warning"></i> {{ $additional->name }}</td>
                                                            <td class="text-center py-1 fw-bold" width="50">x{{ $additional->pivot->quantity }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                                
                                                @if($transaksi->packet->printOptions->isEmpty() && $transaksi->packet->additionalDefaults->isEmpty() && $transaksi->additionals->isEmpty())
                                                    <tr><td colspan="2" class="text-center text-muted fst-italic py-2">No specific details available for this package.</td></tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                    <span class="badge bg-soft-primary text-primary border border-primary p-2 rounded-pill me-2">{{ $photoCount }} Photos</span>
                    
                    @php
                        // Cek Status Pembayaran Global untuk Download All
                        $canDownloadAll = (auth()->user()->isAdmin() || $transaksi->status == 'sudah dibayar');
                    @endphp

                    @if($photoCount > 0)
                        @if($canDownloadAll)
                            <a href="{{ route('transaksi.downloadFolder', ['transaksi' => $transaksi, 'status' => $currentFilter]) }}" class="btn btn-success">
                                <i class="bx bx-download me-1"></i> Download All (.ZIP)
                            </a>
                        @else
                            <button class="btn btn-secondary" disabled title="Please complete payment to download all photos">
                                <i class="bx bx-lock-alt me-1"></i> Download All
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Filter Buttons --}}
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'raw']) }}" class="btn btn-outline-primary {{ $currentFilter === 'RAW' ? 'active' : '' }}">RAW</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_edit']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Edit' ? 'active' : '' }}">Pilih Edit</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'result']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Result' ? 'active' : '' }}">Editing Result</a>
                    <a href="{{ route('transaksi.view-result-photos', ['transaksi' => $transaksi->transaction_id, 'filter' => 'pilih_cetak']) }}" class="btn btn-outline-primary {{ $currentFilter === 'Pilih Cetak' ? 'active' : '' }}">Pilih Cetak</a>
                </div>
            </div>

            {{-- Informational Text --}}
            <div class="alert alert-info shadow-sm border-0">
                @if($currentFilter === 'RAW')
                    <i class="bx bx-camera me-2 font-size-16 align-middle"></i>This is your current gallery of RAW photos from the photographer.
                @elseif($currentFilter === 'Pilih Edit')
                    <i class="bx bx-edit me-2 font-size-16 align-middle"></i>This is your current selection of photos for our editor to work on.
                @elseif($currentFilter === 'Result')
                    <i class="bx bx-check-double me-2 font-size-16 align-middle"></i>These are the final, edited photos. You can view and download them in high resolution.
                @elseif($currentFilter === 'Pilih Cetak')
                    @if(!$printAllowances)
                        <i class="bx bx-info-circle me-2 font-size-16 align-middle"></i>Your packet does not include any photo prints.
                    @elseif($transaksi->process_status === 'Proses Cetak')
                        <i class="bx bx-time-five me-2 font-size-16 align-middle"></i>Your photos are currently in the printing process. Please wait for further information.
                    @else
                        <i class="bx bx-printer me-2 font-size-16 align-middle"></i>This is your current selection of photos for printing.
                    @endif
                @endif
                
                {{-- Payment Warning if needed --}}
                @if($transaksi->status != 'sudah dibayar')
                    <div class="mt-2 text-danger fw-bold">
                        <i class="bx bx-error me-1"></i> Please complete your payment to enable full resolution downloads.
                    </div>
                @endif
            </div>

            @if($photoCount > 0)
                <div class="row g-4">
                    @foreach ($photoUrls as $index => $url)
                        @php
                            // 1. URL THUMBNAIL ($url): Digunakan untuk <img> agar ringan
                            // 2. URL ASLI ($originalUrl): Digunakan untuk preview & download
                            $originalUrl = str_replace(['/Thumbnails/', '/Thumbnails'], ['/', ''], $url);
                            
                            // Cek Status Pembayaran untuk Tombol Download per item
                            $canDownload = (auth()->user()->isAdmin() || $transaksi->status == 'sudah dibayar');
                            
                            $filename = basename($originalUrl);
                        @endphp
                        
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card h-100 mx-auto">
                                <div class="image-container ratio ratio-4x3">
                                    {{-- TAMPILKAN THUMBNAIL --}}
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo {{ $index + 1 }}" loading="lazy" onload="this.classList.add('show'); this.parentElement.classList.add('loaded');">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fs-6 mb-1 text-truncate" title="{{ $filename }}">{{ $filename }}</h5>
                                    <p class="card-text text-muted small mb-3">Photo #{{ $loop->iteration }}</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-auto gap-2">
                                        {{-- PREVIEW: Gunakan URL Thumbnail agar cepat muncul di modal --}}
                                        <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="openModal('{{ $url }}', '{{ $filename }}')">
                                            <i class="bx bx-fullscreen me-1"></i> Preview
                                        </button>
                                        
                                        {{-- DOWNLOAD: Gunakan URL Asli ($originalUrl) --}}
                                        @if($canDownload)
                                            <a href="{{ $originalUrl }}" download="{{ $filename }}" class="btn btn-sm btn-outline-success w-100">
                                                <i class="bx bx-download me-1"></i> Download
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 disabled-link" disabled title="Payment Required">
                                                <i class="bx bx-lock-alt me-1"></i> Download
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center py-5 mt-3">
                    <div class="mb-3">
                        <div class="avatar-md mx-auto">
                            <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-24">
                                <i class="bx bx-images"></i>
                            </span>
                        </div>
                    </div>
                    <h4>No Photos Found</h4>
                    <p class="mb-0 text-muted">There are no photos in the '{{ $currentFilter }}' folder for this transaction yet.</p>
                    <a href="{{ request()->fullUrl() }}" class="btn btn-primary mt-3"><i class="bx bx-refresh me-1"></i> Refresh Page</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Preview --}}
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title" id="modalTitle">Photo Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <div class="p-3" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                        <img id="modalPhoto" src="" class="img-fluid rounded shadow-sm" alt="Preview" style="max-height: 80vh;">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    @php
                        $canDownloadModal = (auth()->user()->isAdmin() || $transaksi->status == 'sudah dibayar');
                    @endphp
                    
                    @if($canDownloadModal)
                        <a id="downloadBtn" href="#" download class="btn btn-primary"><i class="bx bx-download me-1"></i> Download Full Resolution</a>
                    @else
                         <button type="button" class="btn btn-secondary" disabled><i class="bx bx-lock-alt me-1"></i> Payment Required to Download</button>
                    @endif
                    
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function openModal(photoUrl, photoName) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            const modalPhoto = document.getElementById('modalPhoto');
            const downloadBtn = document.getElementById('downloadBtn');
            const modalTitle = document.getElementById('modalTitle');

            // Set Image
            modalPhoto.src = photoUrl;
            modalTitle.textContent = photoName;

            // Update Download Link (Convert Thumbnail URL to Original URL logic in JS)
            // Pastikan replace logika ini sesuai dengan di Blade
            // Dari: .../Thumbnails/RAW/foto.jpg -> .../RAW/foto.jpg
            const originalUrl = photoUrl.replace('/Thumbnails/', '/').replace('/Thumbnails', '');
            
            if(downloadBtn) {
                downloadBtn.href = originalUrl;
                downloadBtn.download = photoName;
            }

            modal.show();
        }

        @if(session('error'))
        document.addEventListener('DOMContentLoaded', function () {
            // Jika pakai Toast
            // const toastElement = document.getElementById('errorToast');
            // const toast = new bootstrap.Toast(toastElement);
            // toast.show();
        });
        @endif
    </script>
@endsection