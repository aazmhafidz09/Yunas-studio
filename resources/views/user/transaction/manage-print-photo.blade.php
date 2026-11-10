@extends('layouts.master')
@section('title', 'Select Photos for Printing')

@section('css')
    <style>
        .photo-card {
            border: 2px solid transparent;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .photo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .photo-card.selected {
            border-color: #34c38f;
            box-shadow: 0 0 0 0.2rem rgba(52, 195, 143, 0.25);
        }
        .image-container {
            overflow: hidden;
            border-top-left-radius: calc(0.5rem - 2px);
            border-top-right-radius: calc(0.5rem - 2px);
            background-color: #f1f5f7;
            position: relative;
        }
        .fixed-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }
        .photo-card:hover .fixed-image {
            transform: scale(1.05);
        }
        .print-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            display: none;
        }
        .photo-card.selected .print-badge {
            display: block;
        }
        /* Style untuk opsi yang disabled */
        option:disabled {
            color: #d1d1d1;
            font-style: italic;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb', ['title' => 'Print Selection', 'pagetitle' => 'Transaction'])
    @endcomponent

    <div class="container-fluid">
        <form action="{{ $formAction }}" method="POST" id="printForm">
            @csrf
            
            <!-- Sticky Info Bar -->
            <div class="row mb-4" style="position: sticky; top: 70px; z-index: 100;">
                <div class="col-12">
                    <div class="card shadow-sm border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <h5 class="mb-1 text-primary"><i class="bx bx-printer me-2"></i>Print Quota Tracker</h5>
                                    <div id="allowance-tracker" class="d-flex flex-wrap gap-3 mt-2">
                                        @foreach($printAllowances as $size => $qty)
                                            <div class="badge bg-soft-primary text-primary p-2 border border-primary allowance-item" data-size="{{ $size }}" data-limit="{{ $qty }}">
                                                {{ $size }}: <span class="current-count fw-bold">0</span> / {{ $qty }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('transaksi.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-success"><i class="bx bx-check-circle me-1"></i> Save Selection</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                @forelse($photoUrls as $index => $url)
                    @php
                        $originalUrl = str_replace(['/Thumbnails/', '/Thumbnails'], ['/', ''], $url);
                        $existingSelection = $selectedForPrint->firstWhere('file_url', $originalUrl);
                        $currentSize = $existingSelection ? $existingSelection->print_size : null;
                    @endphp

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card photo-card h-100 {{ $currentSize ? 'selected' : '' }}" id="card-{{ $index }}">
                            <div class="position-relative">
                                <span class="badge bg-success print-badge"><i class="bx bx-check"></i> Selected</span>
                                <div class="image-container ratio ratio-4x3 cursor-pointer" onclick="openPreview('{{ $originalUrl }}')">
                                    <img src="{{ $url }}" class="card-img-top fixed-image" alt="Photo" loading="lazy">
                                </div>
                            </div>
                            
                            <div class="card-body p-2 d-flex flex-column">
                                <div class="mb-2">
                                    <select name="selected_photos[{{ $originalUrl }}]" class="form-select form-select-sm print-selector" data-card-id="card-{{ $index }}">
                                        <option value="">-- Skip --</option>
                                        @foreach($printAllowances as $size => $qty)
                                            <option value="{{ $size }}" {{ $currentSize == $size ? 'selected' : '' }}>
                                                Cetak {{ $size }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-info w-100 mt-auto" onclick="openPreview('{{ $originalUrl }}')">
                                    <i class="bx bx-zoom-in"></i> Preview
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning text-center">No photos available for printing selection yet.</div>
                    </div>
                @endforelse
            </div>
        </form>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script-bottom')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const trackers = document.querySelectorAll('.allowance-item');
        const selectors = document.querySelectorAll('.print-selector');

        function updateState() {
            // 1. Hitung Total Penggunaan
            let counts = {};
            trackers.forEach(t => counts[t.dataset.size] = 0);

            selectors.forEach(sel => {
                if(sel.value) {
                    counts[sel.value] = (counts[sel.value] || 0) + 1;
                    document.getElementById(sel.dataset.cardId).classList.add('selected');
                } else {
                    document.getElementById(sel.dataset.cardId).classList.remove('selected');
                }
            });

            // 2. Update Badges di Header
            trackers.forEach(t => {
                const size = t.dataset.size;
                const limit = parseInt(t.dataset.limit);
                const current = counts[size] || 0;
                
                t.querySelector('.current-count').innerText = current;

                if(current > limit) {
                    t.className = 'badge bg-danger text-white p-2 border border-danger allowance-item';
                } else if (current === limit) {
                    t.className = 'badge bg-success text-white p-2 border border-success allowance-item';
                } else {
                    t.className = 'badge bg-soft-primary text-primary p-2 border border-primary allowance-item';
                }
            });

            // 3. LOGIKA CERDAS: Sembunyikan/Disable Opsi yang Penuh di Dropdown Lain
            selectors.forEach(sel => {
                const myValue = sel.value; // Nilai yang dipilih dropdown ini

                Array.from(sel.options).forEach(opt => {
                    const size = opt.value;
                    if (!size) return; // Skip placeholder

                    // Cari limit dari elemen tracker
                    const tracker = document.querySelector(`.allowance-item[data-size="${size}"]`);
                    const limit = parseInt(tracker ? tracker.dataset.limit : 0);
                    
                    const totalUsed = counts[size] || 0;
                    
                    // Hitung penggunaan oleh ORANG LAIN (selain dropdown ini)
                    // Rumus: Total - (1 jika saya pakai, 0 jika tidak)
                    const usedByOthers = totalUsed - (myValue === size ? 1 : 0);

                    // Jika orang lain sudah menghabiskan kuota...
                    if (usedByOthers >= limit) {
                        // ...dan saya TIDAK sedang memilihnya -> Sembunyikan
                        if (myValue !== size) {
                            opt.hidden = true;
                            opt.style.display = 'none';
                            opt.disabled = true;
                            opt.text = `${size} (FULL)`;
                        } else {
                            // Saya sedang memilihnya -> Biarkan tetap terlihat
                            opt.hidden = false;
                            opt.style.display = '';
                            opt.disabled = false;
                            opt.text = `${size}`;
                        }
                    } else {
                        // Belum penuh -> Tampilkan
                        const remaining = limit - usedByOthers;
                        opt.hidden = false;
                        opt.style.display = '';
                        opt.disabled = false;
                        // Opsional: Tampilkan sisa
                        // opt.text = `${size} [Sisa: ${remaining}]`;
                        opt.text = size; // Kembalikan text normal
                    }
                });
            });
        }

        selectors.forEach(sel => {
            sel.addEventListener('change', updateState);
        });

        // Jalankan saat pertama kali load
        updateState();

        // --- PREVIEW MODAL ---
        window.openPreview = function(url) {
            document.getElementById('previewImage').src = url;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        }
    });
</script>
@endsection