@extends('layouts.master')

@section('title')
    Pilih Foto
@endsection

@section('css')
    <!-- Sweet Alert-->
    <link href="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* Desktop Default Styles */
        .step-icon {
            width: 50px;
            height: 50px;
            background-color: #eff2f7;
            color: #556ee6;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 24px;
            margin: 0 auto 10px;
            transition: all 0.3s;
        }
        .step-item:hover .step-icon {
            background-color: #556ee6;
            color: #fff;
        }
        .external-link-card {
            border: 2px dashed #ced4da;
            background-color: #f8f9fa;
            transition: all 0.3s;
        }
        .external-link-card:hover {
            border-color: #556ee6;
            background-color: #fff;
        }
        
        /* Optimized Textarea for ALL devices */
        .custom-textarea {
            font-family: 'Courier New', Courier, monospace; /* Monospace for file names */
            background-color: #f8f9fa;
            border-color: #ced4da;
            width: 100%;
            resize: none; /* Disable manual resize */
            overflow: hidden; /* Hide scrollbar initially */
            min-height: 400px; /* Default large height */
        }
        .custom-textarea:focus {
            background-color: #fff;
            border-color: #556ee6;
            box-shadow: none;
        }

        /* Responsive & Mobile Optimization */
        @media (min-width: 768px) {
            .border-end-md {
                border-right: 1px solid #eff2f7 !important;
            }
            .custom-textarea {
                font-size: 14px;
                line-height: 1.6;
            }
        }

        @media (max-width: 767.98px) {
            /* Mobile specific tweaks */
            .step-item {
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                text-align: left !important;
                background: #fff;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .step-icon {
                margin: 0 15px 0 0; /* Icon on the left */
                width: 40px;
                height: 40px;
                font-size: 20px;
                flex-shrink: 0;
            }
            .step-content {
                flex-grow: 1;
            }
            
            /* Bigger font and touch targets for mobile */
            .custom-textarea {
                font-size: 16px !important; /* Prevents iOS zoom */
                line-height: 1.6 !important;
                padding: 12px;
                min-height: 350px;
            }
            
            /* Full width buttons */
            .btn-mobile-block {
                width: 100%;
                margin-bottom: 10px;
                padding: 12px;
                font-size: 16px;
            }
            
            .card-body {
                padding: 1.25rem !important;
            }
            
            .alert {
                font-size: 14px;
            }
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Transaksi @endslot
        @slot('title') Pilih Foto @endslot
    @endcomponent

    @php
        // --- LOGIKA PERHITUNGAN KUOTA FOTO (JATAH EDIT) ---
        $finalMaxPhotos = $transaksi->packet->max_photos_for_edit ?? 0;
        $displayMaxPhotos = $finalMaxPhotos > 0 ? $finalMaxPhotos . ' Foto' : 'Unlimited';

        // Hitung Total Kuota Cetak untuk Badge
        $totalPrintQuota = 0;
        if($transaksi->packet->printOptions->isNotEmpty()) {
            foreach($transaksi->packet->printOptions as $print) {
                $totalPrintQuota += $print->pivot->quantity;
            }
        }
        if($transaksi->additionals->isNotEmpty()) {
            foreach($transaksi->additionals as $additional) {
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    $totalPrintQuota += $additional->pivot->quantity;
                }
            }
        }
        $displayPrintQuota = $totalPrintQuota > 0 ? $totalPrintQuota . ' Lembar' : '0 Lembar';

        // --- GENERATE TEMPLATE TEXTAREA JIKA BELUM ADA (FALLBACK) ---
        // 1. Template Edit
        // Cek apakah controller mengirim variabel $editValue, jika tidak, generate sendiri
        if (!isset($editValue)) {
            $editValue = $transaksi->select_edit_photo;
            if (!$editValue) {
                $editValue = "Daftar Foto untuk Diedit (Maks {$displayMaxPhotos}):\n";
                if ($finalMaxPhotos > 0) {
                    for ($i = 1; $i <= $finalMaxPhotos; $i++) {
                        $editValue .= "{$i}. \n";
                    }
                } else {
                    $editValue .= "1. \n2. \n3. \n(Lanjutkan sendiri...)\n";
                }
            }
        }

        // 2. Template Print
        // Cek apakah controller mengirim variabel $printValue, jika tidak, generate sendiri
        if (!isset($printValue)) {
            $printValue = $transaksi->select_print_photo;
            if (!$printValue) {
                $printValue = "Daftar Foto untuk Dicetak:\n";
                
                // Dari Paket
                if($transaksi->packet->printOptions->isNotEmpty()) {
                    foreach($transaksi->packet->printOptions as $print) {
                        for($i = 0; $i < $print->pivot->quantity; $i++) {
                            $printValue .= "- Cetak {$print->name} : \n";
                        }
                    }
                }
                
                // Dari Additional
                foreach($transaksi->additionals as $additional) {
                    if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                        for($i = 0; $i < $additional->pivot->quantity; $i++) {
                            $printValue .= "- (Add-on) {$additional->name} : \n";
                        }
                    }
                }

                if ($totalPrintQuota == 0) {
                    $printValue = "Tidak ada item cetak dalam paket ini.";
                }
            }
        }
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-10 col-12">
            
            <!-- Panduan Langkah (Mobile Optimized) -->
            <div class="row mb-4">
                <div class="col-md-4 col-12 step-item">
                    <div class="step-icon"><i class="mdi mdi-google-drive"></i></div>
                    <div class="step-content">
                        <h5 class="font-size-14 mb-1">1. Buka Galeri</h5>
                        <p class="text-muted mb-0 font-size-13">Lihat foto di Google Drive.</p>
                    </div>
                </div>
                <div class="col-md-4 col-12 step-item">
                    <div class="step-icon"><i class="mdi mdi-file-document-edit-outline"></i></div>
                    <div class="step-content">
                        <h5 class="font-size-14 mb-1">2. Catat File</h5>
                        <p class="text-muted mb-0 font-size-13">Pilih kode foto (mis: IMG_001).</p>
                    </div>
                </div>
                <div class="col-md-4 col-12 step-item">
                    <div class="step-icon"><i class="mdi mdi-check-circle-outline"></i></div>
                    <div class="step-content">
                        <h5 class="font-size-14 mb-1">3. Simpan</h5>
                        <p class="text-muted mb-0 font-size-13">Isi formulir dan simpan.</p>
                    </div>
                </div>
            </div>

            <!-- DETAIL PAKET -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-dark font-size-16"><i class="mdi mdi-information-outline me-2"></i> Detail Paket</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kolom Kiri: Info Dasar -->
                        <div class="col-md-5 col-12 border-end-md mb-3 mb-md-0">
                            <h6 class="font-size-14 text-muted mb-3">Ringkasan Pesanan</h6>
                            <table class="table table-borderless table-sm mb-0 font-size-14">
                                <tr>
                                    <th style="width: 110px;" class="text-muted fw-normal">Produk</th>
                                    <td class="fw-bold text-primary">: {{ $transaksi->packet->product->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Paket</th>
                                    <td class="fw-bold">: {{ $transaksi->packet->name }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Invoice</th>
                                    <td>: {{ $transaksi->receipt_code }}</td>
                                </tr>
                                <tr><td colspan="2"><hr class="my-2"></td></tr>
                                <tr>
                                    <th class="text-muted fw-normal">Jatah Edit</th>
                                    <td>: 
                                        <span class="badge bg-soft-primary text-primary font-size-12">
                                            <i class="mdi mdi-image-edit"></i> {{ $displayMaxPhotos }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted fw-normal">Jatah Cetak</th>
                                    <td>: 
                                        <span class="badge bg-soft-success text-success font-size-12">
                                            <i class="mdi mdi-printer"></i> {{ $displayPrintQuota }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Kolom Kanan: Tabel Rincian Item -->
                        <div class="col-md-7 col-12">
                            <h6 class="font-size-14 text-muted mb-2">Item Paket & Tambahan:</h6>
                            <div class="table-responsive bg-light rounded p-2" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-borderless mb-0 font-size-13">
                                    <tbody>
                                        {{-- Included Prints --}}
                                        @if($transaksi->packet->printOptions->isNotEmpty())
                                            <tr><td colspan="2" class="fw-bold text-dark small border-bottom pb-1 mb-1 d-block w-100">Included Prints</td></tr>
                                            @foreach($transaksi->packet->printOptions as $print)
                                                <tr>
                                                    <td class="ps-2"><i class="bx bx-printer me-2 text-secondary"></i> Cetak {{ $print->name }}</td>
                                                    <td class="text-end fw-bold">x{{ $print->pivot->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        {{-- Additional Defaults --}}
                                        @if($transaksi->packet->additionalDefaults->isNotEmpty())
                                            <tr><td colspan="2" class="fw-bold text-dark small border-bottom pb-1 mb-1 mt-2 d-block w-100">Included Items</td></tr>
                                            @foreach($transaksi->packet->additionalDefaults as $default)
                                                <tr>
                                                    <td class="ps-2"><i class="bx bx-check-circle me-2 text-secondary"></i> {{ $default->additional->name }}</td>
                                                    <td class="text-end fw-bold">x{{ $default->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif

                                        {{-- Paid Additionals --}}
                                        @if($transaksi->additionals->isNotEmpty())
                                            <tr><td colspan="2" class="fw-bold text-warning small border-bottom pb-1 mb-1 mt-2 d-block w-100">Extra Add-ons</td></tr>
                                            @foreach($transaksi->additionals as $additional)
                                                <tr>
                                                    <td class="ps-2"><i class="bx bx-plus me-2 text-warning"></i> {{ $additional->name }}</td>
                                                    <td class="text-end fw-bold">x{{ $additional->pivot->quantity }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4 class="card-title mb-2">Formulir Pemilihan Foto</h4>
                        <p class="card-title-desc text-muted font-size-13">
                            Silakan masukkan nama file foto sesuai dengan rincian paket.
                        </p>
                    </div>

                    <!-- Area Link Foto -->
                    <div class="external-link-card rounded p-3 mb-4 text-center">
                        <h5 class="font-size-15 mb-3">Akses Galeri Foto Anda</h5>
                        @if($transaksi->url_images)
                            <a href="{{ $transaksi->url_images }}" target="_blank" class="btn btn-primary waves-effect waves-light btn-mobile-block">
                                <i class="mdi mdi-open-in-new me-2"></i> Buka Link Google Drive
                            </a>
                            <div class="mt-2 text-muted small text-break px-2">
                                <i class="mdi mdi-link-variant"></i> {{ $transaksi->url_images }}
                            </div>
                        @else
                            <div class="alert alert-warning d-inline-flex align-items-center mb-0" role="alert">
                                <i class="mdi mdi-alert-outline me-2"></i>
                                <span>Link foto belum tersedia. Hubungi admin.</span>
                            </div>
                        @endif
                    </div>

                    <form action="{{ route('transaksi.handle-select-for-edit', ['transaksi' => $transaksi->transaction_id]) }}" method="POST" id="photoSelectionForm">
                        @csrf
                        
                        <div class="alert alert-info alert-dismissible fade show font-size-13 mb-4" role="alert">
                            <i class="mdi mdi-information-outline me-2"></i>
                            <strong>Tips:</strong> Ketik nama file (mis: IMG_1234.JPG) di setiap baris.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>

                        <div class="row">
                            <!-- INPUT EDIT PHOTO (TEXTAREA) -->
                            <div class="col-md-6 col-12 mb-4">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-soft-primary p-3">
                                        <h5 class="font-size-16 mb-0 text-primary">
                                            <i class="mdi mdi-image-edit me-2"></i> Foto untuk Diedit
                                        </h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <textarea 
                                            name="select_edit_photo" 
                                            class="form-control custom-textarea border-0 rounded-0" 
                                            spellcheck="false"
                                            oninput="autoResize(this)"
                                            placeholder="Contoh:&#10;1. IMG_001.JPG&#10;2. IMG_005.JPG">{{ old('select_edit_photo', $editValue) }}</textarea>
                                    </div>
                                    <div class="card-footer bg-light p-2 text-center text-muted small">
                                        Maksimal: <strong>{{ $displayMaxPhotos }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- INPUT PRINT PHOTO (TEXTAREA) -->
                            <div class="col-md-6 col-12 mb-4">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-soft-success p-3">
                                        <h5 class="font-size-16 mb-0 text-success">
                                            <i class="mdi mdi-printer me-2"></i> Foto untuk Dicetak
                                        </h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <textarea 
                                            name="select_print_photo" 
                                            class="form-control custom-textarea border-0 rounded-0" 
                                            oninput="autoResize(this)"
                                            spellcheck="false">{{ old('select_print_photo', $printValue) }}</textarea>
                                    </div>
                                    <div class="card-footer bg-light p-2 text-center text-muted small">
                                        Total Kuota: <strong>{{ $displayPrintQuota }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-2 border-top pt-4">
                            <a href="{{ route('transaksi.index') }}" class="btn btn-light waves-effect btn-mobile-block order-2 order-md-1">
                                <i class="bx bx-arrow-back me-1"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-success waves-effect waves-light px-4 btn-mobile-block order-1 order-md-2 mb-3 mb-md-0">
                                <i class="bx bx-save me-1"></i> Simpan Pilihan Saya
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<!-- Include SweetAlert -->
<script src="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    function autoResize(textarea) {
        textarea.style.height = 'auto'; // Reset height
        textarea.style.height = textarea.scrollHeight + 'px'; // Set to scrollHeight
    }

    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.custom-textarea');
        textareas.forEach(textarea => {
            autoResize(textarea); // Initial resize
        });

        const form = document.getElementById('photoSelectionForm');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Simpan Pilihan?',
                text: "Pastikan nama file yang Anda tulis sudah benar sesuai di Google Drive.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#34c38f',
                cancelButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
</script>
@endsection