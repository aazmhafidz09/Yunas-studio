{{--
    =====================================================================
    PRODUCT DETAIL MODAL — Universal (Mobile & Desktop)
    =====================================================================
    Cara pakai:
    1. @include('userPage.modal.product-detail-modal') di index.blade.php
    2. Tombol trigger: onclick="showProductDetail(ID_PRODUK)"
    3. Pastikan $packets dan $products tersedia dari controller

    UPDATE:
    - Tambah tombol sort harga (Tertinggi -> Rendah & sebaliknya)
    =====================================================================
--}}

{{-- ===== MODAL ===== --}}
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 overflow-hidden" style="border-radius: 20px;">

            {{-- Hero Header --}}
            <div class="position-relative d-flex align-items-end p-4"
                 style="min-height: 140px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);">
                <button type="button"
                        class="btn-close btn-close-white position-absolute top-0 end-0 m-3"
                        data-bs-dismiss="modal"></button>
                <div>
                    <span class="badge rounded-pill mb-2 px-3 py-2"
                          style="background: rgba(255,255,255,0.15);
                                 border: 0.5px solid rgba(255,255,255,0.2);
                                 font-size: 0.65rem; letter-spacing: 0.08em;
                                 color: rgba(255,255,255,0.85);">
                        Paket Studio
                    </span>
                    <h3 class="text-white fw-semibold mb-0" id="modal-product-name">-</h3>
                </div>
            </div>

            {{-- Body --}}
            <div class="modal-body p-4">
                <p class="text-muted small mb-4" id="modal-product-desc">-</p>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <p class="text-uppercase fw-semibold mb-0"
                       style="font-size: 0.65rem; letter-spacing: 0.1em; color: #999;">
                        Pilih Paket
                    </p>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Urutkan paket">
                        <button type="button"
                                class="btn btn-outline-secondary sort-btn"
                                id="sort-desc-btn"
                                onclick="sortPackets('desc')">
                            ↓ Termahal
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary sort-btn"
                                id="sort-asc-btn"
                                onclick="sortPackets('asc')">
                            ↑ Termurah
                        </button>
                    </div>
                </div>

                <div id="modal-packets-list">
                    {{-- Diisi JavaScript --}}
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">
                    Tutup
                </button>
                <button type="button"
                        class="btn flex-grow-1 rounded-pill fw-semibold"
                        id="modal-book-btn"
                        style="background: #0f3460; color: white;"
                        onclick="goToBooking()"
                        disabled>
                    Pilih paket dulu
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ===== CSS ===== --}}
<style>
    .packet-item {
        border: 1.5px solid #e9ecef;
        border-radius: 14px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 12px;
        position: relative;
    }
    .packet-item:hover {
        border-color: #0f3460;
        background: #f8f9ff;
    }
    .packet-item.selected {
        border-color: #0f3460;
        background: #f0f4ff;
    }
    .packet-item.selected .packet-check-icon {
        opacity: 1;
    }
    .packet-check-icon {
        opacity: 0;
        transition: opacity 0.15s;
        width: 22px; height: 22px;
        border-radius: 50%;
        background: #0f3460;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .packet-price-badge {
        background: #0f3460;
        color: white;
        border-radius: 20px;
        padding: 4px 14px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }
    .packet-feature-list {
        list-style: none;
        padding: 0; margin: 10px 0 0;
    }
    .packet-feature-list li {
        font-size: 12px; color: #6c757d;
        padding: 2px 0;
        display: flex; align-items: flex-start; gap: 6px;
    }
    .packet-feature-list li::before {
        content: '✓';
        color: #0f3460;
        font-weight: 700;
        flex-shrink: 0;
    }
    .packet-tier-badge {
        font-size: 0.6rem; font-weight: 600;
        padding: 3px 10px; border-radius: 20px;
        letter-spacing: 0.05em;
    }

    /* ── Sort buttons ─────────────────────────────────────────────── */
    .sort-btn {
        font-size: 11px;
        font-weight: 600;
        border-radius: 20px !important;
        padding: 4px 12px;
        border-color: #dee2e6;
        color: #6c757d;
    }
    .sort-btn.active {
        background: #0f3460;
        border-color: #0f3460;
        color: #fff;
    }
</style>

