@extends('layouts.master')

@section('title')
    View Selections
@endsection

@section('css')
    <style>
        .selection-card {
            transition: all 0.3s;
            border: 0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
        }
        .selection-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .file-list-container {
            max-height: 450px;
            overflow-y: auto;
        }
        .file-item {
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .file-item:hover {
            background-color: #f8f9fa;
            border-left-color: #556ee6;
        }
        .file-name {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
            color: #2a3042;
        }
        .file-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #eff2f7;
            border-radius: 8px;
            color: #556ee6;
            font-size: 18px;
        }
        .card-header-clean {
            background-color: transparent;
            border-bottom: 1px solid #eff2f7;
            padding: 1.25rem;
        }
        /* Custom Scrollbar */
        .file-list-container::-webkit-scrollbar {
            width: 6px;
        }
        .file-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .file-list-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Transaction @endslot
        @slot('title') View User Selections @endslot
    @endcomponent

    @php
        // Mapping Slot Cetak
        $printSlots = [];
        if($transaksi->packet && $transaksi->packet->printOptions) {
            foreach($transaksi->packet->printOptions as $print) {
                for($i = 0; $i < $print->pivot->quantity; $i++) {
                    $printSlots[] = 'Cetak ' . $print->name;
                }
            }
        }
        if($transaksi->additionals) {
            foreach($transaksi->additionals as $additional) {
                if (stripos($additional->name, 'Cetak') !== false || stripos($additional->name, 'Print') !== false) {
                    for($i = 0; $i < $additional->pivot->quantity; $i++) {
                        $printSlots[] = $additional->name;
                    }
                }
            }
        }

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

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    
                    {{-- Header Area --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 border-bottom pb-4">
                        <div>
                            <h4 class="text-dark mb-1">Detail Pilihan Foto</h4>
                            <p class="text-muted mb-0">
                                <i class="mdi mdi-receipt me-1"></i> {{ $transaksi->receipt_code }} &nbsp;|&nbsp; 
                                <i class="mdi mdi-account me-1"></i> {{ $transaksi->customer_name }}
                            </p>
                        </div>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            @if($urlImages)
                                <a href="{{ $urlImages }}" target="_blank" class="btn btn-light text-primary waves-effect border">
                                    <i class="mdi mdi-google-drive me-1"></i> Buka Link Drive
                                </a>
                            @endif
                            
                            {{-- Tombol Proses Edit (Update Status Value) --}}
                            @if(in_array($transaksi->process_status, ['Pelanggan Pilih Foto', 'Siap Edit dan Cetak']))
                                <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="field" value="process_status">
                                    {{-- UPDATE VALUE STATUS BARU --}}
                                    <input type="hidden" name="value" value="Proses Edit dan Cetak">
                                    <input type="hidden" name="redirect_to" value="index">
                                    <button type="submit" class="btn btn-primary waves-effect waves-light">
                                        <i class="mdi mdi-play-circle-outline me-1"></i> Mulai Proses Edit & Cetak
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Edit Photos Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border shadow-none bg-light">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="font-size-16 mb-0 text-white">
                                        <i class="mdi mdi-image-edit me-2"></i> Foto untuk Diedit
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($transaksi->select_edit_photo)
                                        <div class="form-group">
                                            <textarea 
                                                class="form-control bg-white border-0" 
                                                rows="15" 
                                                readonly 
                                                style="font-family: 'Courier New', Courier, monospace; line-height: 1.6; font-size: 14px; resize: none;"
                                            >{{ $transaksi->select_edit_photo }}</textarea>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <button class="btn btn-outline-primary btn-sm copy-btn" data-clipboard-text="{{ $transaksi->select_edit_photo }}">
                                                <i class="mdi mdi-content-copy me-1"></i> Salin Teks
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-5 text-muted">
                                            <i class="mdi mdi-image-off font-size-24 mb-2 d-block"></i>
                                            Pelanggan belum memilih foto untuk diedit.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Print Photos Section -->
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border shadow-none bg-light">
                                <div class="card-header bg-success text-white">
                                    <h5 class="font-size-16 mb-0 text-white">
                                        <i class="mdi mdi-printer me-2"></i> Foto untuk Dicetak
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($transaksi->select_print_photo)
                                        <div class="form-group">
                                            <textarea 
                                                class="form-control bg-white border-0" 
                                                rows="15" 
                                                readonly 
                                                style="font-family: 'Courier New', Courier, monospace; line-height: 1.6; font-size: 14px; resize: none;"
                                            >{{ $transaksi->select_print_photo }}</textarea>
                                        </div>
                                        <div class="mt-2 text-end">
                                            <button class="btn btn-outline-success btn-sm copy-btn" data-clipboard-text="{{ $transaksi->select_print_photo }}">
                                                <i class="mdi mdi-content-copy me-1"></i> Salin Teks
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center py-5 text-muted">
                                            <i class="mdi mdi-printer-off font-size-24 mb-2 d-block"></i>
                                            Pelanggan belum memilih foto untuk dicetak / Tidak ada kuota cetak.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-start mt-2">
                        <a href="{{ route('transaksi.index') }}" class="btn btn-link text-secondary text-decoration-none ps-0">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Daftar Transaksi
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Menggunakan Toastr atau alert sederhana
                alert('Link berhasil disalin!'); 
            }, function(err) {
                console.error('Gagal menyalin teks: ', err);
            });
        }
    </script>
@endsection