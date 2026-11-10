{{-- ─────────────────────────────────────────────────────────────────────
MODAL DETAIL PRODUK
───────────────────────────────────────────────────────────────────── --}}
<div class="modal fade"  tabindex="-1" aria-labelledby="productDetailTitleDesktop" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">

            {{-- HEADER --}}
            <div class="modal-header border-0 pb-0 px-4 pt-4">

                <div>
                    <h4 class="modal-title fw-bold mb-0" id="productDetailTitle">
                        <i class="mdi mdi-camera-outline text-success me-2"></i>
                        <span id="productDetailName">Detail Produk</span>
                    </h4>

                    <p class="text-muted small mb-0 mt-1">
                        Pilih paket terbaik yang sesuai dengan kebutuhan Anda
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    onclick="this.blur()">
                </button>

            </div>

            {{-- BODY --}}
            <div class="modal-body px-4 py-4">

                <div class="row g-3 justify-content-center" id="productDetailBody">

                    {{-- CARD DINAMIS --}}
                </div>

            </div>

            {{-- FOOTER --}}
            <div class="modal-footer border-0 pt-0 px-4 pb-4">

                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="button" class="btn btn-success rounded-pill px-5 py-2 fw-semibold"
                    id="productDetailBookBtn" onclick="goToBooking()" disabled>

                    <i class="mdi mdi-calendar-check-outline me-1"></i>
                    Pilih Paket Terlebih Dahulu

                </button>

            </div>

        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────────────────────────────
CSS
───────────────────────────────────────────────────────────────────── --}}
<style>
    body.modal-open {
        overflow: hidden !important;
        padding-right: 0 !important;
    }

    /* CARD */
    .packet-desktop-card {
        background: #ffffff;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        padding: 24px 20px;
        cursor: pointer;
        transition: all 0.25s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
    }

    .packet-desktop-card:hover {
        transform: translateY(-4px);
        border-color: #198754;
        box-shadow: 0 10px 20px rgba(0, 0, 0, .05);
    }

    .packet-desktop-card.selected {
        border-color: #198754;
        background: #f0fdf4;
        box-shadow: 0 4px 15px rgba(25, 135, 84, .1);
    }

    .packet-desktop-card.selected::after {
        content: '\F012C';
        font-family: 'Material Design Icons';
        position: absolute;
        top: 12px;
        right: 14px;
        color: #198754;
        font-size: 24px;
    }

    .packet-price-text {
        font-size: 1.35rem;
        font-weight: 700;
        color: #198754;
    }

    .packet-feature-list {
        list-style: none;
        padding: 0;
        margin: 16px 0 0;
    }

    .packet-feature-list li {
        font-size: 13px;
        color: #495057;
        padding: 4px 0;
        text-align: left;
    }

    .packet-feature-list li::before {
        content: '✓ ';
        color: #198754;
        font-weight: bold;
        margin-right: 6px;
    }
</style>

