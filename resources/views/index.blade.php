@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
    <!-- DataTables -->
    <link href="{{ URL::asset('/assets/libs/datatables/datatables.min.css') }}" rel="stylesheet" type="text/css" />

    <style>
        /* Invoice Modal Styling */
        .invoice-modal .modal-dialog { max-width: 800px; }
        .invoice-modal .invoice-header { background-color: #f8f9fa; padding: 2rem; border-bottom: 1px solid #dee2e6; }
        .invoice-modal .invoice-logo { max-height: 60px; }
        .invoice-modal .invoice-details-table th,
        .invoice-modal .invoice-details-table td { border: none; }
        
        /* Links & cursors */
        .clickable-price { cursor: pointer; color: inherit; text-decoration: none; }
        .clickable-price:hover { text-decoration: underline; color: #556ee6; }
        .cursor-pointer { cursor: pointer; }
        
        /* Card Hover Effect */
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

        /* Privacy Blur Effect */
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
    </style>
@endsection

@section('content')

    @component('common-components.breadcrumb')
        @slot('pagetitle') Yunas Studio @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    <div class="row">
        {{-- Total Income Card with Filter --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <div class="dropdown">
                            <a class="dropdown-toggle text-reset" href="#" id="dropdownMenuButtonIncome"
                                data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="fw-semibold">Filter:</span> <span class="text-muted" id="income-sort-label">Bulan Ini</span> <i class="mdi mdi-chevron-down ms-1"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButtonIncome">
                                <a class="dropdown-item income-filter cursor-pointer" data-type="total">Total (Akumulasi)</a>
                                <a class="dropdown-item income-filter cursor-pointer" data-type="monthly">Bulan Ini</a>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="mb-1 mt-1">
                            Rp <span id="total-income-display" 
                                     data-total="{{ $totalIncome }}" 
                                     data-monthly="{{ $totalIncomeMonthly }}">
                                     {{ number_format($totalIncomeMonthly, 0, ',', '.') }}
                               </span>
                        </h4>
                        <p class="text-muted mb-0">Total Pemasukan</p>
                    </div>
                    
                    <p class="text-muted mt-3 mb-0" id="income-subtext">
                        <span class="text-info me-1"><i class="mdi mdi-calendar-month me-1"></i></span> Bulan Ini
                    </p>
                </div>
            </div>
        </div>

        {{-- Total Transactions Card --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="orders-chart" data-colors='["--bs-success"]'> </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $total_transactions }}</span></h4>
                        <p class="text-muted mb-0">Total Transaksi</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-success me-1"><i class="mdi mdi-check-all me-1"></i>{{ $completed_transactions }}</span> Selesai</p>
                </div>
            </div>
        </div>

        {{-- Pending Transactions Card --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="customers-chart" data-colors='["--bs-danger"]'> </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1"><span data-plugin="counterup">{{ $pending_transactions }}</span></h4>
                        <p class="text-muted mb-0">Belum Lunas / DP</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-danger me-1"><i class="mdi mdi-alert-circle-outline me-1"></i></span> Perlu Tindakan</p>
                </div>
            </div>
        </div>

        {{-- Profit Card --}}
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div id="growth-chart" data-colors='["--bs-warning"]'></div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">Rp <span data-plugin="counterup">{{ number_format($profit, 0, ',', '.') }}</span></h4>
                        <p class="text-muted mb-0">Keuntungan Bersih</p>
                    </div>
                    <p class="text-muted mt-3 mb-0"><span class="text-success me-1"><i class="mdi mdi-wallet me-1"></i></span> (Income - Expense)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Sales Analytics Chart --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="float-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-light chart-filter active" data-filter="daily">Daily</button>
                            <button type="button" class="btn btn-light chart-filter" data-filter="monthly">Monthly</button>
                            <button type="button" class="btn btn-light chart-filter" data-filter="yearly">Yearly</button>
                        </div>
                    </div>
                    <h4 class="card-title mb-4">Analisis Pemasukan</h4>

                    <div class="mt-3">
                        <div id="sales-analytics-chart" class="apex-charts" dir="ltr" style="min-height: 450px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Selling Packets --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Paket Terlaris</h4>

                    <div style="max-height: 450px; overflow-y: auto;">
                        @foreach($top_selling_packets as $item)
                        <div class="row align-items-center g-0 mt-3 border-bottom pb-2">
                            <div class="col-sm-3">
                                <div class="avatar-sm">
                                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24">
                                        <i class="bx bx-camera"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-9">
                                <div class="mt-4 mt-sm-0">
                                    <h5 class="font-size-14 mb-1">{{ $item->packet->product->name ?? 'Unknown' }}</h5>
                                    <p class="text-muted mb-0 text-truncate">{{ $item->packet->name ?? '' }}</p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="badge bg-success font-size-12">{{ $item->total }} Terjual</span>
                                        <small class="text-muted">ID: #{{ $item->packet_id }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Advanced Recent Transactions Table --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Transaksi Terbaru</h4>
                        <a href="{{ route('transaksi.index') }}" class="btn btn-primary btn-sm">Lihat Semua <i class="mdi mdi-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="table-responsive dt-responsive">
                        <table id="datatable" class="table table-bordered dt-responsive nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead class="table-light">
                                <tr>
                                    <th>Kode Invoices</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Total Biaya</th>
                                    <th>Tanggal</th>
                                    <th>Status Pembayaran</th>
                                    <th>Status Pengerjaan</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_transactions as $transaksi)
                                    <tr>
                                        <td><a href="javascript: void(0);" class="text-body fw-bold">{{ $transaksi->receipt_code }}</a></td>
                                        
                                        <td>
                                            <h6 class="mb-1">{{ $transaksi->customer_name }}</h6>
                                            <div class="text-muted" style="font-size: 0.85em;">
                                                <i class="bx bx-phone text-secondary me-1"></i>
                                                {{ $transaksi->phone_number }}
                                            </div>
                                        </td>
                            
                                        <td>
                                            <span class="fw-bold">{{ $transaksi->packet->name ?? 'N/A' }}</span>
                                            @if($transaksi->packet && $transaksi->packet->product)
                                                <br>
                                                <small class="text-muted">{{ $transaksi->packet->product->name }}</small>
                                            @endif
                                        </td>
                                        
                                        <td class="fw-bold" data-order="{{ $transaksi->total_price }}">
                                            <a href="javascript:void(0);" class="clickable-price" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">
                                                Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}
                                            </a>
                                        </td>
                            
                                        <td data-order="{{ $transaksi->created_at->timestamp }}">
                                            {{ $transaksi->created_at->format('d M Y, H:i') }}
                                        </td>
                                        
                                        <td>{{ $transaksi->status }}</td>
                                        <td>{{ $transaksi->process_status }}</td>
                                        
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal" data-bs-target="#detailModal{{ $transaksi->transaction_id }}">Lihat</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    {{-- MODALS FOR TABLE ACTIONS (LOOPS) --}}
    @foreach($recent_transactions as $transaksi)
        <div id="detailModal{{ $transaksi->transaction_id }}" class="modal fade invoice-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="invoice-content">
                            <div class="invoice-header text-center"><div class="mb-3"><img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="logo" class="invoice-logo"/></div><p class="text-muted mb-0">Jalan Lingkar Selatan, Sukabumi</p></div>
                            <div class="p-4">
                                <div class="row"><div class="col-md-6"><h5 class="font-size-16">Pelanggan:</h5><p class="mb-1">{{ $transaksi->customer_name }}</p><p class="mb-1 text-muted">{{ $transaksi->phone_number }}</p></div><div class="col-md-6 text-md-end"><h5 class="font-size-16">Info:</h5><p class="mb-1"><strong>Invoice:</strong> {{ $transaksi->receipt_code }}</p><p class="mb-1"><strong>Tgl:</strong> {{ $transaksi->created_at->format('d M Y, H:i') }}</p></div></div>
                                <div class="py-2 mt-3"><h3 class="font-size-15 fw-bold">Ringkasan</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap">
                                        <thead class="table-light"><tr><th>Item</th><th class="text-end">Harga</th></tr></thead>
                                        <tbody>
                                            @if($transaksi->packet)
                                                <tr>
                                                    <td>{{ $transaksi->packet->name }} <small class="text-muted">({{ $transaksi->packet->product->name ?? '' }})</small></td>
                                                    <td class="text-end">Rp {{ number_format($transaksi->packet->price, 0, ',', '.') }}</td>
                                                </tr>
                                            @endif
                                            @foreach($transaksi->additionals as $additional)
                                                <tr>
                                                    <td>{{ $additional->pivot->quantity }}x {{ $additional->name }}</td>
                                                    <td class="text-end">Rp {{ number_format($additional->pivot->price * $additional->pivot->quantity, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            <tr class="border-top"><td class="fw-bold">Total</td><td class="text-end fw-bold">Rp {{ number_format($transaksi->total_price, 0, ',', '.') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Shared Modals (DP, URL, Selection) --}}
    <div class="modal fade" id="dpAmountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="dpAmountForm" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="field" value="status"><input type="hidden" name="value" value="dp"><input type="hidden" name="redirect_to" value="dashboard">
                    <div class="modal-header"><h5 class="modal-title">Masukkan Jumlah DP</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="number" class="form-control" id="dp_amount_modal" name="dp_amount" min="0" required placeholder="Rp 0"></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="urlModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="urlForm" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" id="url_field_input" name="field" value=""><input type="hidden" name="redirect_to" value="dashboard">
                    <div class="modal-header"><h5 class="modal-title" id="urlModalLabel">Update Link</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><input type="url" class="form-control" id="url_input" name="value" placeholder="https://..."></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inputSelectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="selectionForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Input Pilihan Foto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><textarea class="form-control" id="selection_text_input" name="selection_text" rows="10" placeholder="Paste pesan WA pelanggan di sini..."></textarea></div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <!-- Libraries -->
    <script src="{{ URL::asset('/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/libs/datatables/datatables.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/libs/pdfmake/pdfmake.min.js') }}"></script>
    <!-- Init Scripts for default behavior (optional if custom below handles it) -->
    <script src="{{ URL::asset('/assets/js/pages/dashboard.init.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/pages/datatables.init.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- 1. DATATABLE CUSTOM SORTING ---
            // We destroy previous instances to ensure our custom sorting applies
            if ($.fn.DataTable.isDataTable('#datatable')) {
                $('#datatable').DataTable().destroy();
            }
            
            $('#datatable').DataTable({
                "order": [[ 4, "desc" ]], // Sort by Date (Index 4) Descending by default
                "pageLength": 10,
                "lengthChange": false, // Hide "Show 10 entries" dropdown
                "language": {
                    "search": "Cari:",
                    "paginate": { "next": ">", "previous": "<" },
                    "emptyTable": "Tidak ada data transaksi terbaru."
                }
            });


            // --- 2. INCOME FILTER LOGIC ---
            const incomeFilters = document.querySelectorAll('.income-filter');
            const incomeDisplay = document.getElementById('total-income-display');
            const incomeSortLabel = document.getElementById('income-sort-label');
            const incomeSubtext = document.getElementById('income-subtext');

            if(incomeFilters.length > 0){
                incomeFilters.forEach(filter => {
                    filter.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        const type = this.dataset.type;
                        const totalVal = parseFloat(incomeDisplay.dataset.total);
                        const monthlyVal = parseFloat(incomeDisplay.dataset.monthly);
                        
                        let displayVal = 0;
                        let labelText = "Total";
                        let subtextHtml = "";

                        if (type === 'monthly') {
                            displayVal = monthlyVal;
                            labelText = "Bulan Ini";
                            subtextHtml = '<span class="text-info me-1"><i class="mdi mdi-calendar-month me-1"></i></span> Bulan Ini';
                        } else {
                            displayVal = totalVal;
                            labelText = "Total";
                            subtextHtml = '<span class="text-success me-1"><i class="mdi mdi-chart-line me-1"></i></span> Akumulasi';
                        }

                        const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

                        incomeDisplay.textContent = formatter.format(displayVal);
                        incomeSortLabel.textContent = labelText;
                        incomeSubtext.innerHTML = subtextHtml;
                    });
                });
            }


            // --- 3. SALES CHART LOGIC ---
            const chartElement = document.querySelector("#sales-analytics-chart");
            if(chartElement) {
                const chartData = {
                    daily: { labels: @json($chart_daily_labels), data: @json($chart_daily_data) },
                    monthly: { labels: @json($chart_monthly_labels), data: @json($chart_monthly_data) },
                    yearly: { labels: @json($chart_yearly_labels), data: @json($chart_yearly_data) }
                };

                let currentChart = null;

                function renderChart(type) {
                    const data = chartData[type];
                    const options = {
                        series: [{ name: 'Pemasukan', data: data.data }],
                        chart: { height: 450, type: 'area', toolbar: { show: false }, zoom: { enabled: false } },
                        colors: ['#556ee6'],
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 2 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, inverseColors: false, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100, 100, 100] } },
                        xaxis: { categories: data.labels },
                        yaxis: { labels: { formatter: function (value) { return "Rp " + new Intl.NumberFormat('id-ID').format(value); } } },
                        grid: { borderColor: '#f1f1f1' }
                    };

                    if (currentChart) { currentChart.destroy(); }
                    currentChart = new ApexCharts(chartElement, options);
                    currentChart.render();
                }

                renderChart('daily'); // Initial Render

                document.querySelectorAll('.chart-filter').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('.chart-filter').forEach(b => {
                            b.classList.remove('active', 'btn-primary');
                            b.classList.add('btn-light');
                        });
                        this.classList.remove('btn-light');
                        this.classList.add('active', 'btn-primary');
                        renderChart(this.dataset.filter);
                    });
                });
            }


            // --- 4. MODAL LOGIC (DP, URL, SELECTION) ---
            
            // DP Modal
            const dpModalEl = document.getElementById('dpAmountModal');
            if(dpModalEl){
                const dpModal = new bootstrap.Modal(dpModalEl);
                const dpForm = document.getElementById('dpAmountForm');
                document.querySelectorAll('.payment-status-select').forEach(select => {
                    select.addEventListener('change', function (e) {
                        if (e.target.value === 'dp') {
                            dpForm.action = e.target.closest('form').action;
                            dpModal.show();
                        } else {
                            e.target.closest('form').submit();
                        }
                    });
                });
            }

            // URL Modal
            const urlModalEl = document.getElementById('urlModal');
            if(urlModalEl) {
                const urlModal = new bootstrap.Modal(urlModalEl);
                const urlForm = document.getElementById('urlForm');
                const urlInput = document.getElementById('url_input');
                const urlLabel = document.getElementById('urlModalLabel');
                const urlFieldInput = document.getElementById('url_field_input');

                document.querySelectorAll('.update-url-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const field = this.dataset.field;
                        const val = this.dataset.value;
                        
                        if (field === 'url_images') urlLabel.textContent = 'Update Link Galeri';
                        else urlLabel.textContent = 'Update Link Final';

                        urlInput.value = val;
                        urlFieldInput.value = field;
                        urlForm.action = `/transaksi/${id}/update-status`;
                        urlModal.show();
                    });
                });
            }

            // Selection Modal
            const selModalEl = document.getElementById('inputSelectionModal');
            if(selModalEl){
                const selModal = new bootstrap.Modal(selModalEl);
                document.querySelectorAll('.input-selection-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.getElementById('selection_text_input').value = this.dataset.existingText;
                        document.getElementById('selectionForm').action = `/transaksi/${this.dataset.id}/update-selections`;
                        selModal.show();
                    });
                });
            }

            // Tooltips Init
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
        });
    </script>
@endsection