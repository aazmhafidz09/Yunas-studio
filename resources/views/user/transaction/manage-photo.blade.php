@extends('layouts.master')
@section('title', 'Select Photos for Editing & Printing')

@section('css')
    <!-- Tambahkan CSS SweetAlert2 -->
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    
    <style>
        .photo-card {
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            background: #fff;
        }
        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #b4c6fc;
        }
        .photo-card.has-selection {
            border-color: #556ee6;
            background-color: #f8f9fa;
            box-shadow: 0 0 0 2px rgba(85, 110, 230, 0.2);
        }
        .image-container {
            position: relative;
            width: 100%;
            background-color: #eee;
            background-image: linear-gradient(45deg, #e0e0e0 25%, transparent 25%, transparent 75%, #e0e0e0 75%, #e0e0e0),
            linear-gradient(45deg, #e0e0e0 25%, transparent 25%, transparent 75%, #e0e0e0 75%, #e0e0e0);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        .fixed-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .selection-badges {
            min-height: 24px;
            margin-top: 8px;
            margin-bottom: 8px;
        }
        
        /* --- MODAL TEXT COLOR FIX --- */
        #selectionModal .modal-body, 
        #selectionModal .form-label, 
        #selectionModal .form-check-label, 
        #selectionModal h5,
        #selectionModal p,
        #selectionModal div {
            color: #000000 !important;
        }
        #selectionModal .form-select,
        #selectionModal .form-control {
            color: #000000;
            border-color: #ced4da;
        }
        
        /* Styling khusus untuk switch container */
        .switch-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        /* Disabled Link Style */
        a.disabled-link {
            pointer-events: none;
            cursor: default;
            color: #ccc !important;
            border-color: #eee !important;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Photo Selection', 'pagetitle' => 'Gallery'])
    @endcomponent

    <div class="container-fluid">
        <form id="photoSelectionForm" method="POST" action="{{ $formAction }}">
            @csrf
            <!-- Sticky Header -->
            <div class="row sticky-top" style="top: 70px; z-index: 999;">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body py-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div style="flex: 1; min-width: 300px;">
                                    <h5 class="mb-1 text-truncate">#{{ $transaksi->receipt_code }}</h5>
                                    <p class="text-muted mb-1">{{ $transaksi->customer_name }}</p>

                                    {{-- INFORMASI PAKET (BARU DITAMBAHKAN) --}}
                                    @if($transaksi->packet)
                                        <div class="mt-2">
                                            <div class="d-flex align-items-center flex-wrap gap-2">
                                                <span class="badge bg-primary font-size-12">{{ $transaksi->packet->name }}</span>
                                                @if($transaksi->packet->product)
                                                    <span class="text-muted small"><i class="bx bx-category me-1"></i>{{ $transaksi->packet->product->name }}</span>
                                                @endif
                                                
                                                <a class="text-primary small text-decoration-none cursor-pointer ms-2" data-bs-toggle="collapse" href="#packetDetails" role="button" aria-expanded="false">
                                                    <i class="bx bx-info-circle me-1"></i>View Details
                                                </a>
                                            </div>

                                            {{-- TABEL DETAIL PAKET (COLLAPSIBLE) --}}
                                            <div class="collapse mt-2" id="packetDetails">
                                                <div class="card border shadow-none mb-0 bg-light">
                                                    <div class="card-body p-0">
                                                        <table class="table table-sm table-striped mb-0 font-size-13">
                                                            <tbody>
                                                                {{-- Included Prints --}}
                                                                @if($transaksi->packet->printOptions->isNotEmpty())
                                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small">Included Prints</td></tr>
                                                                    @foreach($transaksi->packet->printOptions as $print)
                                                                        <tr>
                                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-printer me-2 text-secondary"></i> Cetak {{ $print->name }}</td>
                                                                            <td class="text-center py-1" width="50">x{{ $print->pivot->quantity }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif

                                                                {{-- Additional Defaults --}}
                                                                @if($transaksi->packet->additionalDefaults->isNotEmpty())
                                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-muted small">Included Items</td></tr>
                                                                    @foreach($transaksi->packet->additionalDefaults as $default)
                                                                        <tr>
                                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-check-circle me-2 text-secondary"></i> {{ $default->additional->name }}</td>
                                                                            <td class="text-center py-1" width="50">x{{ $default->quantity }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif

                                                                {{-- Paid Additionals --}}
                                                                @if($transaksi->additionals->isNotEmpty())
                                                                    <tr><td colspan="2" class="px-3 py-1 fw-bold text-warning small">Extra Add-ons</td></tr>
                                                                    @foreach($transaksi->additionals as $additional)
                                                                        <tr>
                                                                            <td class="px-3 py-1 ps-4"><i class="bx bx-plus me-2 text-warning"></i> {{ $additional->name }}</td>
                                                                            <td class="text-center py-1" width="50">x{{ $additional->pivot->quantity }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 align-self-start">
                                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary"><i class="bx bx-arrow-back"></i> Back</a>
                                    
                                    @if(auth()->user()->isAdmin() || $transaksi->status == 'sudah dibayar')
                                        <a href="{{ route('transaksi.downloadFolder', ['transaksi' => $transaksi->transaction_id, 'status' => 'RAW']) }}" class="btn btn-success">
                                            <i class="bx bx-download"></i> ZIP
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-secondary" disabled title="Payment required to download">
                                            <i class="bx bx-lock-alt"></i> ZIP
                                        </button>
                                    @endif

                                    <button type="submit" class="btn btn-primary px-4"><i class="bx bx-save me-1"></i> Submit Selections</button>
                                </div>
                            </div>
                            
                            <div class="row mt-3 pt-3 border-top text-center">
                                <div class="col-6 border-end">
                                    <small class="text-uppercase text-muted fw-bold">Edit Quota</small>
                                    <h5 class="mb-0 mt-1 text-primary"><span id="edit-count">0</span> / {{ $photoLimit }}</h5>
                                </div>
                                <div class="col-6">
                                    <small class="text-uppercase text-muted fw-bold">Print Quota</small>
                                    <div id="print-allowance-tracker" class="d-flex justify-content-center flex-wrap gap-2 mt-1">
                                        @if(empty($printAllowances))
                                            <small>-</small>
                                        @else
                                            @foreach($printAllowances as $size => $quantity)
                                                <span class="badge bg-soft-info text-info border border-info" data-size="{{ $size }}">
                                                    {{ $size }}: <span>0</span>/{{ $quantity }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($photoCount > 0)
                <div class="row g-2">
                    @foreach ($photoUrls as $index => $url)
                        @php
                            // 1. URL THUMBNAIL ($url): Digunakan untuk <img> source agar ringan
                            // 2. URL ASLI ($originalUrl): Digunakan sebagai ID Unik untuk logic database
                            $originalUrl = str_replace(['/Thumbnails/', '/Thumbnails'], ['/', ''], $url);
                            
                            // Cek Status Pembayaran untuk Tombol Download
                            $canDownload = (auth()->user()->isAdmin() || $transaksi->status == 'sudah dibayar');
                        @endphp

                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            {{-- PENTING: data-url menyimpan ID Asli, data-thumb menyimpan URL Gambar Kecil --}}
                            <div class="card photo-card h-100" data-url="{{ $originalUrl }}" data-thumb="{{ $url }}">
                                
                                <div class="image-container ratio ratio-4x3">
                                    {{-- Tampilkan Thumbnail --}}
                                    <img src="{{ $url }}" 
                                         class="card-img-top fixed-image" 
                                         alt="Img {{ $index }}" 
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='https://via.placeholder.com/400x300?text=Image+Error';">
                                </div>

                                <div class="card-body d-flex flex-column p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted text-truncate" style="max-width: 100px;" title="{{ basename($url) }}">
                                            {{ basename($url) }}
                                        </small>
                                    </div>
                                    
                                    <div class="selection-badges"></div>
                                    
                                    <div class="mt-auto d-grid gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm instant-select-btn fw-bold">
                                            Select Edit
                                        </button>
                                        <div class="btn-group btn-group-sm">
                                             {{-- PREVIEW: Menggunakan URL Thumbnail ($url) agar cepat --}}
                                             <button type="button" class="btn btn-light border preview-btn" data-src="{{ $url }}" title="Zoom">
                                                <i class="bx bx-fullscreen"></i>
                                             </button>
                                             
                                             <button type="button" class="btn btn-light border manage-selection-btn" title="Print Options">
                                                <i class="bx bx-printer"></i>
                                             </button>
                                             
                                             {{-- DOWNLOAD: Menggunakan URL Asli ($originalUrl), tapi didisable jika belum lunas --}}
                                             @if($canDownload)
                                                 <a href="{{ $originalUrl }}" class="btn btn-light border text-success" download title="Download High Res">
                                                    <i class="bx bx-download"></i>
                                                 </a>
                                             @else
                                                 <button type="button" class="btn btn-light border text-muted disabled-link" title="Payment Required to Download" disabled>
                                                    <i class="bx bx-lock-alt"></i>
                                                 </button>
                                             @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="row justify-content-center mt-5">
                    <div class="col-md-6 text-center">
                        <div class="mb-4">
                            <i class="bx bx-images text-muted display-1"></i>
                        </div>
                        <h4>No Photos Available</h4>
                        <p class="text-muted">Photos are currently being uploaded or processed. Please check back in a few minutes.</p>
                        <a href="{{ request()->fullUrl() }}" class="btn btn-primary"><i class="bx bx-refresh me-1"></i> Refresh Page</a>
                    </div>
                </div>
            @endif
        </form>
    </div>

    <!-- Selection Modal -->
    <div class="modal fade" id="selectionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2 bg-light">
                    <h5 class="modal-title fs-6 text-dark">Manage Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="img-thumbnail bg-light mb-3 d-inline-block" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                        {{-- Image src ini nanti diisi oleh JS dengan URL Thumbnail --}}
                        <img id="modalPhotoPreview" src="" class="img-fluid" style="max-height: 300px;" alt="Preview">
                    </div>
                    <input type="hidden" id="currentPhotoUrl">
                    
                    <div class="text-start px-3">
                        <!-- Layout Switch yang Diperbaiki -->
                        <div class="switch-container">
                            <label class="form-check-label fw-bold m-0" for="modalSelectForEdit" style="cursor: pointer; font-size: 1rem;">Select for Editing</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" id="modalSelectForEdit" style="width: 3em; height: 1.5em; cursor: pointer;">
                            </div>
                        </div>

                        <!-- Select Print -->
                        <div class="mb-2">
                            <label for="modalAssignToPrint" class="form-label fw-bold small text-uppercase">Print Option</label>
                            <select id="modalAssignToPrint" class="form-select text-dark fw-medium">
                                <option value="">-- Do Not Print --</option>
                                @foreach($printAllowances as $size => $quantity)
                                    <option value="{{ $size }}">{{ $size }} (Quota: {{ $quantity }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="clearSelectionBtn">Clear All</button>
                    <button type="button" class="btn btn-primary btn-sm ms-auto px-4" id="saveSelectionBtn">Apply Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fullscreen Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0 text-center position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 bg-white rounded p-2" data-bs-dismiss="modal"></button>
                    {{-- Image src ini nanti diisi oleh JS dengan URL Thumbnail --}}
                    <img id="previewModalPhoto" src="" class="img-fluid rounded shadow-lg" style="max-height: 90vh;">
                </div>
            </div>
        </div>
    </div>

    @include('partials.success-modal')
@endsection

@section('script')
    <!-- Tambahkan Library JS SweetAlert2 Disini -->
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@endsection

@section('script-bottom')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- CONFIGURATION ---
    const config = {
        photoLimit: {{ $photoLimit }},
        printAllowances: @json($printAllowances),
        initialEditSelections: @json($selectedForEdit).map(url => url.replace('/Thumbnails/', '/').replace('/Thumbnails', '')),
        initialPrintSelections: {}
    };

    const rawPrintSelections = @json($selectedForPrint);
    Object.entries(rawPrintSelections).forEach(([url, size]) => {
        const cleanUrl = url.replace('/Thumbnails/', '/').replace('/Thumbnails', '');
        config.initialPrintSelections[cleanUrl] = size;
    });

    // --- STATE ---
    let selections = {};
    let activeModalUrl = null; // Ini menyimpan ID URL Asli

    // --- ELEMENTS ---
    const selectionModal = new bootstrap.Modal(document.getElementById('selectionModal'));
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const form = document.getElementById('photoSelectionForm');
    
    const el = (id) => document.getElementById(id);

    // --- CORE LOGIC ---
    const updateDisplays = () => {
        const editCount = Object.values(selections).filter(s => s.edit).length;
        el('edit-count').textContent = editCount;
        el('edit-count').className = editCount >= config.photoLimit ? 'text-danger fw-bold' : 'text-primary';

        const printCounts = {};
        Object.values(selections).filter(s => s.print).forEach(s => {
            printCounts[s.print] = (printCounts[s.print] || 0) + 1;
        });

        document.querySelectorAll('#print-allowance-tracker span[data-size]').forEach(badge => {
            const size = badge.dataset.size;
            const limit = config.printAllowances[size];
            const current = printCounts[size] || 0;
            const counterSpan = badge.querySelector('span');
            
            if(counterSpan) counterSpan.textContent = current;

            if (current > limit) {
                badge.className = 'badge bg-danger text-white border border-danger';
            } else if (current === limit) {
                badge.className = 'badge bg-success text-white border border-success';
            } else {
                badge.className = 'badge bg-soft-info text-info border border-info';
            }
        });

        document.querySelectorAll('.photo-card').forEach(card => {
            const url = card.dataset.url;
            const selection = selections[url];
            const badges = card.querySelector('.selection-badges');
            badges.innerHTML = '';

            if (selection && (selection.edit || selection.print)) {
                card.classList.add('has-selection');
                if (selection.edit) {
                    badges.innerHTML += `<span class="badge bg-primary me-1"><i class="bx bx-edit"></i> Edit</span>`;
                }
                if (selection.print) {
                    badges.innerHTML += `<span class="badge bg-info text-white"><i class="bx bx-printer"></i> ${selection.print}</span>`;
                }
                
                const btn = card.querySelector('.instant-select-btn');
                if(selection.edit) {
                    btn.classList.replace('btn-outline-primary', 'btn-primary');
                    btn.textContent = 'Selected';
                } else {
                    btn.classList.replace('btn-primary', 'btn-outline-primary');
                    btn.textContent = 'Select Edit';
                }

            } else {
                card.classList.remove('has-selection');
                const btn = card.querySelector('.instant-select-btn');
                btn.classList.replace('btn-primary', 'btn-outline-primary');
                btn.textContent = 'Select Edit';
            }
        });
    };

    // MODAL: Menerima URL Asli (untuk ID) dan URL Thumb (untuk Display)
    const openSelectionModal = (urlAsli, urlThumb) => {
        activeModalUrl = urlAsli;
        
        // GANTI SUMBER GAMBAR MODAL KE THUMBNAIL
        el('modalPhotoPreview').src = urlThumb; 
        el('currentPhotoUrl').value = urlAsli;

        const currentSelection = selections[urlAsli] || { edit: false, print: "" };
        
        el('modalSelectForEdit').checked = currentSelection.edit;
        el('modalAssignToPrint').value = currentSelection.print || "";
        
        el('modalAssignToPrint').dataset.lastValue = currentSelection.print || "";
        el('modalSelectForEdit').dataset.initialState = currentSelection.edit ? "checked" : "unchecked";

        el('modalSelectForEdit').disabled = false;
        Array.from(el('modalAssignToPrint').options).forEach(opt => {
            opt.disabled = false;
            opt.hidden = false; 
            opt.style.display = ''; 
            
            if (opt.value) {
                const originalText = opt.getAttribute('data-original-text');
                if (originalText) opt.text = originalText;
                else opt.setAttribute('data-original-text', opt.text);
            }
        });

        const printCounts = {};
        Object.entries(selections).forEach(([key, val]) => {
            if (key !== urlAsli && val.print) {
                printCounts[val.print] = (printCounts[val.print] || 0) + 1;
            }
        });

        Array.from(el('modalAssignToPrint').options).forEach(option => {
            const size = option.value;
            if (size) { 
                const limit = config.printAllowances[size] || 0;
                const currentUsed = printCounts[size] || 0;
                
                if (!option.getAttribute('data-original-text')) {
                    option.setAttribute('data-original-text', option.text);
                }

                if (currentUsed >= limit && currentSelection.print !== size) {
                    option.hidden = true;
                    option.style.display = 'none';
                    option.disabled = true;
                } else {
                    const remaining = limit - currentUsed;
                    const baseText = option.getAttribute('data-original-text').split(' (Quota')[0];
                    option.text = `${baseText} (Sisa: ${remaining > 0 ? remaining : 0})`;
                }
            }
        });

        selectionModal.show();
    };

    // --- LOGIKA KUOTA MODAL ---
    el('modalSelectForEdit').addEventListener('change', function() {
        if (this.checked) {
            const editCount = Object.values(selections).filter(s => s.edit).length;
            const isAlreadySelectedInGlobal = selections[activeModalUrl] && selections[activeModalUrl].edit;

            if (!isAlreadySelectedInGlobal && editCount >= config.photoLimit) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Edit Quota Reached!',
                    text: `You have reached the limit of ${config.photoLimit} photos for editing.`,
                    confirmButtonColor: '#556ee6'
                });
                this.checked = false;
            }
        }
    });

    const saveModalSelection = () => {
        if (!activeModalUrl) return;
        
        const isEdit = el('modalSelectForEdit').checked;
        const printVal = el('modalAssignToPrint').value;

        if (!isEdit && !printVal) {
            delete selections[activeModalUrl];
        } else {
            selections[activeModalUrl] = { edit: isEdit, print: printVal };
        }

        renderHiddenInputs();
        updateDisplays();
        selectionModal.hide();
    };

    const renderHiddenInputs = () => {
        form.querySelectorAll('input[type="hidden"].selection-data').forEach(e => e.remove());
        
        Object.entries(selections).forEach(([url, data]) => {
            if(data.edit) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.className = 'selection-data';
                input.name = `selections[${url}][edit]`;
                input.value = '1';
                form.appendChild(input);
            }
            if(data.print) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.className = 'selection-data';
                input.name = `selections[${url}][print]`;
                input.value = data.print;
                form.appendChild(input);
            }
        });
    };

    // --- INITIALIZATION ---
    config.initialEditSelections.forEach(url => {
        selections[url] = { ...selections[url], edit: true };
    });
    Object.entries(config.initialPrintSelections).forEach(([url, size]) => {
        selections[url] = { ...selections[url], print: size };
    });
    
    renderHiddenInputs();
    updateDisplays();

    // --- EVENT LISTENERS ---
    
    document.querySelectorAll('.instant-select-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const card = e.target.closest('.photo-card');
            const url = card.dataset.url;
            
            const current = selections[url] || { edit: false, print: "" };
            const editCount = Object.values(selections).filter(s => s.edit).length;

            if (!current.edit && editCount >= config.photoLimit) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Edit Quota Reached',
                    text: `You can only select up to ${config.photoLimit} photos for editing.`,
                    confirmButtonColor: '#556ee6'
                });
                return;
            }

            current.edit = !current.edit;
            
            if (!current.edit && !current.print) {
                delete selections[url];
            } else {
                selections[url] = current;
            }

            renderHiddenInputs();
            updateDisplays();
        });
    });

    // Modal Trigger
    document.querySelectorAll('.manage-selection-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const card = e.target.closest('.photo-card');
            const urlAsli = card.dataset.url;
            const urlThumb = card.dataset.thumb; // Ambil URL Thumbnail dari atribut
            openSelectionModal(urlAsli, urlThumb); // Kirim 2 parameter
        });
    });

    // Preview Trigger (Pake Thumbnail URL)
    document.querySelectorAll('.preview-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const urlThumb = e.currentTarget.dataset.src; // Ini sudah thumbnail dari Blade
            el('previewModalPhoto').src = urlThumb;
            previewModal.show();
        });
    });

    el('saveSelectionBtn').addEventListener('click', saveModalSelection);
    
    el('clearSelectionBtn').addEventListener('click', () => {
        if (activeModalUrl && selections[activeModalUrl]) {
            delete selections[activeModalUrl];
            renderHiddenInputs();
            updateDisplays();
        }
        selectionModal.hide();
    });

    // --- LOGIKA VALIDASI SUBMIT SELEKSI ---
    form.addEventListener('submit', function(e) {
        e.preventDefault(); 

        const editCount = Object.values(selections).filter(s => s.edit).length;
        const editRemaining = config.photoLimit - editCount;

        const printCounts = {};
        Object.values(selections).filter(s => s.print).forEach(s => {
            printCounts[s.print] = (printCounts[s.print] || 0) + 1;
        });

        let warningMessage = "";
        
        if (editRemaining > 0) {
            warningMessage += `<li>Edit: <b>${editRemaining}</b> foto belum dipilih</li>`;
        }

        Object.entries(config.printAllowances).forEach(([size, limit]) => {
            const current = printCounts[size] || 0;
            const remaining = limit - current;
            if (remaining > 0) {
                warningMessage += `<li>Cetak ${size}: <b>${remaining}</b> foto belum dipilih</li>`;
            }
        });

        if (warningMessage) {
            Swal.fire({
                title: 'Kuota Belum Terpenuhi',
                html: `
                    <p class="text-muted">Anda masih memiliki sisa kuota:</p>
                    <ul class="text-start text-dark" style="list-style-position: inside; font-size: 0.95rem;">${warningMessage}</ul>
                    <p class="mt-3">Apakah Anda yakin ingin menyimpan sekarang?</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#34c38f',
                cancelButtonColor: '#f46a6a',
                confirmButtonText: 'Ya, Simpan Saja',
                cancelButtonText: 'Batal, Pilih Lagi'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else {
            Swal.fire({
                title: 'Simpan Pilihan?',
                text: "Pastikan pilihan Anda sudah benar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                confirmButtonColor: '#556ee6'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });

    @if (session('success_message'))
        new bootstrap.Modal(document.getElementById('successModal')).show();
    @endif
});
</script>
@endsection