<script>
(function () {
    'use strict';

    const allPackets  = {!! json_encode($packets) !!};
    const allProducts = @json($products->toArray());

    // State yang akan dikirim ke booking modal
    let selectedProductId  = null;
    let selectedPacketId   = null;
    let selectedPacketName = null;

    // State list paket yang sedang tampil & urutan sort aktif
    let currentPacketList = [];
    let currentSortOrder  = null; // null | 'asc' | 'desc'

    const TIER_STYLES = {
        'Bronze':   { bg: '#f5ede0', color: '#7d4f1a' },
        'Silver':   { bg: '#eef0f5', color: '#4a5568' },
        'Gold':     { bg: '#fef6d9', color: '#7a5c00' },
        'Platinum': { bg: '#e8f0fe', color: '#1a4494' },
        'Diamond':  { bg: '#eef7f5', color: '#0e5a46' },
    };

    const formatRp = (n) =>
        new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', minimumFractionDigits: 0
        }).format(n);

    // ── Render daftar paket ke DOM ────────────────────────────────────
    function renderPacketList(packetList) {
        const list = document.getElementById('modal-packets-list');
        const bookBtn = document.getElementById('modal-book-btn');
        list.innerHTML = '';

        if (!packetList || packetList.length === 0) {
            list.innerHTML = '<p class="text-muted text-center py-3">Belum ada paket tersedia.</p>';
            return;
        }

        packetList.forEach(packet => {
            const tier  = TIER_STYLES[packet.name] ?? { bg: '#eef0f5', color: '#4a5568' };
            const lines = (packet.description ?? '').split('\n').filter(l => l.trim());

            const item = document.createElement('div');
            item.className      = 'packet-item';
            item.dataset.id     = packet.id;
            item.dataset.name   = packet.name;
            item.dataset.price  = packet.price;

            // Pertahankan status selected saat re-render (misal setelah sort)
            if (String(packet.id) === String(selectedPacketId)) {
                item.classList.add('selected');
            }

            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="fw-bold" style="font-size:15px;">${packet.name}</div>
                        <span class="packet-tier-badge"
                              style="background:${tier.bg}; color:${tier.color};">
                            ${packet.name}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="packet-price-badge">${formatRp(packet.price)}</span>
                        <div class="packet-check-icon">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                                <path d="M2 6l3 3 5-5" stroke="white" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
                ${lines.length ? `
                    <ul class="packet-feature-list mt-2">
                        ${lines.map(l => `<li>${l.trim()}</li>`).join('')}
                    </ul>` : ''}
                <div class="mt-2 text-muted" style="font-size:11px;">
                    Max ${packet.max_photos_for_edit} foto editing
                </div>
            `;

            item.addEventListener('click', function () {
                // Deselect semua, select yang diklik
                list.querySelectorAll('.packet-item').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');

                selectedPacketId   = this.dataset.id;
                selectedPacketName = this.dataset.name;
                const harga        = formatRp(this.dataset.price);

                // Aktifkan tombol booking
                bookBtn.disabled    = false;
                bookBtn.textContent = `Booking — ${selectedPacketName} · ${harga}`;
            });

            list.appendChild(item);
        });
    }

    // ── Urutkan paket berdasarkan harga ───────────────────────────────
    window.sortPackets = function (order) {
        if (!currentPacketList.length) return;

        const sorted = [...currentPacketList].sort((a, b) =>
            order === 'asc' ? a.price - b.price : b.price - a.price
        );

        currentPacketList = sorted;
        currentSortOrder  = order;

        // Update tampilan tombol aktif
        document.getElementById('sort-asc-btn').classList.toggle('active', order === 'asc');
        document.getElementById('sort-desc-btn').classList.toggle('active', order === 'desc');

        renderPacketList(sorted);
    };

    // ── Buka modal detail produk ─────────────────────────────────────
    window.showProductDetail = function (productId) {

        // Reset state
        selectedProductId  = productId;
        selectedPacketId   = null;
        selectedPacketName = null;
        currentSortOrder   = null;

        // Cari data produk & paket
        let productName = null;
        let packetList  = null;

        // Cari dari allPackets (grouped by product name)
        for (const [name, group] of Object.entries(allPackets)) {
            if (String(group[0].product_id) === String(productId)) {
                productName = name;
                packetList  = group;
                break;
            }
        }

        // Fallback: cari dari allProducts (with eager loaded packets)
        if (!packetList) {
            const product = allProducts.find(p => p.id == productId);
            if (product) {
                productName = product.name;
                packetList  = product.packets ?? [];
            }
        }

        if (!packetList) return;

        // Isi header modal
        document.getElementById('modal-product-name').textContent = productName;

        // Isi deskripsi produk
        const product = allProducts.find(p => p.id == productId);
        document.getElementById('modal-product-desc').textContent =
            product?.description ?? '';

        // Reset tombol booking
        const bookBtn = document.getElementById('modal-book-btn');
        bookBtn.disabled    = true;
        bookBtn.textContent = 'Pilih paket dulu';

        // Reset tombol sort (belum ada urutan aktif = urutan asli)
        document.getElementById('sort-asc-btn').classList.remove('active');
        document.getElementById('sort-desc-btn').classList.remove('active');

        // Simpan list saat ini (urutan asli dari server) & render
        currentPacketList = [...packetList];
        renderPacketList(currentPacketList);

        // Buka modal
        const modalEl = document.getElementById('productDetailModal');
        const modal   = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    };

    // ── Pindah ke booking modal dengan data terpreselect ────────────
    window.goToBooking = function () {
        if (!selectedPacketId) return;

        // Tutup modal detail
        const detailModalEl = document.getElementById('productDetailModal');
        const detailModal   = bootstrap.Modal.getInstance(detailModalEl);
        detailModal?.hide();

        // Setelah modal detail selesai tutup, buka booking modal
        detailModalEl.addEventListener('hidden.bs.modal', function handler() {
            this.removeEventListener('hidden.bs.modal', handler);

            const bookingModalEl = document.getElementById('bookingModal');
            const bookingModal   = new bootstrap.Modal(bookingModalEl);
            bookingModal.show();

            // Setelah booking modal terbuka, preselect & langsung ke step 1
            bookingModalEl.addEventListener('shown.bs.modal', function onShown() {
                this.removeEventListener('shown.bs.modal', onShown);

                // Preselect produk & paket di background
                if (typeof bookingPreselect === 'function') {
                    bookingPreselect(selectedProductId, selectedPacketId);
                }

                // Mulai dari step 1 (isi biodata dulu)
                if (typeof bookingNextStep === 'function') {
                    bookingNextStep(1);
                }
            });
        });
    };

})();
</script>