{{-- ─────────────────────────────────────────────────────────────────────
JAVASCRIPT
───────────────────────────────────────────────────────────────────── --}}
<script>

    (function () {

        'use strict';

        // DATA DARI BACKEND
        const allPackets = {!! json_encode($packets) !!};

        // STATE
        let selectedProductId = null;
        let selectedProductName = null;
        let selectedPacketId = null;

        // FORMAT RUPIAH
        const formatRp = (n) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(n);
        };

        // ─────────────────────────────────────────────────────────
        // SHOW DETAIL MODAL
        // ─────────────────────────────────────────────────────────
        window.showProductDetail = function (productId) {

            let productName = null;
            let packetList = null;

            // CARI PRODUK
            for (const [name, group] of Object.entries(allPackets)) {

                if (
                    group.length > 0 &&
                    String(group[0].product_id) === String(productId)
                ) {
                    productName = name;
                    packetList = group;
                    break;
                }
            }

            if (!packetList) return;

            // RESET STATE
            selectedProductId = productId;
            selectedProductName = productName;
            selectedPacketId = null;

            // SET TITLE
            document.getElementById('productDetailName').textContent = productName;

            // BUTTON
            const bookBtn = document.getElementById('productDetailBookBtn');

            bookBtn.disabled = true;

            bookBtn.innerHTML = `
            <i class="mdi mdi-calendar-check-outline me-1"></i>
            Pilih Paket Terlebih Dahulu
        `;

            // BODY
            const body = document.getElementById('productDetailBody');

            body.innerHTML = '';

            // EMPTY
            if (packetList.length === 0) {

                body.innerHTML = `
                <div class="col-12">
                    <p class="text-muted text-center py-4">
                        Belum ada paket tersedia.
                    </p>
                </div>
            `;

            } else {

                const colClass = packetList.length === 2
                    ? 'col-md-6'
                    : 'col-md-4';

                packetList.forEach(packet => {

                    const colWrapper = document.createElement('div');

                    colWrapper.className = `
                    ${colClass}
                    col-sm-6
                    d-flex
                    align-items-stretch
                `;

                    colWrapper.innerHTML = `
                    <div class="packet-desktop-card w-100 text-center"
                         data-id="${packet.id}"
                         data-name="${packet.name}">

                        <div>

                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill text-uppercase mb-2"
                                  style="font-size:11px;letter-spacing:.05em;">

                                ${packet.name}

                            </span>

                            <div class="packet-price-text my-2">
                                ${formatRp(packet.price)}
                            </div>

                            <hr class="text-muted opacity-25 my-3">

                            ${packet.description
                            ? `
                                    <ul class="packet-feature-list">

                                        ${packet.description
                                .split('\n')
                                .filter(l => l.trim())
                                .map(l => `<li>${l.trim()}</li>`)
                                .join('')
                            }

                                    </ul>
                                `
                            : `
                                    <p class="text-muted small mt-3">
                                        Fitur standar studio foto.
                                    </p>
                                `
                        }

                        </div>

                        <div class="mt-4 pt-2">

                            <button type="button"
                                    class="btn btn-sm btn-outline-success rounded-pill px-4 card-select-indicator-btn w-100">

                                Pilih Paket

                            </button>

                        </div>

                    </div>
                `;

                    const card = colWrapper.querySelector('.packet-desktop-card');

                    // CLICK CARD
                    card.addEventListener('click', function () {

                        // RESET SEMUA CARD
                        body.querySelectorAll('.packet-desktop-card').forEach(c => {

                            c.classList.remove('selected');

                            const btn = c.querySelector('.card-select-indicator-btn');

                            if (btn) {

                                btn.className =
                                    'btn btn-sm btn-outline-success rounded-pill px-4 card-select-indicator-btn w-100';

                                btn.textContent = 'Pilih Paket';
                            }
                        });

                        // SELECT CARD
                        this.classList.add('selected');

                        const currentBtn = this.querySelector('.card-select-indicator-btn');

                        currentBtn.className =
                            'btn btn-sm btn-success rounded-pill px-4 card-select-indicator-btn w-100';

                        currentBtn.textContent = 'Terpilih ✓';

                        // SAVE STATE
                        selectedPacketId = this.dataset.id;

                        // ENABLE BUTTON
                        bookBtn.disabled = false;

                        bookBtn.innerHTML = `
                        <i class="mdi mdi-calendar-check-outline me-1"></i>
                        Lanjutkan Booking — ${this.dataset.name}
                    `;
                    });

                    body.appendChild(colWrapper);
                });
            }

            // SHOW MODAL
            const modalEl = document.getElementById('productDetailModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                console.error("Elemen productDetailModal tidak ditemukan di DOM!");
            }
        };

        // ─────────────────────────────────────────────────────────
        // GO TO BOOKING
        // ─────────────────────────────────────────────────────────
        window.goToBooking = function () {

            if (!selectedProductId) return;

            const detailModalEl =
                document.getElementById('productDetailModal');

            const detailModal =
                bootstrap.Modal.getInstance(detailModalEl);

            if (!detailModal) return;

            // HILANGKAN FOCUS
            if (document.activeElement) {
                document.activeElement.blur();
            }

            // TUNGGU MODAL BENAR-BENAR TERTUTUP
            detailModalEl.addEventListener('hidden.bs.modal', function handler() {

                detailModalEl.removeEventListener(
                    'hidden.bs.modal',
                    handler
                );

                // CLEANUP BACKDROP
                document.querySelectorAll('.modal-backdrop').forEach(el => {
                    el.remove();
                });

                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                // OPEN BOOKING MODAL
                const bookingModalEl =
                    document.getElementById('bookingModal');

                const bookingModal =
                    bootstrap.Modal.getOrCreateInstance(bookingModalEl);

                bookingModal.show();

                // SET DATA SAAT MODAL SUDAH TERBUKA
                bookingModalEl.addEventListener('shown.bs.modal', function onShown() {

                    bookingModalEl.removeEventListener(
                        'shown.bs.modal',
                        onShown
                    );

                    // PRODUCT
                    const productSelect =
                        document.getElementById('b_product_id');

                    if (productSelect) {

                        productSelect.value = selectedProductId;

                        productSelect.dispatchEvent(
                            new Event('change')
                        );
                    }

                    // PACKET
                    if (selectedPacketId) {

                        setTimeout(() => {

                            const packetSelect =
                                document.getElementById('b_packet_id');

                            if (packetSelect) {

                                packetSelect.value = selectedPacketId;

                                packetSelect.dispatchEvent(
                                    new Event('change')
                                );
                            }

                        }, 300);
                    }

                    // NEXT STEP
                    if (typeof window.bookingNextStep === 'function') {
                        window.bookingNextStep(2);
                    }

                });

            }, { once: true });

            // HIDE MODAL
            detailModal.hide();
        };

    })();
</script>