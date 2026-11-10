@extends('layouts.master')
@section('title')
    Daftar Transaksi
@endsection

@php
    use Illuminate\Support\Str;
@endphp

@section('css')
{{-- CSS Section --}}
<style>
    .invoice-modal .modal-dialog { max-width: 800px; }
    .invoice-modal .invoice-header { background-color: #f8f9fa; padding: 2rem; border-bottom: 1px solid #dee2e6; }
    .invoice-modal .invoice-logo { max-height: 60px; }
    .invoice-modal .invoice-details-table th,
    .invoice-modal .invoice-details-table td { border: none; }
    .clickable-price { cursor: pointer; color: inherit; text-decoration: none; }
    .clickable-price:hover { text-decoration: underline; color: #556ee6; }
    .sortable-header { cursor: pointer; position: relative; padding-right: 20px; }
    .sortable-header .sort-icon { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); opacity: 0.4; }
    .sortable-header.active .sort-icon { opacity: 1; color: #556ee6; }
    .advanced-filter-toggler { text-decoration: none; font-size: 0.9em; }
    a.disabled { pointer-events: none; opacity: 0.65; }
    .clickable-card {
        cursor: pointer;
        transition: transform 0.2s;
        display: block;
        color: inherit; 
    }
    .clickable-card:hover {
        transform: scale(1.03);
        color: inherit;
        text-decoration: none;
    }
    /* blur */
    .amount-container {
        position: relative;
        display: inline-block;
    }
    .amount-hidden {
        visibility: hidden;
        position: relative;
    }
    .amount-hidden::after {
        content: '******';
        visibility: visible;
        position: absolute;
        top: 0;
        left: 0;
        display: inline-block;
    }
    .toggle-amount-visibility {
        position: relative;
        z-index: 2;
        cursor: pointer;
        margin-left: 20px;
        color: #556ee6;
    }
    .toggle-amount-visibility:hover {
        color: #4458b8;
    }
    .toggle-amount-visibility.text-white {
        color: #ffffff !important;
    }
    /* Fix for 1920px monitors */
    @media (min-width: 1200px) and (max-width: 1920px) {
        /* 1. Reduce overall font size for the table */
        .table-responsive {
            font-size: 0.85rem;
        }

        /* 2. Tighten padding drastically */
        .table.table-centered th, 
        .table.table-centered td {
            padding: 0.5rem 0.25rem !important; /* Very tight horizontal padding */
            vertical-align: middle;
        }

        /* 3. CUSTOMER COLUMN: Force truncate with ellipsis */
        .table.table-centered td h6 {
            font-size: 0.9rem;
            margin-bottom: 0 !important;
            max-width: 150px;       /* Force max width */
            white-space: nowrap;    /* No wrapping */
            overflow: hidden;       /* Hide overflow */
            text-overflow: ellipsis; /* Add ... */
        }

        /* 4. PRODUCT COLUMN: Allow wrapping so it doesn't push width */
        .table.table-centered td:nth-child(3) { 
            max-width: 180px;       /* Limit width */
            white-space: normal !important; /* ALLOW WRAPPING */
            line-height: 1.2;
        }

        /* 5. DROPDOWNS: Make them smaller and compact */
        .form-select-sm {
            padding-top: 0.1rem;
            padding-bottom: 0.1rem;
            padding-left: 0.4rem;
            font-size: 0.8rem;
            min-width: auto; /* Allow shrinking */
            max-width: 140px; /* Prevent huge dropdowns */
        }

        /* 6. ACTION COLUMN: Reduce gaps and button sizes */
        .table .d-flex.gap-2 {
            gap: 0.25rem !important; /* Reduce gap from 0.5rem to 0.25rem */
        }
        .update-url-btn, .input-selection-btn, .wa-btn-action, .delete-btn, .btn-sm {
            padding: 0.15rem 0.3rem; /* Tiny buttons */
            font-size: 0.75rem;
        }
        
        /* 7. HIDE SCROLLBAR (Slider) if content fits */
        .table-responsive {
            overflow-x: auto;
        }
        /* Optional: Hide scrollbar visually but allow scroll */
        .table-responsive::-webkit-scrollbar {
            height: 4px; /* Make scrollbar very thin */
        }
    }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Daftar Transaksi', 'pagetitle' => 'Admin', 'breadcrumbs' => [['text' => 'Transaksi', 'url' => '']]])
    @endcomponent

    

    {{-- Interactive Filter Card --}}
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    @php
                                        function sortable_header($label, $column, $request) {
                                            $sortBy = $request->input('sort_by');
                                            $sortDirection = $request->input('sort_direction', 'asc');
                                            $isActive = ($sortBy === $column);
                                            $newDirection = ($isActive && $sortDirection === 'asc') ? 'desc' : 'asc';
                                            $icon = $isActive ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort';
                                            $url = $request->fullUrlWithQuery(['sort_by' => $column, 'sort_direction' => $newDirection]);
                                            return '<a href="' . $url . '" class="text-dark sortable-header' . ($isActive ? ' active' : '') . '">' . $label . '<i class="bx ' . $icon . ' sort-icon"></i></a>';
                                        }
                                    @endphp
                                    <th>Pelanggan</th>
                                    <th>Down Payment</th>
                                    <!-- <th>Tipe Pembayaran</th> -->
                                    <th>Produk</th>
                                    <th>{!! sortable_header('Total Biaya', 'total_price', request()) !!}</th>
                                    <th>{!! sortable_header('Tanggal', 'created_at', request()) !!}</th>
                                    <th>{!! sortable_header('Tanggal Book', 'session_date', request()) !!}</th>
                                    <th>Status Pembayaran</th>
                                    <th>Status Pengerjaan</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $paymentStatusConfig = [
                                        'belum dibayar'     => ['label' => 'Belum Dibayar', 'icon' => '🟡', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'menunggu pembayaran'=> ['label' => 'Menunggu Pembayaran', 'icon' => '🟠', 'class' => 'bg-neutral-subtle text-neutral-emphasis'], // Opsional, jika dipakai
                                        'dp'                => ['label' => 'DP (Uang Muka)', 'icon' => '🔵', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'sudah dibayar'     => ['label' => 'Lunas', 'icon' => '🟢', 'class' => 'bg-success-subtle text-success-emphasis'],
                                        'gagal'             => ['label' => 'Gagal / Kedaluwarsa', 'icon' => '🔴', 'class' => 'bg-danger-subtle text-danger-emphasis'],
                                        'xendit'             => ['label' => 'Xendit', 'icon' => '🟢', 'class' => 'bg-danger-subtle text-danger-emphasis'],

                                    ];
                                    
                                    $processStatusConfig = [
                                        'Pelanggan Belum Foto' => ['icon' => '📷❌', 'class' => 'bg-light text-dark'],
                                        'Pelanggan Pilih Foto' => ['icon' => '🖼️', 'class' => 'bg-info-subtle text-info-emphasis'],
                                        'Proses Edit' => ['icon' => '✏️', 'class' => 'bg-primary-subtle text-primary-emphasis'],
                                        'Proses Cetak' => ['icon' => '🖨️', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                                        'Selesai' => ['icon' => '✅', 'class' => 'bg-success-subtle text-success-emphasis']
                                    ];

                                    $paymentTypes = [
                                        'none' => 'None',
                                        'Cash' => 'Cash',
                                        'Transfer' => 'Transfer',
                                        'QRIS' => 'QRIS',
                                        'Xendit' => 'Xendit'
                                    ];
                                @endphp
                                @forelse($transactions as $transaksi)
                                    <tr data-process-status="{{ $transaksi->process_status }}" data-payment-status="{{ $transaksi->status }}">
                                        
                                        <td>
                                            <h6 class="mb-1">{{ $transaksi->customer_name }}</h6>
                                            <div class="text-muted" style="font-size: 0.85em;">
                                                <i class="bx bx-phone text-secondary me-1"></i>
                                                {{ $transaksi->phone_number }}
                                            </div>
                                        </td>

                                       <td>
                                            <h6 class="mb-1">
                                            {{ $transaksi->dp_amount ? 'Rp ' . number_format($transaksi->dp_amount, 0, ',', '.') : '-' }}
                                            </h6>
                                        </td>

                                        {{-- Payment Type Column --}}
                                        <!-- <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="payment_type">
                                                <select name="value" class="form-select form-select-sm" onchange="this.form.submit()">
                                                     @foreach ($paymentTypes as $val => $label)
                                                         <option value="{{ $val }}" {{ $transaksi->payment_type == $val ? 'selected' : '' }}>
                                                             {{ $label }}
                                                         </option>
                                                     @endforeach
                                                </select>
                                            </form>
                                        </td> -->

                                        <td>
                                            <span class="fw-bold">{{ $transaksi->packet->product->name ?? 'N/A' }}</span>
                                            @if($transaksi->packet && $transaksi->packet->product)
                                                <br>
                                                <small class="text-muted">{{ $transaksi->packet->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold"><a href="javascript:void(0);" class="clickable-price" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</a></td>
                                        <td>{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                        
                                        <td>{{ $transaksi->session_date }}, {{$transaksi->session_time}}</td>

                                        <td>
                                            {{-- Payment Status Dropdown with Modal Trigger --}}
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST" class="status-update-form">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="status">
                                                <select name="value" 
                                                        class="form-select form-select-sm payment-status-select {{ $paymentStatusConfig[$transaksi->status]['class'] ?? '' }}" 
                                                        data-transaction-id="{{ $transaksi->transaction_id }}"
                                                        data-current-status="{{ $transaksi->status }}"
                                                        data-current-payment-type="{{ $transaksi->payment_type }}">
                                                    @foreach ($paymentStatusConfig as $statusKey => $config)
                                                        <option value="{{ $statusKey }}" {{ $transaksi->status == $statusKey ? 'selected' : '' }}>
                                                            {{ $config['icon'] }} {{ $config['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('transaksi.update-status', $transaksi->transaction_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="field" value="process_status">
                                                <select name="value" id="process-status-select-{{ $transaksi->transaction_id }}" class="form-select form-select-sm {{ $processStatusConfig[$transaksi->process_status]['class'] ?? '' }}" onchange="this.form.submit()">
                                                     @foreach ($processStatusConfig as $status => $config)
                                                         <option value="{{ $status }}" 
                                                            {{ $transaksi->process_status == $status ? 'selected' : '' }}>
                                                             {{ $config['icon'] }} {{ $status }}
                                                         </option>
                                                     @endforeach
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Lihat</button>
                                        </td>
                                        
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">Data transaksi tidak ditemukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="row mt-4">
                        <div class="col-sm-6 d-flex align-items-center">
                            <div>
                                <p class="mb-sm-0">Menampilkan {{ $transactions->firstItem() }} sampai {{ $transactions->lastItem() }} dari {{ $transactions->total() }} data</p>
                            </div>
                            <div class="ms-3">
                                <form method="GET" action="{{ route('transaksi.index') }}" class="d-flex align-items-center">
                                    {{-- Preserve all current filters including hide_completed --}}
                                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ is_array($value) ? http_build_query($value) : $value }}">
                                    @endforeach
                                    <label for="per_page" class="form-label me-2 mb-0">Tampil:</label>
                                    <select name="per_page" id="per_page" class="form-select form-select-sm" style="width: 70px;" onchange="this.form.submit()">
                                        @foreach($perPageOptions as $option)
                                            <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-end">
                                {{ $transactions->withQueryString()->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Details, Delete Confirmation, Payment Action Modal --}}
    @foreach($transactions as $transaksi)
        <div id="detailModal{{ $transaksi->transaction_id }}" class="modal fade invoice-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="invoice-content" id="invoiceContent{{ $transaksi->transaction_id }}">
                            <div class="invoice-header text-center"><div class="mb-3"><img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="logo" class="invoice-logo"/></div><p class="text-muted mb-0">Jalan Lingkar Selatan, Sukabumi</p></div>
                            <div class="p-4">
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Ditagihkan Kepada:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p>@if($transaksi->phone_number)<p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p>@endif @if($transaksi->user)<p class="mb-1 text-muted"><i class="mdi mdi-account-circle-outline me-1"></i> Akun User: {{ $transaksi->user->name }}</p>@endif</div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Rincian Invoice:</h5><p class="mb-1"><strong>No. Invoice:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Tanggal Transaksi:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p><p class="mb-1"><strong>Status Pembayaran:</strong> {{ $paymentStatusConfig[$transaksi->status]['label'] ?? ucwords($transaksi->status) }}</p>
                                <p class="mb-1"><strong>Tipe Pembayaran:</strong> {{ $transaksi->payment_type == 'none' ? '-' : $transaksi->payment_type }}</p>
                                <p class="mb-1"><strong>Status Pengerjaan:</strong> {{ $transaksi->process_status }}</p></div></div>
                                <div class="py-2 mt-3"><h3 class="font-size-15 fw-bold">Ringkasan Pesanan</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap">
                                        <thead class="table-light"><tr><th style="width: 70px;">No.</th><th>Item</th><th class="text-end">Harga</th><th class="text-center">Jml</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                            @if($transaksi->packet)
                                                <tr>
                                                    <td>1</td>
                                                    <td><h5 class="font-size-15 mb-0">{{ $transaksi->packet->name }}</h5><span class="text-muted">{{ $transaksi->packet->product->name ?? '' }}</span></td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                    <td class="text-center">1</td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                </tr>
                                            @endif
                                            @if($transaksi->packet && $transaksi->packet->printOptions->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Cetak Termasuk:</strong></td></tr>
                                                @foreach($transaksi->packet->printOptions as $printOption)
                                                    <tr class="bg-light">
                                                        <td><i class="mdi mdi-circle-small text-muted"></i></td>
                                                        <td>
                                                            <span class="text-dark">Include Cetak {{ $printOption->name }}</span>
                                                        </td>
                                                        <td class="text-end text-muted small">(Included)</td>
                                                        <td class="text-center">{{ $printOption->pivot->quantity }}</td>
                                                        <td class="text-end">-</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            @if($transaksi->packet && $transaksi->packet->additionalDefaults->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Tambahan Termasuk:</strong></td></tr>
                                                @foreach($transaksi->packet->additionalDefaults as $default)
                                                    <tr>
                                                        <td><i class="mdi mdi-circle-small text-muted"></i></td>
                                                        <td colspan="4">{{ $default->quantity }}x {{ $default->additional->name }}</td>
                                                    </tr>
                                                @endforeach 
                                            @endif
                                            @if($transaksi->additionals->isNotEmpty())
                                                <tr><td colspan="5" class="pt-3 pb-0"><strong class="text-muted small">Item Tambahan:</strong></td></tr>
                                                @foreach($transaksi->additionals as $additional)
                                                    <tr>
                                                        <td><i class="mdi mdi-circle-small text-muted"></td>
                                                        <td><h5 class="font-size-15 mb-0">{{ $additional->name }}</h5><span class="text-muted">Item Tambahan</span></td>
                                                        <td class="text-end">Rp {{ number_format($additional->pivot->price, 0, ',', '.') }}</td>
                                                        <td class="text-center">{{ $additional->pivot->quantity }}</td>
                                                        <td class="text-end">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach 
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <div class="w-50">
                                        <table class="table table-nowrap invoice-details-table">
                                            <tbody>
                                                <tr><td class="fw-bold">Subtotal</td><td class="text-end">Rp {{ number_format($transaksi->total_price + $transaksi->discount, 0, ',', '.') }}</td></tr>
                                                @if ($transaksi->discount > 0)<tr class="text-danger"><td class="fw-bold">Diskon</td><td class="text-end">- Rp {{ number_format($transaksi->discount, 0, ',', '.') }}</td></tr>@endif
                                                <tr class="border-top"><td class="fw-bold">Total Akhir</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                
                                                @if($transaksi->status == 'dp' && $transaksi->dp_amount > 0)
                                                    <tr><td class="fw-bold">DP Terbayar</td><td class="text-end">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Sisa Tagihan</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                @elseif($transaksi->status == 'sudah dibayar')
                                                    @if($transaksi->dp_amount > 0)
                                                        <tr><td class="fw-bold">DP (Awal)</td><td class="text-end">Rp {{ number_format($transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                        <tr><td class="fw-bold">Pelunasan</td><td class="text-end">Rp {{ number_format($transaksi->total_price - $transaksi->dp_amount, 0, ',', '.') }}</td></tr>
                                                    @endif
                                                    <tr class="fs-5 bg-light"><td class="fw-bold">Total Terbayar</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @else
                                                     <tr class="fs-5 bg-light"><td class="fw-bold">Total Tagihan</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @if($transaksi->note)<hr><div class="py-2"><h5 class="font-size-15">Catatan:</h5><p class="text-muted fst-italic">{{ $transaksi->note }}</p></div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><a href="{{ route('transaksi.print-invoice', $transaksi) }}" target="_blank" class="btn btn-primary"><i class="mdi mdi-printer me-1"></i> Cetak Nota</a></div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bx bx-error-circle bx-lg text-danger mb-2"></i>
                        <p>Apakah Anda yakin ingin menghapus transaksi ini?</p>
                        <p class="text-muted">Tindakan ini tidak dapat dibatalkan dan akan menghapus folder foto terkait.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-btn">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script>
    function setQuickFilter(type) {
        const today = new Date();
        let startDate = '';
        let endDate = '';
        const formatDate = (date) => {
            let d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            return [year, month, day].join('-');
        }
        if (type === 'today') {
            startDate = formatDate(today);
            endDate = formatDate(today);
        } else if (type === 'month') {
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            startDate = formatDate(firstDay);
            endDate = formatDate(lastDay);
        } else if (type === 'year') {
            const firstDay = new Date(today.getFullYear(), 0, 1);
            const lastDay = new Date(today.getFullYear(), 11, 31);
            startDate = formatDate(firstDay);
            endDate = formatDate(lastDay);
        } else if (type === 'all') {
            startDate = '';
            endDate = '';
        }
        document.getElementById('start_date').value = startDate;
        document.getElementById('end_date').value = endDate;
        document.getElementById('filterForm').submit();
    }

document.addEventListener('DOMContentLoaded', function () {
    // 1. REPLACED dpAmountModal with paymentActionModal
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentActionModal'));
    const paymentForm = document.getElementById('paymentActionForm');
    const paymentInputContainer = document.getElementById('dp_amount_container');
    const paymentInput = document.getElementById('dp_amount_modal');
    const paymentStatusInput = document.getElementById('payment_status_input');

    // Init URL Modal
    const urlModal = new bootstrap.Modal(document.getElementById('urlModal'));
    const urlForm = document.getElementById('urlForm');
    const urlInput = document.getElementById('url_input');
    const urlLabel = document.getElementById('urlModalLabel');
    const urlFieldInput = document.getElementById('url_field_input');

    // Init Selection Modal
    const selectionModal = new bootstrap.Modal(document.getElementById('inputSelectionModal'));
    const selectionForm = document.getElementById('selectionForm');
    const selectionTextInput = document.getElementById('selection_text_input');

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModal) {
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        let formToSubmit = null;

        confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const formId = button.getAttribute('data-form-id');
            formToSubmit = document.getElementById(formId);
        });

        confirmDeleteBtn.addEventListener('click', function () {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    }

    
    // =================================================

    // 2. UPDATED LOGIC for Payment Status Selection (WITH SKIP CHECK)
    document.querySelectorAll('.payment-status-select').forEach(selectElement => {
        selectElement.addEventListener('change', function (e) {
            const selectedStatus = e.target.value;
            const form = e.target.closest('form');
            
            // Get current state from data attributes
            const currentStatus = this.getAttribute('data-current-status');
            const currentPaymentType = this.getAttribute('data-current-payment-type');

            if (selectedStatus === 'dp') {
                // Case: DP -> Show Amount Input + Payment Type
                paymentForm.action = form.action;
                paymentStatusInput.value = 'dp';
                paymentInputContainer.style.display = 'block';
                paymentInput.required = true;
                paymentModal.show();
            } else if (selectedStatus === 'sudah dibayar') {
                // Case: Lunas
                
                // UPGRADE: CHECK IF WE SHOULD SKIP MODAL
                // If coming from DP AND we already have a valid payment type
                if (currentStatus === 'dp' && currentPaymentType && currentPaymentType !== 'none') {
                    form.submit();
                    return; // Stop execution to prevent modal show
                }

                // If not skipped, prepare modal
                paymentForm.action = form.action;
                paymentStatusInput.value = 'sudah dibayar';
                paymentInputContainer.style.display = 'none';
                paymentInput.required = false;
                
                // Pre-select payment type if exists (e.g. from previous state if any)
                if (currentPaymentType && currentPaymentType !== 'none') {
                     const modalSelect = document.getElementById('payment_type_modal');
                     if(modalSelect) modalSelect.value = currentPaymentType;
                }

                paymentModal.show();
            } else {
                // Case: Belum Dibayar -> Submit Immediately
                form.submit();
            }
        });
    });

    // Auto-focus logic for modal
    document.getElementById('paymentActionModal').addEventListener('shown.bs.modal', function () {
        if(paymentInputContainer.style.display !== 'none') {
            paymentInput.focus();
        }
    });

    // Handle Update URL Buttons
    document.querySelectorAll('.update-url-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.dataset.id;
            const field = this.dataset.field;
            const currentValue = this.dataset.value;
            
            if (field === 'url_images') {
                urlLabel.textContent = 'Update Link Galeri (Pemilihan Foto)';
                urlInput.placeholder = 'https://...';
            } else {
                urlLabel.textContent = 'Update Link Hasil Akhir (Final)';
                urlInput.placeholder = 'https://...';
            }

            urlInput.value = currentValue;
            urlFieldInput.value = field;
            urlForm.action = `/transaksi/${transactionId}/update-status`;
            urlModal.show();
        });
    });

    document.getElementById('urlModal').addEventListener('shown.bs.modal', function () {
        urlInput.focus();
    });

    // Handle Input Selection Button
    document.querySelectorAll('.input-selection-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const transactionId = this.dataset.id;
            const existingText = this.dataset.existingText;
            
            document.getElementById('selection_text_input').value = existingText;
            document.getElementById('selectionForm').action = `/transaksi/${transactionId}/update-selections`;
            
            selectionModal.show();
        });
    });

    // Handle WA Button Auto Update
    document.addEventListener('click', function(e) {
        if (e.target.closest('.wa-btn-action')) {
            const btn = e.target.closest('.wa-btn-action');
            const nextStatus = btn.dataset.nextStatus;
            const transactionId = btn.dataset.transactionId;

            if (nextStatus) {
                setTimeout(() => {
                    const select = document.getElementById(`process-status-select-${transactionId}`);
                    if (select) {
                        select.value = nextStatus;
                        select.form.submit();
                    }
                }, 1000); 
            }
        }
    });

    const profitCard = document.getElementById('profit-card-toggler');
    if (profitCard) {
        const titleEl = document.getElementById('profit-card-title');
        const valueEl = document.getElementById('profit-card-value');
        const subtitleEl = document.getElementById('profit-card-subtitle');

        const filteredProfit = parseFloat(profitCard.dataset.filteredProfit);
        const overallProfit = parseFloat(profitCard.dataset.overallProfit);
        
        let isShowingFiltered = true;

        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });

        profitCard.addEventListener('click', function(event) {
            if (event.target.classList.contains('toggle-amount-visibility')) return;

            event.preventDefault(); 
            isShowingFiltered = !isShowingFiltered;

            const amountContainer = valueEl.querySelector('.amount-container');
            const amountValue = amountContainer.querySelector('.amount-value');

            if (isShowingFiltered) {
                titleEl.textContent = 'Total Pendapatan (Terfilter)';
                subtitleEl.textContent = 'Klik untuk lihat total keseluruhan';
                amountContainer.dataset.amount = formatter.format(filteredProfit);
                amountValue.textContent = formatter.format(filteredProfit);
            } else {
                titleEl.textContent = 'Total Pendapatan (Keseluruhan)';
                subtitleEl.textContent = 'Klik untuk lihat total terfilter';
                amountContainer.dataset.amount = formatter.format(overallProfit);
                amountValue.textContent = formatter.format(overallProfit);
            }
        });
    }
    
    document.querySelectorAll('.toggle-amount-visibility').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            const amountContainer = this.closest('.amount-container');
            const amountValue = amountContainer.querySelector('.amount-value');
            
            if (amountValue.classList.contains('amount-hidden')) {
                amountValue.classList.remove('amount-hidden');
                this.classList.remove('bx-hide');
                this.classList.add('bx-show-alt');
            } else {
                amountValue.classList.add('amount-hidden');
                this.classList.remove('bx-show-alt');
                this.classList.add('bx-hide');
            }
        });
    });
});
</script>
@endsection

@section('script-bottom')
{{-- 3. REPLACED dpAmountModal WITH paymentActionModal --}}
<div class="modal fade" id="paymentActionModal" tabindex="-1" aria-labelledby="paymentActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="paymentActionForm" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="field" value="status">
                <input type="hidden" name="value" id="payment_status_input" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentActionModalLabel">Konfirmasi Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Only shown for DP --}}
                    <div class="mb-3" id="dp_amount_container">
                        <label for="dp_amount_modal" class="form-label">Jumlah DP (Rp)</label>
                        <input type="number" class="form-control" id="dp_amount_modal" name="dp_amount" min="0">
                    </div>
                    
                    {{-- Shown for both DP and Lunas --}}
                    <div class="mb-3">
                        <label for="payment_type_modal" class="form-label">Tipe Pembayaran</label>
                        <select class="form-select" id="payment_type_modal" name="payment_type" required>
                            @foreach ($paymentTypes as $val => $label)
                                @if($val !== 'none') 
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- URL Input Modal (NO REQUIRED) --}}
<div class="modal fade" id="urlModal" tabindex="-1" aria-labelledby="urlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="urlForm" method="POST">
                @csrf
                @method('PUT')
                {{-- Field name will be dynamic --}}
                <input type="hidden" id="url_field_input" name="field" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="urlModalLabel">Update Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="url_input" class="form-label">URL / Link</label>
                        {{-- REMOVED REQUIRED ATTRIBUTE --}}
                        <input type="url" class="form-control" id="url_input" name="value" placeholder="https://...">
                        <small class="text-muted">Pastikan link diawali dengan https://</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Link</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Selection Input Modal (UPDATED: Single Textarea) --}}
<div class="modal fade" id="inputSelectionModal" tabindex="-1" aria-labelledby="inputSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="selectionForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="inputSelectionModalLabel">Input Pilihan Foto Pelanggan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-1"></i> Salin dan tempel (Paste) seluruh pesan balasan dari WhatsApp pelanggan ke kolom di bawah ini. Sistem akan otomatis memisahkan daftar foto edit dan cetak.
                    </div>
                    
                    <div class="mb-3">
                        <label for="selection_text_input" class="form-label fw-bold">Pesan Balasan WhatsApp</label>
                        <textarea class="form-control" id="selection_text_input" name="selection_text" rows="15" placeholder="Contoh:
*DAFTAR FOTO EDIT (Max 10 Foto)*
1. 1234
2. 5678

*DAFTAR FOTO CETAK*
- 10R : 1234"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Pilihan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection