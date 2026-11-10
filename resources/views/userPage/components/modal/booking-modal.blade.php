{{--
=====================================================================
BOOKING MODAL — Versi dengan fitur DURASI PAKET + TAMBAHAN EKSTRA
=====================================================================
Perubahan dari versi sebelumnya:
1. bookingUpdatePackets → tambah dataset.duration di option paket
2. bookingUpdatePrice → refresh slot saat paket diganti
3. fetchAndRenderSlots → kirim packet_id ke /booking/available-slots
4. Tambahan Ekstra (additional) sekarang benar-benar terhubung ke:
- Perhitungan getTotalPrice() (paket + tambahan)
- Validasi & tampilan DP
- Ringkasan harga step 2
- Ringkasan konfirmasi step 3
- Payload yang dikirim ke /booking/snap-token
=====================================================================
--}}

<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">

    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bm-card">

            {{-- ── Header ────────────────────────────────────────────── --}}
            <div class="bm-header">
                <div class="bm-header-inner">
                    <div class="bm-icon-wrap">
                        <i class="mdi mdi-camera-outline"></i>
                    </div>
                    <div>
                        <h5 class="bm-title">Booking Sesi Foto</h5>
                        <p class="bm-subtitle" id="booking-modal-subtitle">
                            Isi data di bawah untuk melakukan booking sesi foto
                        </p>
                    </div>
                </div>
                <button type="button" class="bm-close-btn" data-bs-dismiss="modal" id="bookingCloseBtn">
                    <i class="mdi mdi-close"></i>
                </button>
            </div>

            {{-- ── Step Indicator ────────────────────────────────────── --}}
            <div class="bm-steps-wrap">
                <div class="bm-steps">
                    <div class="booking-step active" id="step-indicator-1">
                        <span class="step-number">1</span>
                        <span class="step-label">Data Diri</span>
                    </div>
                    <div class="step-line flex-grow-1"></div>
                    <div class="booking-step" id="step-indicator-2">
                        <span class="step-number">2</span>
                        <span class="step-label">Pilih Paket</span>
                    </div>
                    <div class="step-line flex-grow-1"></div>
                    <div class="booking-step" id="step-indicator-3">
                        <span class="step-number">3</span>
                        <span class="step-label">Konfirmasi</span>
                    </div>
                </div>
            </div>

            {{-- ── Body ──────────────────────────────────────────────── --}}
            <div class="modal-body bm-body" id="booking-modal-body">

                {{-- ════════════ STEP 1 : Data Diri ════════════ --}}
                <div id="booking-step-1">

                    <div id="booking-preselect-banner" class="d-none mb-4">
                        <div class="bm-banner">
                            <div class="bm-banner-icon">
                                <i class="mdi mdi-check"></i>
                            </div>
                            <div>
                                <p class="bm-banner-title">Paket sudah dipilih</p>
                                <p class="bm-banner-sub" id="banner-packet-info">-</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bm-label">
                                Nama Lengkap <span class="bm-required">*</span>
                            </label>
                            <input type="text" class="bm-input" id="b_customer_name"
                                placeholder="Masukkan nama lengkap Anda" autocomplete="name">
                            <div class="invalid-feedback d-none" id="err_customer_name">
                                Nama tidak boleh kosong.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="bm-label">
                                Nomor WhatsApp <span class="bm-required">*</span>
                            </label>
                            <div class="bm-input-group">
                                <span class="bm-input-prefix">
                                    <i class="mdi mdi-whatsapp"></i>
                                </span>
                                <input type="tel" class="bm-input bm-input-suffix" id="b_phone_number"
                                    placeholder="08xxxxxxxxxx" autocomplete="tel">
                            </div>
                            <div class="invalid-feedback d-none" id="err_phone_number">
                                Nomor tidak valid. Gunakan format 08xxxxxxxxxx.
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="bm-btn-primary" onclick="bookingNextStep(2)">
                            Lanjut Pilih Paket <i class="mdi mdi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
                {{-- /STEP 1 --}}

                {{-- ════════════ STEP 2 : Pilih Paket & Jadwal ════════════ --}}
                <div id="booking-step-2" class="d-none">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="bm-label">
                                Produk <span class="bm-required">*</span>
                            </label>
                            <select class="bm-select" id="b_product_id" onchange="bookingUpdatePackets()">
                                <option value="" disabled selected>-- Pilih Produk --</option>
                                @foreach($packets as $productName => $packetGroup)
                                    <option value="{{ $packetGroup->first()->product_id }}"
                                        data-product-name="{{ $productName }}">
                                        {{ $productName }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-none" id="err_product">
                                Pilih produk terlebih dahulu.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="bm-label">
                                Paket <span class="bm-required">*</span>
                            </label>
                            <select class="bm-select" id="b_packet_id" disabled onchange="bookingUpdatePrice()">
                                <option value="" data-price="0" disabled selected>-- Pilih Paket --</option>
                            </select>
                            <div class="invalid-feedback d-none" id="err_packet">
                                Pilih paket terlebih dahulu.
                            </div>
                            {{-- ── durasi paket ── --}}
                            <small class="text-muted d-none" id="b-duration-hint">
                                <i class="mdi mdi-clock-outline me-1"></i>
                                <span id="b-duration-text"></span>
                            </small>
                        </div>

                        {{-- ── Jadwal Sesi: Kalender + Slot Waktu ── --}}
                        <div class="col-12">
                            <label class="bm-label">
                                Jadwal Sesi <span class="bm-required">*</span>
                            </label>

                            <div class="row g-3">
                                {{-- Kalender --}}
                                <div class="col-md-6">
                                    <div class="bm-calendar">
                                        <div class="bm-cal-nav">
                                            <button type="button" class="bm-cal-nav-btn" id="b-cal-prev">
                                                <i class="mdi mdi-chevron-left"></i>
                                            </button>
                                            <span class="bm-cal-label" id="b-cal-label">—</span>
                                            <button type="button" class="bm-cal-nav-btn" id="b-cal-next">
                                                <i class="mdi mdi-chevron-right"></i>
                                            </button>
                                        </div>

                                        {{-- Header hari --}}
                                        <div class="bm-cal-dow-row">
                                            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)
                                                <div class="bm-cal-dow">{{ $d }}</div>
                                            @endforeach
                                        </div>

                                        {{-- Grid tanggal --}}
                                        <div class="bm-cal-grid" id="b-cal-days"></div>
                                    </div>
                                    <div class="invalid-feedback d-none" id="err_date">
                                        Pilih tanggal sesi.
                                    </div>
                                </div>

                                {{-- Slot Waktu --}}
                                <div class="col-md-6">
                                    <div class="bm-slot-panel">
                                        <p class="bm-slot-heading" id="b-slot-heading">
                                            Pilih tanggal terlebih dahulu
                                        </p>
                                        <div id="b-slot-loading" class="text-center py-3 d-none">
                                            <div class="spinner-border spinner-border-sm bm-spinner" role="status">
                                            </div>
                                            <p class="bm-slot-heading mt-1 mb-0">Memuat slot...</p>
                                        </div>
                                        <div class="row row-cols-2 g-1" id="b-slot-grid"></div>
                                    </div>
                                    <div class="invalid-feedback d-none" id="err_time">
                                        Pilih waktu sesi.
                                    </div>
                                </div>

                            </div>

                            {{-- Legenda --}}

                            <!-- Legenda Slot -->
                            <div class="bm-legend">
                                <span class="bm-legend-item">
                                    <span class="bm-legend-dot bm-legend-available"></span>
                                    Tersedia
                                </span>
                                <span class="bm-legend-item">
                                    <span class="bm-legend-dot bm-legend-half"></span>
                                    1 slot terisi
                                </span>
                                <span class="bm-legend-item">
                                    <span class="bm-legend-dot bm-legend-full"></span>
                                    Penuh
                                </span>
                            </div>

                            <input type="hidden" id="b_session_date">
                            <input type="hidden" id="b_session_time">
                        </div>
                        {{-- /Jadwal Sesi --}}

                        {{-- ── Tambahan Ekstra (Additional) ── --}}
                        <div class="col-12">
                            <label class="bm-label">Tambahan Ekstra (Opsional)</label>

                            <div id="b-extra-additionals-list" class="mb-2"></div>

                            <div class="bm-input-group" style="gap:8px;">
                                <select id="add_additional_select" class="bm-select">
                                    <option value="">Pilih tambahan...</option>
                                    @foreach($all_additionals as $additional)
                                        <option value="{{ $additional->id }}" data-name="{{ $additional->name }}"
                                            data-price="{{ $additional->price }}">
                                            {{ $additional->name }} (Rp
                                            {{ number_format($additional->price, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="bm-btn-secondary" style="white-space:nowrap;"
                                    id="add-additional-btn">
                                    <i class="mdi mdi-plus"></i> Tambah
                                </button>
                            </div>
                        </div>
                        {{-- /Tambahan Ekstra --}}

                        {{-- ── Metode Pembayaran ── --}}
                        <div class="col-12">
                            <label class="bm-label">
                                Metode Pembayaran <span class="bm-required">*</span>
                            </label>
                            <div class="bm-payment-options">
                                <label class="bm-payment-card selected" id="payment-card-full">
                                    <input type="radio" name="b_payment_type" id="b_payment_full" value="full" checked
                                        onchange="bookingTogglePaymentType()">
                                    <div class="bm-payment-card-inner">
                                        <i class="mdi mdi-cash-check"></i>
                                        <div>
                                            <p class="bm-payment-card-title">Bayar Penuh</p>
                                            <p class="bm-payment-card-sub">Lunas sekarang</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="bm-payment-card" id="payment-card-dp">
                                    <input type="radio" name="b_payment_type" id="b_payment_dp" value="dp"
                                        onchange="bookingTogglePaymentType()">
                                    <div class="bm-payment-card-inner">
                                        <i class="mdi mdi-cash-clock"></i>
                                        <div>
                                            <p class="bm-payment-card-title">DP (Uang Muka)</p>
                                            <p class="bm-payment-card-sub">Min. Rp 50.000</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="bm-payment-card" id="payment-card-onLocation">
                                    <input type="radio" name="b_payment_type" id="b_payment_onLocation" value="onLocation"
                                        onchange="bookingTogglePaymentType()">
                                    <div class="bm-payment-card-inner">
                                        <i class="mdi mdi-cash-clock"></i>
                                        <div>
                                            <p class="bm-payment-card-title">Bayar ditempat</p>
                                            <p class="bm-payment-card-sub"></p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- ── Input jumlah DP (muncul jika DP dipilih) ── --}}
                        <div class="col-12 d-none" id="booking-dp-input-wrap">
                            <label class="bm-label">
                                Jumlah DP <span class="bm-required">*</span>
                            </label>
                            <div class="bm-input-group">
                                <span class="bm-input-prefix"
                                    style="color:var(--bm-blue);font-size:14px;font-weight:600;">Rp</span>
                                <input type="number" class="bm-input bm-input-suffix" id="b_dp_amount" min="50000"
                                    step="1000" placeholder="Minimal 50.000" oninput="bookingOnDpInput()">
                            </div>
                            <div class="invalid-feedback d-none" id="err_dp_amount">
                                DP minimal Rp 50.000 dan tidak boleh melebihi total harga (paket + tambahan).
                            </div>
                            <small class="text-muted d-block mt-1" id="b-dp-remaining-hint"></small>
                        </div>

                        <div class="col-12 d-none" id="booking-price-summary">
                            <div class="bm-price-summary">
                                <div>
                                    <p class="bm-price-label" id="booking-price-label">Total Pembayaran</p>
                                    <p class="bm-price-value" id="booking-price-display">Rp 0</p>
                                    <p class="bm-price-sub d-none" id="booking-price-sub"></p>
                                </div>
                                <i class="mdi mdi-tag-check-outline bm-price-icon"></i>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="bm-btn-secondary px-4" onclick="bookingNextStep(1)">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </button>
                        <button class="bm-btn-primary flex-grow-1" onclick="bookingNextStep(3)">
                            Lanjut Konfirmasi <i class="mdi mdi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
                {{-- /STEP 2 --}}

                {{-- ════════════ STEP 3 : Konfirmasi & Simpan ════════════ --}}
                <div id="booking-step-3" class="d-none">

                    <div class="bm-summary-card">
                        <h6 class="bm-summary-title">
                            <i class="mdi mdi-clipboard-check-outline me-1"></i>
                            Ringkasan Pesanan
                        </h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="bm-tbl-label" style="width:40%">Nama</td>
                                    <td class="bm-tbl-value" id="confirm_name">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">WhatsApp</td>
                                    <td class="bm-tbl-value" id="confirm_phone">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Produk</td>
                                    <td class="bm-tbl-value" id="confirm_product">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Paket</td>
                                    <td class="bm-tbl-value" id="confirm_packet">-</td>
                                </tr>
                                {{-- ── tampilkan durasi di ringkasan ── --}}
                                <tr>
                                    <td class="bm-tbl-label">Durasi</td>
                                    <td class="bm-tbl-value" id="confirm_duration">-</td>
                                </tr>
                                {{-- ── tampilkan tambahan ekstra di ringkasan ── --}}
                                <tr id="confirm_additionals_row" class="d-none">
                                    <td class="bm-tbl-label align-top">Tambahan</td>
                                    <td class="bm-tbl-value" id="confirm_additionals">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Tanggal</td>
                                    <td class="bm-tbl-value" id="confirm_date">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Waktu</td>
                                    <td class="bm-tbl-value" id="confirm_time">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Selesai</td>
                                    <td class="bm-tbl-value" id="confirm_end_time">-</td>
                                </tr>
                                <tr>
                                    <td class="bm-tbl-label">Metode Pembayaran</td>
                                    <td class="bm-tbl-value" id="confirm_payment_method">-</td>
                                </tr>
                                <tr id="confirm_dp_row" class="d-none">
                                    <td class="bm-tbl-label">DP Dibayar Sekarang</td>
                                    <td class="bm-tbl-value" id="confirm_dp_amount">-</td>
                                </tr>
                                <tr id="confirm_remaining_row" class="d-none">
                                    <td class="bm-tbl-label">Sisa Pembayaran</td>
                                    <td class="bm-tbl-value" id="confirm_remaining">-</td>
                                </tr>
                                <tr class="bm-tbl-total-row">
                                    <td class="bm-tbl-total-label pt-2" id="confirm_total_label">Total</td>
                                    <td class="bm-tbl-total-value pt-2" id="confirm_total">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bm-info-box mb-3">
                        <i class="mdi mdi-information-outline me-1"></i>
                        Setelah booking dikonfirmasi, tim kami akan menghubungi Anda via WhatsApp
                        untuk info pembayaran lebih lanjut.
                    </div>

                    <div class="d-none text-center py-3" id="booking-loading">
                        <div class="spinner-border bm-spinner" role="status"></div>
                        <p class="mt-2 bm-slot-heading">Menyimpan booking Anda...</p>
                    </div>

                    <div class="d-flex gap-2" id="booking-action-btns">
                        <button class="bm-btn-secondary px-4" onclick="bookingNextStep(2)" id="booking-back-btn">
                            <i class="mdi mdi-arrow-left me-1"></i> Kembali
                        </button>
                        <button class="bm-btn-primary flex-grow-1" onclick="bookingSubmit()" id="booking-pay-btn">
                            <i class="mdi mdi-check-circle-outline me-1"></i> Konfirmasi Booking
                        </button>
                    </div>

                </div>
                {{-- /STEP 3 --}}

            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --bm-navy: #1a1a2e;
        --bm-blue: #0f3460;
        --bm-mid: #16213e;
        --bm-slate: #6c757d;
        --bm-light: #f0f4ff;
        --bm-white: #FFFFFF;
        --bm-surface: #f8f9fc;
        --bm-border: #e9ecef;
        --bm-text: #1a1a2e;
        --bm-text-muted: #6c757d;
        --bm-danger: #dc3545;
    }

    .bm-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 64px rgba(15, 52, 96, .18), 0 4px 16px rgba(15, 52, 96, .08);
        background: var(--bm-white);
    }

    .bm-header {
        background: linear-gradient(135deg, var(--bm-navy) 0%, var(--bm-mid) 50%, var(--bm-blue) 100%);
        padding: 28px 28px 24px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .bm-header-inner {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .bm-icon-wrap {
        width: 44px;
        height: 44px;
        background: rgba(255, 255, 255, .12);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 22px;
        color: var(--bm-light);
        backdrop-filter: blur(4px);
    }

    .bm-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--bm-white);
        margin: 0;
        letter-spacing: -.2px;
    }

    .bm-subtitle {
        font-size: 12px;
        color: rgba(238, 238, 238, .65);
        margin: 3px 0 0;
    }

    .bm-close-btn {
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .15);
        color: var(--bm-light);
        border-radius: 10px;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        font-size: 16px;
        transition: background .2s;
        padding: 0;
    }

    .bm-close-btn:hover {
        background: rgba(255, 255, 255, .22);
    }

    .bm-steps-wrap {
        background: var(--bm-surface);
        border-bottom: 1px solid var(--bm-border);
        padding: 16px 28px;
    }

    .bm-steps {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .booking-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }

    .booking-step .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--bm-border);
        color: var(--bm-slate);
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .3s ease;
        border: 2px solid transparent;
    }

    .booking-step .step-label {
        font-size: 10px;
        color: var(--bm-slate);
        white-space: nowrap;
        font-weight: 500;
        transition: all .3s ease;
        letter-spacing: .2px;
    }

    .booking-step.active .step-number {
        background: var(--bm-blue);
        color: var(--bm-white);
        border-color: var(--bm-blue);
        box-shadow: 0 0 0 4px rgba(15, 52, 96, .1);
    }

    .booking-step.active .step-label {
        color: var(--bm-blue);
        font-weight: 700;
    }

    .booking-step.done .step-number {
        background: var(--bm-surface);
        color: var(--bm-blue);
        border-color: var(--bm-blue);
    }

    .booking-step.done .step-label {
        color: var(--bm-blue);
    }

    .step-line {
        height: 2px;
        background: var(--bm-border);
        margin-bottom: 18px;
        transition: background .3s ease;
        border-radius: 2px;
    }

    .step-line.done {
        background: var(--bm-blue);
    }

    @keyframes stepFadeIn {
        from {
            opacity: 0;
            transform: translateY(10px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    .booking-step-fade {
        animation: stepFadeIn .25s ease;
    }
    .invalid-feedback {
    font-size: 11px;
    color: var(--bm-danger);
    margin-top: 4px;
}
    .invalid-feedback:not(.d-none) {
        display: block !important;
    }

    .bm-body {
        padding: 28px;
        background: var(--bm-white);
    }

    .bm-banner {
        display: flex;
        align-items: center;
        gap: 14px;
        background: #f0f4ff;
        border-left: 3px solid var(--bm-blue);
        border-radius: 12px;
        padding: 14px 16px;
    }

    .bm-banner-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--bm-blue);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--bm-white);
        font-size: 16px;
    }

    .bm-banner-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-blue);
        margin: 0;
    }

    .bm-banner-sub {
        font-size: 11px;
        color: var(--bm-slate);
        margin: 2px 0 0;
    }

    .bm-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-text);
        margin-bottom: 6px;
        letter-spacing: .1px;
    }

    .bm-required {
        color: var(--bm-danger);
    }

    .bm-input,
    .bm-select {
        width: 100%;
        padding: 11px 14px;
        font-size: 14px;
        color: var(--bm-text);
        background: var(--bm-white);
        border: 1.5px solid var(--bm-border);
        border-radius: 10px;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        appearance: none;
        -webkit-appearance: none;
    }

    .bm-input::placeholder {
        color: #adb5bd;
    }

    .bm-input:focus,
    .bm-select:focus {
        border-color: var(--bm-blue);
        box-shadow: 0 0 0 3px rgba(15, 52, 96, .1);
    }

    .bm-input.is-invalid,
    .bm-select.is-invalid {
        border-color: var(--bm-danger);
    }

    .bm-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23787A91' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 36px;
        cursor: pointer;
    }

    .bm-select:disabled {
        background-color: var(--bm-surface);
        color: var(--bm-slate);
        cursor: not-allowed;
    }

    .bm-input-group {
        display: flex;
        align-items: stretch;
    }

    .bm-input-prefix {
        display: flex;
        align-items: center;
        padding: 0 12px;
        background: var(--bm-surface);
        border: 1.5px solid var(--bm-border);
        border-right: none;
        border-radius: 10px 0 0 10px;
        color: #25D366;
        font-size: 18px;
    }

    .bm-input.bm-input-suffix {
        border-radius: 0 10px 10px 0;
        flex: 1;
    }

    .bm-btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 13px 24px;
        font-size: 14px;
        font-weight: 600;
        color: var(--bm-white);
        background: linear-gradient(135deg, var(--bm-blue) 0%, var(--bm-navy) 100%);
        border: none;
        border-radius: 50px;
        cursor: pointer;
        letter-spacing: .2px;
        transition: opacity .2s, transform .15s, box-shadow .2s;
        box-shadow: 0 4px 16px rgba(15, 52, 96, .25);
    }

    .bm-btn-primary:hover {
        opacity: .9;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(15, 52, 96, .3);
    }

    .bm-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 13px 20px;
        font-size: 14px;
        font-weight: 500;
        color: var(--bm-slate);
        background: var(--bm-white);
        border: 1.5px solid var(--bm-border);
        border-radius: 50px;
        cursor: pointer;
        transition: border-color .2s, color .2s, background .2s;
    }

    .bm-btn-secondary:hover {
        border-color: var(--bm-blue);
        color: var(--bm-blue);
        background: #f0f4ff;
    }

    .bm-calendar {
        background: var(--bm-surface);
        border: 1.5px solid var(--bm-border);
        border-radius: 14px;
        padding: 16px;
    }

    .bm-cal-dow-row {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        margin-bottom: 6px;
    }

    .bm-cal-dow {
        font-size: 10px;
        color: var(--bm-slate);
        font-weight: 600;
        padding: 2px 0;
        text-transform: uppercase;
        letter-spacing: .4px;
        text-align: center;
    }

    .bm-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }

    .bm-cal-day {
        text-align: center;
        padding: 3px 0;
    }

    .bm-cal-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 12px;
        cursor: pointer;
        user-select: none;
        transition: background .15s, color .15s;
    }

    .bm-cal-circle:hover:not(.bc-past):not(.bc-empty) {
        background: rgba(15, 52, 96, .1);
    }

    .bc-empty {
        opacity: .25;
        cursor: default;
        color: #adb5bd;
    }

    .bc-past {
        opacity: .35;
        cursor: not-allowed;
        color: var(--bm-slate);
    }

    .bc-today {
        border: 2px solid var(--bm-blue);
        color: var(--bm-blue);
        font-weight: 700;
    }

    .bc-selected {
        background: var(--bm-blue) !important;
        color: #fff !important;
        font-weight: 700;
    }

    .bm-cal-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .bm-cal-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-text);
    }

    .bm-cal-nav-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: var(--bm-white);
        border: 1.5px solid var(--bm-border);
        color: var(--bm-slate);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        padding: 0;
        transition: border-color .2s, color .2s;
    }

    .bm-cal-nav-btn:hover {
        border-color: var(--bm-blue);
        color: var(--bm-blue);
    }

    .b-cal-day-cell:hover {
        background: rgba(15, 52, 96, .1) !important;
    }

    .bm-slot-panel {
        background: var(--bm-surface);
        border: 1.5px solid var(--bm-border);
        border-radius: 14px;
        padding: 16px;
        min-height: 220px;
    }

    .bm-slot-heading {
        font-size: 11px;
        color: var(--bm-slate);
        font-weight: 500;
        margin-bottom: 10px;
    }

    .bm-spinner {
        color: var(--bm-blue) !important;
    }

    .b-slot-btn-available:hover {
        background: rgba(15, 52, 96, .07) !important;
    }

    .b-slot-btn-half:hover {
        background: #FEF3C7 !important;
    }

    .bm-legend {
        display: flex;
        gap: 16px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .bm-legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        color: var(--bm-slate);
    }

    .bm-legend-dot {
        width: 11px;
        height: 11px;
        border-radius: 3px;
        display: inline-block;
    }

    .bm-legend-available {
        border: 2px solid var(--bm-blue);
        background: transparent;
    }

    .bm-legend-half {
        border: 2px solid #F59E0B;
        background: #FFF8E1;
    }

    .bm-legend-full {
        background: var(--bm-border);
    }

    .bm-price-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, var(--bm-blue) 0%, var(--bm-navy) 100%);
        border-radius: 12px;
        padding: 16px 20px;
        color: var(--bm-white);
    }

    .bm-price-label {
        font-size: 12px;
        color: rgba(238, 238, 238, .7);
        margin: 0;
    }

    .bm-price-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--bm-white);
        margin: 3px 0 0;
        letter-spacing: -.3px;
    }

    .bm-price-icon {
        font-size: 28px;
        color: rgba(238, 238, 238, .5);
    }

    .bm-summary-card {
        background: var(--bm-surface);
        border: 1.5px solid var(--bm-border);
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 14px;
    }

    .bm-summary-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--bm-text);
        margin-bottom: 14px;
        letter-spacing: .1px;
    }

    .bm-tbl-label {
        font-size: 12px;
        color: var(--bm-slate);
        padding: 4px 0;
    }

    .bm-tbl-value {
        font-size: 13px;
        font-weight: 500;
        color: var(--bm-text);
        padding: 4px 0;
    }

    .bm-tbl-total-row {
        border-top: 1.5px solid var(--bm-border);
    }

    .bm-tbl-total-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--bm-text);
    }

    .bm-tbl-total-value {
        font-size: 17px;
        font-weight: 700;
        color: var(--bm-blue);
    }

    .bm-info-box {
        background: #f0f4ff;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 12px;
        color: var(--bm-blue);
        line-height: 1.5;
    }

    .bm-payment-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .bm-payment-card {
        position: relative;
        display: block;
        cursor: pointer;
        margin: 0;
    }

    .bm-payment-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .bm-payment-card-inner {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px;
        border: 1.5px solid var(--bm-border);
        border-radius: 12px;
        background: var(--bm-white);
        transition: border-color .2s, background .2s;
    }

    .bm-payment-card-inner i {
        font-size: 22px;
        color: var(--bm-slate);
        flex-shrink: 0;
    }

    .bm-payment-card-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-text);
        margin: 0;
    }

    .bm-payment-card-sub {
        font-size: 11px;
        color: var(--bm-slate);
        margin: 2px 0 0;
    }

    .bm-payment-card.selected .bm-payment-card-inner {
        border-color: var(--bm-blue);
        background: #f0f4ff;
    }

    .bm-payment-card.selected .bm-payment-card-inner i,
    .bm-payment-card.selected .bm-payment-card-title {
        color: var(--bm-blue);
    }

    .bm-price-sub {
        font-size: 11px;
        color: rgba(238, 238, 238, .8);
        margin: 4px 0 0;
    }

    .invalid-feedback {
        font-size: 11px;
        color: var(--bm-danger);
        margin-top: 4px;
    }

    /* ── Tambahan Ekstra (list item yang dipilih) ── */
    .b-extra-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: var(--bm-surface);
        border: 1.5px solid var(--bm-border);
        border-radius: 10px;
        padding: 8px 12px;
        margin-bottom: 6px;
    }

    .b-extra-item-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-text);
        margin: 0;
    }

    .b-extra-item-price {
        font-size: 11px;
        color: var(--bm-slate);
        margin: 1px 0 0;
    }

    .b-extra-item-qty {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .b-qty-btn {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 1.5px solid var(--bm-border);
        background: var(--bm-white);
        color: var(--bm-blue);
        font-weight: 700;
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
    }

    .b-qty-btn:hover {
        border-color: var(--bm-blue);
        background: #f0f4ff;
    }

    .b-qty-val {
        min-width: 18px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: var(--bm-text);
    }

    .b-extra-remove {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--bm-danger);
        font-size: 15px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .b-extra-remove:hover {
        background: rgba(220, 53, 69, .1);
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════════
JAVASCRIPT — IIFE UTAMA
═══════════════════════════════════════════════════════════════════ --}}
<script>
    (function () {
        'use strict';

        const productPackets = {!! json_encode($packets) !!};

        const formatRp = (n) =>
            new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);

        const formatDate = (s) => s
            ? new Intl.DateTimeFormat('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
                .format(new Date(s + 'T00:00:00'))
            : '-';

        // ── Helper: menit → "X jam Y menit" ──────────────────────────────
        function formatDuration(minutes) {
            const jam = Math.floor(minutes / 60);
            const menit = minutes % 60;
            let label = '';
            if (jam > 0) label += jam + ' jam';
            if (menit > 0) label += (label ? ' ' : '') + menit + ' menit';
            return label || minutes + ' menit';
        }

        // ── Helper: tambah menit ke string "HH:MM" ────────────────────────
        function addMinutesToTime(timeStr, minutes) {
            const [h, m] = timeStr.split(':').map(Number);
            const total = h * 60 + m + minutes;
            return String(Math.floor(total / 60)).padStart(2, '0') + ':' +
                String(total % 60).padStart(2, '0');
        }

        // ── Preselect produk & paket (dari product-detail-modal) ──────────
        window.bookingPreselect = function (productId, packetId) {
            const productSel = document.getElementById('b_product_id');
            productSel.value = productId;
            bookingUpdatePackets();

            setTimeout(() => {
                const packetSel = document.getElementById('b_packet_id');
                packetSel.value = packetId;
                bookingUpdatePrice();

                const productName = productSel.options[productSel.selectedIndex]?.dataset.productName ?? '';
                const packetName = packetSel.options[packetSel.selectedIndex]?.dataset.name ?? '';
                const packetPrice = parseFloat(packetSel.options[packetSel.selectedIndex]?.dataset.price) || 0;

                const banner = document.getElementById('booking-preselect-banner');
                const info = document.getElementById('banner-packet-info');
                if (banner && info && packetName) {
                    info.textContent = `${productName} — ${packetName} · ${formatRp(packetPrice)}`;
                    banner.classList.remove('d-none');
                }
            }, 50);
        };

        // ── Step navigation ───────────────────────────────────────────────
        window.bookingNextStep = function (step) {
            if (step > 1 && !validateStep(step - 1)) return;

            [1, 2, 3].forEach(i => {
                document.getElementById(`booking-step-${i}`).classList.add('d-none');
                document.getElementById(`step-indicator-${i}`).classList.remove('active', 'done');
            });

            document.querySelectorAll('.step-line').forEach((line, idx) => {
                line.classList.toggle('done', idx < step - 1);
            });

            for (let i = 1; i < step; i++) {
                document.getElementById(`step-indicator-${i}`).classList.add('done');
            }
            document.getElementById(`step-indicator-${step}`).classList.add('active');

            const el = document.getElementById(`booking-step-${step}`);
            el.classList.remove('d-none');
            el.classList.add('booking-step-fade');
            setTimeout(() => el.classList.remove('booking-step-fade'), 300);

            if (step === 2 && !_calInitialized) { calInit(); _calInitialized = true; }
            if (step === 3) fillConfirmation();
            
        };

        // ── Validasi per step ─────────────────────────────────────────────
        function validateStep(step) {
            let valid = true;

            const clearErr = (inputId, errId) => {
                document.getElementById(inputId)?.classList.remove('is-invalid');
                document.getElementById(errId)?.classList.add('d-none');
            };
            const setErr = (inputId, errId) => {
                document.getElementById(inputId)?.classList.add('is-invalid');
                document.getElementById(errId)?.classList.remove('d-none');
                valid = false;
            };

            if (step === 1) {

                clearErr('b_customer_name', 'err_customer_name');
                clearErr('b_phone_number', 'err_phone_number');

                if (!document.getElementById('b_customer_name').value.trim())
                    setErr('b_customer_name', 'err_customer_name');

                if (!/^08\d{8,12}$/.test(document.getElementById('b_phone_number').value.trim()))
                    setErr('b_phone_number', 'err_phone_number');
            }

            if (step === 2) {
                [
                    ['b_product_id', 'err_product'],
                    ['b_packet_id', 'err_packet'],
                    ['b_session_date', 'err_date'],
                    ['b_session_time', 'err_time'],
                ].forEach(([iId, eId]) => {
                    clearErr(iId, eId);
                    if (!document.getElementById(iId).value) setErr(iId, eId);
                });

                // ── Validasi DP ──
                clearErr('b_dp_amount', 'err_dp_amount');
                if (getPaymentType() === 'dp') {
                    const dp = getDpAmount();
                    const total = getTotalPrice();
                    if (!dp || dp < 50000 || dp > total) {
                        setErr('b_dp_amount', 'err_dp_amount');
                    }
                }
            }

            return valid;
        }

        // ── Dropdown paket (+ simpan dataset.duration) ────────────────────
        window.bookingUpdatePackets = function () {
            const productSel = document.getElementById('b_product_id');
            const packetSel = document.getElementById('b_packet_id');

            packetSel.innerHTML = '<option value="" data-price="0" disabled selected>-- Pilih Paket --</option>';
            packetSel.disabled = true;
            document.getElementById('booking-price-summary').classList.add('d-none');
            document.getElementById('b-duration-hint').classList.add('d-none');

            const name = productSel.options[productSel.selectedIndex]?.dataset.productName;
            if (!name || !productPackets[name]) return;

            productPackets[name].forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.dataset.price = p.price;
                opt.dataset.name = p.name;
                opt.dataset.duration = p.duration_minutes ?? 60;  // ← DURASI
                opt.textContent = `${p.name} — ${formatRp(p.price)}`;
                packetSel.appendChild(opt);
            });

            packetSel.disabled = false;
            bookingUpdatePrice();
        };

        // ── State: additional ekstra yang dipilih ─────────────────────────
        // Format: { [additional_id]: { id, name, price, qty } }
        let _selectedAdditionals = {};

        function getAdditionalsTotal() {
            return Object.values(_selectedAdditionals)
                .reduce((sum, a) => sum + (a.price * a.qty), 0);
        }

        function renderAdditionalsList() {
            const list = document.getElementById('b-extra-additionals-list');
            list.innerHTML = '';

            Object.values(_selectedAdditionals).forEach(item => {
                const div = document.createElement('div');
                div.className = 'b-extra-item';
                div.dataset.id = item.id;
                div.innerHTML = `
                    <div>
                        <p class="b-extra-item-name">${item.name}</p>
                        <p class="b-extra-item-price">${formatRp(item.price)} / item</p>
                    </div>
                    <div class="b-extra-item-qty">
                        <button type="button" class="b-qty-btn" data-action="dec">−</button>
                        <span class="b-qty-val">${item.qty}</span>
                        <button type="button" class="b-qty-btn" data-action="inc">+</button>
                    </div>
                    <button type="button" class="b-extra-remove" data-action="remove">
                        <i class="mdi mdi-close"></i>
                    </button>
                `;
                list.appendChild(div);
            });

            updatePriceSummaryDisplay();
        }

        window.bookingAddAdditional = function () {
            const sel = document.getElementById('add_additional_select');
            const opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) return;

            const id = opt.value;
            if (_selectedAdditionals[id]) {
                _selectedAdditionals[id].qty += 1;
            } else {
                _selectedAdditionals[id] = {
                    id,
                    name: opt.dataset.name,
                    price: parseFloat(opt.dataset.price) || 0,
                    qty: 1,
                };
            }
            sel.value = '';
            renderAdditionalsList();
        };

        document.getElementById('add-additional-btn').addEventListener('click', bookingAddAdditional);

        document.getElementById('b-extra-additionals-list').addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            const row = btn.closest('.b-extra-item');
            const id = row.dataset.id;
            const action = btn.dataset.action;

            if (action === 'inc') _selectedAdditionals[id].qty += 1;
            if (action === 'dec') {
                _selectedAdditionals[id].qty -= 1;
                if (_selectedAdditionals[id].qty <= 0) delete _selectedAdditionals[id];
            }
            if (action === 'remove') delete _selectedAdditionals[id];

            renderAdditionalsList();
        });

        // ── Helper pembayaran (Full / DP) ─────────────────────────────────
        // getTotalPrice() = harga paket + total semua tambahan ekstra
        function getTotalPrice() {
            const packetSel = document.getElementById('b_packet_id');
            const packetPrice = parseFloat(packetSel.options[packetSel.selectedIndex]?.dataset.price) || 0;
            return packetPrice + getAdditionalsTotal();
        }

        function getPaymentType() {
            return document.getElementById('b_payment_dp').checked ? 'dp' : document.getElementById('b_payment_onLocation').checked ? 'onLocation' : 'full';
        }

        function getDpAmount() {
            return parseInt(document.getElementById('b_dp_amount').value, 10) || 0;
        }

        function updatePaymentCardStyles() {
            const isFull = document.getElementById('b_payment_full').checked;
            const isDp = document.getElementById('b_payment_dp').checked;
            const isOnLocation = document.getElementById('b_payment_onLocation').checked;

            document.getElementById('payment-card-full').classList.toggle('selected', isFull);
            document.getElementById('payment-card-dp').classList.toggle('selected', isDp);
            document.getElementById('payment-card-onLocation').classList.toggle('selected', isOnLocation);
        }

        function updatePriceSummaryDisplay() {
            const total = getTotalPrice();
            const summary = document.getElementById('booking-price-summary');
            const label = document.getElementById('booking-price-label');
            const display = document.getElementById('booking-price-display');
            const sub = document.getElementById('booking-price-sub');

            if (total <= 0) {
                summary.classList.add('d-none');
                return;
            }
            summary.classList.remove('d-none');

            const extrasTotal = getAdditionalsTotal();
            const extrasNote = extrasTotal > 0 ? `Termasuk tambahan ${formatRp(extrasTotal)}` : '';

            if (getPaymentType() === 'dp') {
                const dp = getDpAmount();
                label.textContent = 'DP Dibayar Sekarang';
                display.textContent = formatRp(dp);
                sub.textContent = `Total ${formatRp(total)}` + (extrasTotal > 0 ? ` (${extrasNote})` : '') + ` · Sisa ${formatRp(Math.max(total - dp, 0))}`;
                sub.classList.remove('d-none');

            } else if (getPaymentType() === 'onLocation') {
                label.textContent = 'Bayar di Lokasi';
                display.textContent = formatRp(total);
                if (extrasNote) {
                    sub.textContent = extrasNote;
                    sub.classList.remove('d-none');
                } else {
                    sub.classList.add('d-none');
                }
            }
            else {
                label.textContent = 'Total Pembayaran';
                display.textContent = formatRp(total);
                if (extrasNote) {
                    sub.textContent = extrasNote;
                    sub.classList.remove('d-none');
                } else {
                    sub.classList.add('d-none');
                }
            }
        }

        function updateDpRemainingHint() {
            const total = getTotalPrice();
            const dp = getDpAmount();
            const hint = document.getElementById('b-dp-remaining-hint');
            hint.textContent = (total > 0)
                ? `Sisa pembayaran: ${formatRp(Math.max(total - dp, 0))}`
                : '';
        }

        window.bookingTogglePaymentType = function () {
            updatePaymentCardStyles();
            const isDp = getPaymentType() === 'dp';
            const dpWrap = document.getElementById('booking-dp-input-wrap');
            dpWrap.classList.toggle('d-none', !isDp);

            if (isDp) {
                document.getElementById('b_dp_amount').max = getTotalPrice();
                updateDpRemainingHint();
            } else {
                document.getElementById('b_dp_amount').value = '';
                document.getElementById('b_dp_amount').classList.remove('is-invalid');
                document.getElementById('err_dp_amount').classList.add('d-none');
            }
            updatePriceSummaryDisplay();
        };

        window.bookingOnDpInput = function () {
            document.getElementById('b_dp_amount').classList.remove('is-invalid');
            document.getElementById('err_dp_amount').classList.add('d-none');
            updateDpRemainingHint();
            updatePriceSummaryDisplay();
        };

        window.bookingUpdatePrice = function () {
            const packetSel = document.getElementById('b_packet_id');
            const duration = parseInt(packetSel.options[packetSel.selectedIndex]?.dataset.duration) || 0;
            const hintEl = document.getElementById('b-duration-hint');
            const hintText = document.getElementById('b-duration-text');

            // Reset metode pembayaran ke default tiap ganti paket
            document.getElementById('b_payment_full').checked = true;
            document.getElementById('b_dp_amount').value = '';
            document.getElementById('booking-dp-input-wrap').classList.add('d-none');
            updatePaymentCardStyles();
            updatePriceSummaryDisplay();

            if (duration > 0) {
                hintText.textContent = 'Durasi: ' + formatDuration(duration);
                hintEl.classList.remove('d-none');
            } else {
                hintEl.classList.add('d-none');
            }

            const selectedDate = document.getElementById('b_session_date').value;
            if (selectedDate && packetSel.value) {
                document.getElementById('b_session_time').value = '';
                fetchAndRenderSlots(selectedDate);
            }
        };

        // ── Isi ringkasan step 3 ──────────────────────────────────────────
        function fillConfirmation() {
            const productSel = document.getElementById('b_product_id');
            const packetSel = document.getElementById('b_packet_id');
            const packetPrice = parseFloat(packetSel.options[packetSel.selectedIndex]?.dataset.price) || 0;
            const duration = parseInt(packetSel.options[packetSel.selectedIndex]?.dataset.duration) || 0;
            const sessionTime = document.getElementById('b_session_time').value;
            const totalPrice = getTotalPrice(); // paket + tambahan
            const infoBox = document.querySelector('#booking-step-3 .bm-info-box');

            
            document.getElementById('confirm_name').textContent = document.getElementById('b_customer_name').value;
            document.getElementById('confirm_phone').textContent = document.getElementById('b_phone_number').value;
            document.getElementById('confirm_product').textContent = productSel.options[productSel.selectedIndex]?.dataset.productName || '-';
            document.getElementById('confirm_packet').textContent = packetSel.options[packetSel.selectedIndex]?.dataset.name || '-';
            document.getElementById('confirm_duration').textContent = duration ? formatDuration(duration) : '-';
            document.getElementById('confirm_date').textContent = formatDate(document.getElementById('b_session_date').value);
            document.getElementById('confirm_time').textContent = sessionTime ? sessionTime + ' WIB' : '-';

            if (sessionTime && duration) {
                document.getElementById('confirm_end_time').textContent = addMinutesToTime(sessionTime, duration) + ' WIB';
            } else {
                document.getElementById('confirm_end_time').textContent = '-';
            }

            // ── Tambahan ekstra ──
            const additionalsList = Object.values(_selectedAdditionals);
            const additionalsRow = document.getElementById('confirm_additionals_row');
            if (additionalsList.length > 0) {
                document.getElementById('confirm_additionals').innerHTML = additionalsList
                    .map(a => `${a.qty}x ${a.name} (${formatRp(a.price * a.qty)})`)
                    .join('<br>');
                additionalsRow.classList.remove('d-none');
            } else {
                additionalsRow.classList.add('d-none');
            }

            // ── Metode pembayaran ──
            const paymentType = getPaymentType();
            const dpRow = document.getElementById('confirm_dp_row');
            const remainingRow = document.getElementById('confirm_remaining_row');
            const totalLabel = document.getElementById('confirm_total_label');

            document.getElementById('confirm_payment_method').textContent =
                paymentType === 'dp' ? 'DP (Uang Muka)' : paymentType === 'onLocation' ? 'Bayar di Lokasi' : 'Bayar Penuh';

            if(paymentType === 'onLocation') {
                            dpRow.classList.add('d-none');
                remainingRow.classList.add('d-none');
                totalLabel.textContent = 'Dibayar di Lokasi';
                document.getElementById('confirm_total').textContent = formatRp(totalPrice);

            }
            else if (paymentType === 'dp') {
                const dp = getDpAmount();
                document.getElementById('confirm_dp_amount').textContent = formatRp(dp);
                document.getElementById('confirm_remaining').textContent = formatRp(Math.max(totalPrice - dp, 0));
                dpRow.classList.remove('d-none');
                remainingRow.classList.remove('d-none');
                totalLabel.textContent = 'Dibayar Sekarang';
                document.getElementById('confirm_total').textContent = formatRp(dp);
            } else {
                dpRow.classList.add('d-none');
                remainingRow.classList.add('d-none');
                totalLabel.textContent = 'Total';
                document.getElementById('confirm_total').textContent = formatRp(totalPrice);
            }
        }


        //  Submit → Xendit Invoice Redirect
        window.bookingSubmit = async function () {
            const loadingEl = document.getElementById('booking-loading');
            const actionEl = document.getElementById('booking-action-btns');
            const closeBtn = document.getElementById('bookingCloseBtn');

            loadingEl.classList.remove('d-none');
            actionEl.classList.add('d-none');
            closeBtn.disabled = true;

            const additionalsPayload = {};
            Object.values(_selectedAdditionals).forEach(a => {
                additionalsPayload[a.id] = { quantity: a.qty };
            });

            const payload = {
                customer_name: document.getElementById('b_customer_name').value.trim(),
                phone_number: document.getElementById('b_phone_number').value.trim(),
                product_id: document.getElementById('b_product_id').value,
                packet_id: document.getElementById('b_packet_id').value,
                session_date: document.getElementById('b_session_date').value,
                session_time: document.getElementById('b_session_time').value,
                payment_option: getPaymentType(),
                dp_amount: getPaymentType() === 'dp' ? getDpAmount() : null,
                additionals: additionalsPayload,
            };

            try {
                const invoiceRes = await fetch('{{ route("booking.snap-token") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                const invoiceData = await invoiceRes.json();

                if (!invoiceRes.ok) {
                    const errMsg = invoiceData.errors
                        ? Object.values(invoiceData.errors).flat().join('\n')
                        : (invoiceData.message || 'Terjadi kesalahan.');
                    throw new Error(errMsg);
                }

                const target = invoiceData.redirect_url || invoiceData.invoice_url;
                if (!target) throw new Error('URL tujuan tidak ditemukan.');
                window.location.href = target;

            } catch (error) {
                loadingEl.classList.add('d-none');
                actionEl.classList.remove('d-none');
                closeBtn.disabled = false;
                showInlineError(error.message);
            }
        };

        function showInlineError(message) {
            const existing = document.getElementById('booking-inline-error');
            if (existing) existing.remove();
            const div = document.createElement('div');
            div.id = 'booking-inline-error';
            div.className = 'alert alert-danger rounded-3 mt-3 mb-0';
            div.innerHTML = `<i class="mdi mdi-alert-circle-outline me-1"></i>${message}`;
            document.getElementById('booking-action-btns').insertAdjacentElement('beforebegin', div);
        }

        // ── Reset saat modal ditutup ──────────────────────────────────────
        document.getElementById('bookingModal').addEventListener('hidden.bs.modal', function () {
            if (window._bookingGoingToSnap) {
                if (document.activeElement) document.activeElement.blur();
                document.body.focus();
                return;
            }

            ['b_customer_name', 'b_phone_number'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.value = ''; el.classList.remove('is-invalid'); }
            });
            ['b_product_id'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.selectedIndex = 0; el.classList.remove('is-invalid'); }
            });
            ['b_session_date', 'b_session_time'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.value = ''; el.classList.remove('is-invalid'); }
            });

            const packetSelect = document.getElementById('b_packet_id');
            if (packetSelect) {
                packetSelect.innerHTML = '<option value="" data-price="0" disabled selected>-- Pilih Paket --</option>';
                packetSelect.disabled = true;
            }

            // Reset tambahan ekstra
            _selectedAdditionals = {};
            document.getElementById('b-extra-additionals-list').innerHTML = '';

            document.getElementById('booking-preselect-banner')?.classList.add('d-none');
            document.getElementById('booking-price-summary')?.classList.add('d-none');
            document.getElementById('b-duration-hint')?.classList.add('d-none');
            document.getElementById('booking-loading')?.classList.add('d-none');
            document.getElementById('booking-action-btns')?.classList.remove('d-none');
            document.getElementById('bookingCloseBtn') && (document.getElementById('bookingCloseBtn').disabled = false);
            document.getElementById('booking-inline-error')?.remove();
            document.getElementById('b_payment_full').checked = true;
            document.getElementById('b_dp_amount').value = '';
            document.getElementById('booking-dp-input-wrap')?.classList.add('d-none');
            document.getElementById('payment-card-full')?.classList.add('selected');
            document.getElementById('payment-card-dp')?.classList.remove('selected');
            document.getElementById('booking-price-sub')?.classList.add('d-none');

            bookingNextStep(1);
            calReset();

            if (document.activeElement) document.activeElement.blur();
            document.body.focus();
        });

    })();
</script>

{{-- ═══════════════════════════════════════════════════════════════════
JAVASCRIPT — KALENDER & SLOT WAKTU
═══════════════════════════════════════════════════════════════════ --}}
<script>
    (function () {
        'use strict';

        let _calYear = null;
        let _calMonth = null;
        let _calSelectedDate = null;
        let _selectedSlot = null;
        let _currentSlots = [];

        window._calInitialized = false;

        window.calInit = function () {
            const today = new Date();
            _calYear = today.getFullYear();
            _calMonth = today.getMonth();
            _calSelectedDate = null;
            _selectedSlot = null;
            _currentSlots = [];
            window._calInitialized = true;

            document.getElementById('b_session_date').value = '';
            document.getElementById('b_session_time').value = '';

            renderCalendar();
            clearSlotGrid();
        };

        window.calReset = function () {
            window._calInitialized = false;
            calInit();
        };

        function renderCalendar() {
            const rawLabel = new Date(_calYear, _calMonth, 1)
                .toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            document.getElementById('b-cal-label').textContent =
                rawLabel.charAt(0).toUpperCase() + rawLabel.slice(1);

            const grid = document.getElementById('b-cal-days');
            grid.innerHTML = '';

            const firstDay = new Date(_calYear, _calMonth, 1).getDay(); // 0=Min
            const offset = firstDay === 0 ? 6 : firstDay - 1;           // Sen=0..Min=6
            const daysInMonth = new Date(_calYear, _calMonth + 1, 0).getDate();
            const prevDays = new Date(_calYear, _calMonth, 0).getDate();
            const today = new Date(); today.setHours(0, 0, 0, 0);

            // Sel kosong bulan sebelumnya
            for (let i = 0; i < offset; i++) {
                grid.appendChild(makeEmptyCell(prevDays - offset + 1 + i));
            }

            // Hari-hari bulan ini
            for (let d = 1; d <= daysInMonth; d++) {
                const cellDate = new Date(_calYear, _calMonth, d);
                const dateStr = toDateStr(_calYear, _calMonth, d);
                const isPast = cellDate < today;
                const isSel = dateStr === _calSelectedDate;
                const isToday = cellDate.toDateString() === today.toDateString();

                let circleStyle = 'width:30px;height:30px;font-size:12px;display:flex;' +
                    'align-items:center;justify-content:center;border-radius:50%;margin:auto;';

                if (isPast) circleStyle += 'opacity:.3;color:#6c757d;cursor:not-allowed;';
                else if (isSel) circleStyle += 'background:#0f3460;color:#fff;font-weight:600;cursor:pointer;';
                else if (isToday) circleStyle += 'border:2px solid #0f3460;color:#0f3460;font-weight:600;cursor:pointer;';
                else circleStyle += 'color:#1A1A2E;cursor:pointer;';

                const col = document.createElement('div');
                col.className = 'col text-center py-1';

                const circle = document.createElement('div');
                circle.style.cssText = circleStyle;
                circle.textContent = d;
                if (!isPast) circle.classList.add('b-cal-day-cell');

                if (!isPast) {
                    circle.onclick = () => {
                        _calSelectedDate = dateStr;
                        _selectedSlot = null;
                        document.getElementById('b_session_date').value = dateStr;
                        document.getElementById('b_session_time').value = '';
                        document.getElementById('b_session_date').classList.remove('is-invalid');
                        document.getElementById('err_date').classList.add('d-none');
                        renderCalendar();
                        fetchAndRenderSlots(dateStr);
                    };
                }

                col.appendChild(circle);
                grid.appendChild(col);
            }

            // Sel kosong bulan berikutnya — lengkapi hingga kelipatan 7
            const totalCells = offset + daysInMonth;
            const trailing = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (let i = 1; i <= trailing; i++) {
                grid.appendChild(makeEmptyCell(i));
            }
        }

        function makeEmptyCell(num) {
            const c = document.createElement('div');
            c.className = 'col text-center py-1';
            c.innerHTML = `<div style="width:30px;height:30px;font-size:12px;display:flex;
        align-items:center;justify-content:center;
        border-radius:50%;margin:auto;color:#ccc;cursor:default;">${num}</div>`;
            return c;
        }



        function toDateStr(y, m, d) {
            return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }

        // ── Fetch slot — sertakan packet_id agar backend tahu durasi ─────
        window.fetchAndRenderSlots = async function (dateStr) {
            const loadingEl = document.getElementById('b-slot-loading');
            const gridEl = document.getElementById('b-slot-grid');

            loadingEl.classList.remove('d-none');
            gridEl.innerHTML = '';

            try {
                // ── BARU: tambahkan packet_id ke query string ──
                const packetId = document.getElementById('b_packet_id').value;
                const url = packetId
                    ? `/booking/available-slots?date=${dateStr}&packet_id=${packetId}`
                    : `/booking/available-slots?date=${dateStr}`;

                const res = await fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                if (!res.ok) throw new Error('Gagal memuat slot.');
                const data = await res.json();
                _currentSlots = data.slots || [];
            } catch (e) {
                _currentSlots = [];
            }

            loadingEl.classList.add('d-none');
            renderSlots(dateStr);
        };

        function renderSlots(dateStr) {
            const grid = document.getElementById('b-slot-grid');
            const heading = document.getElementById('b-slot-heading');
            grid.innerHTML = '';

            if (!dateStr) {
                heading.textContent = 'Pilih tanggal terlebih dahulu';
                return;
            }

            const dObj = new Date(dateStr + 'T00:00:00');
            const label = dObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            heading.textContent = `Pilih waktu di tanggal ${label}`;

            if (_currentSlots.length === 0) {
                grid.innerHTML = '<div class="col-12"><p class="small text-muted">Tidak ada slot tersedia.</p></div>';
                return;
            }

            _currentSlots.forEach(slot => {
                const col = document.createElement('div');
                col.className = 'col';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = slot.time + ' WIB';
                btn.style.cssText =
                    'width:100%;font-size:12px;padding:8px 4px;border-radius:8px;' +
                    'font-weight:500;border:2px solid;transition:background .15s,color .15s;';

                const isSelected = slot.time === _selectedSlot;

                if (!slot.available) {
                    btn.disabled = true;
                    btn.style.borderColor = '#e9ecef';
                    btn.style.color = '#adb5bd';
                    btn.style.background = '#f8f9fc';
                    btn.style.textDecoration = 'line-through';
                    btn.style.cursor = 'not-allowed';
                } else if (slot.booked === 1) {
                    applySlotStyle(btn, isSelected, 'half');
                    btn.classList.add('b-slot-btn-half');
                } else {
                    applySlotStyle(btn, isSelected, 'available');
                    btn.classList.add('b-slot-btn-available');
                }

                if (slot.available) {
                    btn.onclick = () => selectSlot(slot.time, dateStr);
                }

                col.appendChild(btn);
                grid.appendChild(col);
            });
        }

        function applySlotStyle(btn, isSelected, type) {
            if (isSelected) {
                btn.style.borderColor = '#0f3460';
                btn.style.background = '#0f3460';
                btn.style.color = '#ffffff';
            } else if (type === 'half') {
                btn.style.borderColor = '#F59E0B';
                btn.style.background = '#FFF8E1';
                btn.style.color = '#92400E';
            } else {
                btn.style.borderColor = '#0f3460';
                btn.style.background = 'transparent';
                btn.style.color = '#0f3460';
            }
        }

        function selectSlot(time, dateStr) {
            _selectedSlot = time;
            document.getElementById('b_session_time').value = time;
            document.getElementById('b_session_time').classList.remove('is-invalid');
            document.getElementById('err_time').classList.add('d-none');
            renderSlots(dateStr);
        }

        function clearSlotGrid() {
            document.getElementById('b-slot-grid').innerHTML = '';
            document.getElementById('b-slot-heading').textContent = 'Pilih tanggal terlebih dahulu';
        }

        document.getElementById('b-cal-prev').addEventListener('click', () => {
            _calMonth--;
            if (_calMonth < 0) { _calMonth = 11; _calYear--; }
            renderCalendar();
        });

        document.getElementById('b-cal-next').addEventListener('click', () => {
            _calMonth++;
            if (_calMonth > 11) { _calMonth = 0; _calYear++; }
            renderCalendar();
        });

    })();
</script>

<script>
    document.getElementById('bookingModal').addEventListener('shown.bs.modal', function () {
        const closeBtn = document.getElementById('bookingCloseBtn');
        if (closeBtn) closeBtn.blur();
    });
</script>