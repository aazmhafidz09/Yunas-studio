<div class="sch-page">
    {{-- ── Hero header ─────────────────────────────────────────── --}}
    <div class="sch-hero">
        <div class="sch-hero-inner">
            <div class="sch-hero-icon">
                <i class="mdi mdi-calendar-clock"></i>
            </div>
            <div>
                <h1 class="sch-hero-title">Jadwal Studio</h1>
                <p class="sch-hero-sub">Cek ketersediaan slot dan ubah jadwal booking Anda</p>
            </div>
        </div>
    </div>

    <div class="container-xl sch-container">
        <div class="sch-layout">

            {{-- ══════════════ KOLOM KIRI: Kalender + Reschedule ══════════════ --}}
            <div class="sch-left">

                {{-- ── Kalender ── --}}
                <div class="sch-card">
                    <div class="sch-card-header">
                        <i class="mdi mdi-calendar-month-outline me-2"></i>
                        Pilih Tanggal
                    </div>
                    <div class="sch-card-body">
                        <div class="sch-cal-nav">
                            <button class="sch-cal-btn" id="sc-prev">
                                <i class="mdi mdi-chevron-left"></i>
                            </button>
                            <span class="sch-cal-label" id="sc-cal-label">—</span>
                            <button class="sch-cal-btn" id="sc-next">
                                <i class="mdi mdi-chevron-right"></i>
                            </button>
                        </div>
                        <div class="sch-cal-dow-row">
                            @foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $d)
                                <div class="sch-cal-dow">{{ $d }}</div>
                            @endforeach
                        </div>
                        <div class="sch-cal-grid" id="sc-cal-days"></div>
                    </div>
                </div>

                {{-- ── Legenda ── --}}
                <div class="sch-legend-card">
                    <div class="sch-legend-row">
                        <span class="sch-leg-dot sch-leg-available"></span>
                        <span class="sch-leg-label">Tersedia</span>
                    </div>
                    <div class="sch-legend-row">
                        <span class="sch-leg-dot sch-leg-half"></span>
                        <span class="sch-leg-label">1 dari 2 slot terisi</span>
                    </div>
                    <div class="sch-legend-row">
                        <span class="sch-leg-dot sch-leg-full"></span>
                        <span class="sch-leg-label">Penuh</span>
                    </div>
                </div>

                {{-- ── Panel Reschedule ── --}}
                <div class="sch-card" id="sch-reschedule-panel">
                    <div class="sch-card-header">
                        <i class="mdi mdi-calendar-edit me-2"></i>
                        Ubah Jadwal Booking
                    </div>
                    <div class="sch-card-body">
                        <p class="sch-panel-desc">
                            Masukkan nomor WhatsApp atau Order ID untuk menemukan booking Anda.
                        </p>

                        {{-- Step R1: Cari Booking --}}
                        <div id="rs-step-1">
                            <div class="sch-input-wrap mb-3">
                                <label class="sch-label">
                                    No. WhatsApp / Order ID
                                </label>
                                <input type="text" class="sch-input" id="rs-identifier"
                                       placeholder="08xxxxxxxxxx atau INV/20250101/123">
                                <div class="sch-error d-none" id="rs-err-identifier">
                                    Masukkan nomor HP atau Order ID yang valid.
                                </div>
                            </div>
                            <button class="sch-btn-primary w-100" onclick="rsCariBooking()">
                                <span id="rs-search-text">
                                    <i class="mdi mdi-magnify me-1"></i> Cari Booking
                                </span>
                                <span id="rs-search-loading" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    Mencari...
                                </span>
                            </button>
                        </div>

                        {{-- Step R2: Pilih booking yang ingin di-reschedule --}}
                        <div id="rs-step-2" class="d-none">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="sch-step-label">Pilih booking:</span>
                                <button class="sch-btn-link" onclick="rsReset()">
                                    <i class="mdi mdi-arrow-left me-1"></i> Ganti
                                </button>
                            </div>
                            <div id="rs-booking-list"></div>
                        </div>

                        {{-- Step R3: Pilih jadwal baru --}}
                        <div id="rs-step-3" class="d-none">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="sch-step-label">Jadwal baru:</span>
                                <button class="sch-btn-link" onclick="rsBackToList()">
                                    <i class="mdi mdi-arrow-left me-1"></i> Kembali
                                </button>
                            </div>

                            {{-- Info booking terpilih --}}
                            <div class="rs-booking-badge mb-3" id="rs-selected-info"></div>

                            <p class="sch-panel-desc mb-2">
                                Klik tanggal di kalender, lalu pilih waktu baru.
                            </p>

                            {{-- Tanggal & waktu terpilih --}}
                            <div class="rs-chosen-wrap" id="rs-chosen-wrap">
                                <div class="rs-chosen-item">
                                    <span class="rs-chosen-label">Tanggal</span>
                                    <span class="rs-chosen-val" id="rs-chosen-date">—</span>
                                </div>
                                <div class="rs-chosen-item">
                                    <span class="rs-chosen-label">Waktu</span>
                                    <span class="rs-chosen-val" id="rs-chosen-time">—</span>
                                </div>
                            </div>

                            <div class="sch-error d-none mb-2" id="rs-err-slot">
                                Pilih tanggal dan waktu terlebih dahulu.
                            </div>

                            <button class="sch-btn-primary w-100 mt-3" onclick="rsSimpan()">
                                <span id="rs-save-text">
                                    <i class="mdi mdi-check me-1"></i> Simpan Jadwal Baru
                                </span>
                                <span id="rs-save-loading" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>

                        {{-- Step R4: Sukses --}}
                        <div id="rs-step-4" class="d-none">
                            <div class="rs-success-box">
                                <div class="rs-success-icon">
                                    <i class="mdi mdi-check-circle"></i>
                                </div>
                                <p class="rs-success-title">Jadwal Berhasil Diubah!</p>
                                <div class="rs-success-detail" id="rs-success-detail"></div>
                                <button class="sch-btn-secondary w-100 mt-3" onclick="rsReset()">
                                    Ubah Booking Lain
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            {{-- /kolom kiri --}}

            {{-- ══════════════ KOLOM KANAN: Grid Slot ══════════════ --}}
            <div class="sch-right">
                <div class="sch-card sch-slot-card">
                    <div class="sch-card-header d-flex align-items-center justify-content-between">
                        <div>
                            <i class="mdi mdi-clock-outline me-2"></i>
                            <span id="sc-slot-header-date">Pilih tanggal untuk melihat slot</span>
                        </div>
                        <span class="sch-slot-ops">Ops: 10:00 – 18:00</span>
                    </div>
                    <div class="sch-card-body">

                        {{-- Placeholder sebelum tanggal dipilih --}}
                        <div id="sc-slot-placeholder" class="sch-placeholder">
                            <div class="sch-placeholder-icon">
                                <i class="mdi mdi-calendar-arrow-right"></i>
                            </div>
                            <p class="sch-placeholder-text">
                                Pilih tanggal di kalender untuk melihat ketersediaan slot
                            </p>
                        </div>

                        {{-- Loading --}}
                        <div id="sc-slot-loading" class="sch-placeholder d-none">
                            <div class="spinner-border sch-spinner" role="status"></div>
                            <p class="sch-placeholder-text mt-2">Memuat slot...</p>
                        </div>

                        {{-- Grid slot --}}
                        <div id="sc-slot-grid" class="sch-slot-grid d-none"></div>

                    </div>
                </div>

                {{-- Info tambahan --}}
                <div class="sch-info-card">
                    <i class="mdi mdi-information-outline me-2" style="color: var(--sc-blue); font-size:16px;"></i>
                    Setiap slot maksimal untuk <strong>2 sesi</strong> bersamaan.
                    Slot berwarna kuning berarti masih ada 1 tempat tersisa.
                </div>
            </div>
            {{-- /kolom kanan --}}

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     CSS
═══════════════════════════════════════════════════════════════════ --}}
<style>
:root {
    --sc-navy:    #1a1a2e;
    --sc-blue:    #0f3460;
    --sc-mid:     #16213e;
    --sc-surface: #f8f9fc;
    --sc-border:  #e9ecef;
    --sc-white:   #ffffff;
    --sc-slate:   #6c757d;
    --sc-text:    #1a1a2e;
    --sc-danger:  #dc3545;
    --sc-success: #198754;
    --sc-amber:   #F59E0B;
}

/* ── Page ── */
.sch-page {
    min-height: 100vh;
    background: var(--sc-surface);
    padding-bottom: 60px;
}

/* ── Hero ── */
.sch-hero {
    background: linear-gradient(135deg, var(--sc-navy) 0%, var(--sc-mid) 50%, var(--sc-blue) 100%);
    padding: 36px 0 32px;
    margin-bottom: 32px;
}
.sch-hero-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.sch-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.12);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; color: #e0e8ff;
    flex-shrink: 0;
}
.sch-hero-title {
    font-size: 24px; font-weight: 700;
    color: #fff; margin: 0;
    letter-spacing: -.3px;
}
.sch-hero-sub {
    font-size: 13px; color: rgba(220,228,255,.7);
    margin: 4px 0 0;
}

/* ── Container & layout ── */
.sch-container { padding: 0 20px; }
.sch-layout {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 900px) {
    .sch-layout { grid-template-columns: 1fr; }
}

/* ── Card ── */
.sch-card {
    background: var(--sc-white);
    border: 1.5px solid var(--sc-border);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(15,52,96,.06);
}
.sch-card-header {
    padding: 14px 20px;
    font-size: 13px; font-weight: 700;
    color: var(--sc-text);
    border-bottom: 1.5px solid var(--sc-border);
    background: var(--sc-surface);
    display: flex; align-items: center;
    letter-spacing: .1px;
}
.sch-card-body { padding: 20px; }
.sch-slot-card { margin-bottom: 16px; }
.sch-slot-ops {
    font-size: 11px; color: var(--sc-slate);
    font-weight: 500;
}

/* ── Kalender ── */
.sch-cal-nav {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.sch-cal-label {
    font-size: 14px; font-weight: 600;
    color: var(--sc-text);
}
.sch-cal-btn {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: var(--sc-surface);
    border: 1.5px solid var(--sc-border);
    color: var(--sc-slate);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 18px;
    transition: border-color .2s, color .2s;
    padding: 0;
}
.sch-cal-btn:hover { border-color: var(--sc-blue); color: var(--sc-blue); }
.sch-cal-dow-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    margin-bottom: 6px;
}
.sch-cal-dow {
    font-size: 10px; font-weight: 600;
    color: var(--sc-slate); padding: 2px 0;
    text-transform: uppercase; letter-spacing: .4px;
}
.sch-cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}
.sch-cal-day {
    text-align: center; padding: 4px 0;
}
.sch-cal-circle {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    font-size: 12px;
    transition: background .15s, color .15s;
    cursor: pointer;
    user-select: none;
}
.sch-cal-circle:hover:not(.sc-day-past):not(.sc-day-empty) {
    background: rgba(15,52,96,.1);
}
.sc-day-past { opacity: .3; cursor: not-allowed; }
.sc-day-today {
    border: 2px solid var(--sc-blue);
    color: var(--sc-blue);
    font-weight: 700;
}
.sc-day-selected {
    background: var(--sc-blue) !important;
    color: #fff !important;
    font-weight: 700;
}
.sc-day-empty { opacity: .25; cursor: default; color: #adb5bd; }

/* ── Legenda ── */
.sch-legend-card {
    background: var(--sc-white);
    border: 1.5px solid var(--sc-border);
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 16px;
    display: flex; gap: 20px; flex-wrap: wrap;
}
.sch-legend-row {
    display: flex; align-items: center; gap: 7px;
}
.sch-leg-dot {
    width: 12px; height: 12px;
    border-radius: 4px; flex-shrink: 0;
}
.sch-leg-available { border: 2px solid var(--sc-blue); background: transparent; }
.sch-leg-half      { border: 2px solid var(--sc-amber); background: #FFF8E1; }
.sch-leg-full      { background: var(--sc-border); }
.sch-leg-label     { font-size: 11px; color: var(--sc-slate); }

/* ── Slot grid ── */
.sch-slot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 10px;
}
.sch-slot-btn {
    padding: 12px 8px;
    border-radius: 10px;
    border: 2px solid;
    font-size: 12px; font-weight: 600;
    text-align: center;
    cursor: pointer;
    transition: transform .12s, box-shadow .12s;
    user-select: none;
    background: none;
    width: 100%;
}
.sch-slot-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15,52,96,.15);
}
.sch-slot-available {
    border-color: var(--sc-blue);
    color: var(--sc-blue);
    background: transparent;
}
.sch-slot-available.rs-slot-selected,
.sch-slot-available:active {
    background: var(--sc-blue);
    color: #fff;
}
.sch-slot-half {
    border-color: var(--sc-amber);
    background: #FFF8E1;
    color: #92400E;
}
.sch-slot-half.rs-slot-selected {
    background: var(--sc-amber);
    color: #fff;
    border-color: var(--sc-amber);
}
.sch-slot-full {
    border-color: var(--sc-border);
    background: var(--sc-surface);
    color: #adb5bd;
    text-decoration: line-through;
    cursor: not-allowed;
}
.sch-slot-time  { display: block; font-size: 13px; font-weight: 700; }
.sch-slot-avail { display: block; font-size: 10px; margin-top: 3px; font-weight: 400; }

/* ── Placeholder & loading ── */
.sch-placeholder {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 48px 24px; text-align: center;
}
.sch-placeholder-icon { font-size: 40px; color: var(--sc-border); margin-bottom: 12px; }
.sch-placeholder-text { font-size: 13px; color: var(--sc-slate); margin: 0; }
.sch-spinner { color: var(--sc-blue) !important; width: 28px; height: 28px; }

/* ── Reschedule panel ── */
.sch-panel-desc { font-size: 12px; color: var(--sc-slate); margin-bottom: 16px; line-height: 1.6; }
.sch-input-wrap { margin-bottom: 0; }
.sch-label { display: block; font-size: 12px; font-weight: 600; color: var(--sc-text); margin-bottom: 6px; }
.sch-input {
    width: 100%; padding: 10px 14px;
    font-size: 13px; color: var(--sc-text);
    background: var(--sc-white);
    border: 1.5px solid var(--sc-border);
    border-radius: 10px; outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.sch-input::placeholder { color: #adb5bd; }
.sch-input:focus { border-color: var(--sc-blue); box-shadow: 0 0 0 3px rgba(15,52,96,.1); }
.sch-error { font-size: 11px; color: var(--sc-danger); margin-top: 4px; }

/* ── Tombol ── */
.sch-btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 11px 20px; font-size: 13px; font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, var(--sc-blue), var(--sc-navy));
    border: none; border-radius: 50px; cursor: pointer;
    transition: opacity .2s, transform .15s;
    box-shadow: 0 4px 14px rgba(15,52,96,.25);
}
.sch-btn-primary:hover { opacity: .9; transform: translateY(-1px); }
.sch-btn-secondary {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 11px 20px; font-size: 13px; font-weight: 500;
    color: var(--sc-slate); background: var(--sc-white);
    border: 1.5px solid var(--sc-border);
    border-radius: 50px; cursor: pointer;
    transition: border-color .2s, color .2s;
}
.sch-btn-secondary:hover { border-color: var(--sc-blue); color: var(--sc-blue); }
.sch-btn-link {
    background: none; border: none; padding: 0;
    font-size: 12px; color: var(--sc-blue);
    cursor: pointer; text-decoration: underline;
    display: inline-flex; align-items: center;
}
.sch-step-label { font-size: 12px; font-weight: 600; color: var(--sc-text); }

/* ── Booking list (hasil pencarian) ── */
.rs-booking-item {
    border: 1.5px solid var(--sc-border);
    border-radius: 12px; padding: 14px;
    cursor: pointer; margin-bottom: 10px;
    transition: border-color .2s, background .2s;
}
.rs-booking-item:hover { border-color: var(--sc-blue); background: #f5f8ff; }
.rs-booking-item.rs-item-selected { border-color: var(--sc-blue); background: #f0f4ff; }
.rs-item-order { font-size: 10px; color: var(--sc-slate); margin: 0 0 4px; font-weight: 600; letter-spacing: .3px; }
.rs-item-packet { font-size: 13px; font-weight: 700; color: var(--sc-text); margin: 0 0 6px; }
.rs-item-schedule {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.rs-item-badge {
    font-size: 11px; padding: 3px 10px;
    border-radius: 20px;
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    color: var(--sc-slate);
}
.rs-item-status-paid   { background: #d1fae5; border-color: #6ee7b7; color: #065f46; }
.rs-item-status-unpaid { background: #fef3c7; border-color: #fcd34d; color: #92400e; }

/* ── Booking terpilih badge ── */
.rs-booking-badge {
    background: #f0f4ff;
    border: 1.5px solid rgba(15,52,96,.15);
    border-radius: 10px; padding: 12px 14px;
    font-size: 12px; color: var(--sc-blue);
    line-height: 1.6;
}
.rs-booking-badge strong { display: block; font-size: 13px; margin-bottom: 2px; }

/* ── Jadwal baru terpilih ── */
.rs-chosen-wrap {
    display: flex; gap: 10px;
    background: var(--sc-surface);
    border: 1.5px solid var(--sc-border);
    border-radius: 10px; padding: 12px 14px;
}
.rs-chosen-item { flex: 1; }
.rs-chosen-label { font-size: 10px; color: var(--sc-slate); font-weight: 600; text-transform: uppercase; display: block; }
.rs-chosen-val   { font-size: 14px; font-weight: 700; color: var(--sc-text); }

/* ── Sukses ── */
.rs-success-box { text-align: center; padding: 8px 0; }
.rs-success-icon { font-size: 44px; color: var(--sc-success); margin-bottom: 8px; }
.rs-success-title { font-size: 15px; font-weight: 700; color: var(--sc-text); margin: 0 0 12px; }
.rs-success-detail {
    background: var(--sc-surface);
    border-radius: 10px; padding: 12px;
    font-size: 12px; color: var(--sc-slate);
    text-align: left; line-height: 1.8;
}

/* ── Info card ── */
.sch-info-card {
    background: #f0f4ff;
    border-radius: 10px; padding: 12px 16px;
    font-size: 12px; color: var(--sc-blue);
    display: flex; align-items: flex-start; gap: 4px;
}

/* ── Slot mode reschedule: klik untuk pilih ── */
.sch-slot-btn.rs-mode:not(:disabled) { cursor: pointer; }
.sch-slot-btn.rs-slot-selected {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15,52,96,.2);
}
</style>

{{-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
'use strict';

/* ── State ──────────────────────────────────────────────────────────── */
let _calYear  = new Date().getFullYear();
let _calMonth = new Date().getMonth();
let _selectedDate = null;   // tanggal aktif di kalender
let _slots    = [];         // cache slot terakhir

// Reschedule state
let _rsMode        = false; // sedang dalam mode pilih slot reschedule?
let _rsBookings    = [];    // hasil pencarian booking
let _rsSelected    = null;  // booking yang dipilih untuk direschedule
let _rsNewDate     = null;
let _rsNewTime     = null;
let _rsIdentifier  = '';

/* ── CSRF ── */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* ── Format helpers ── */
const formatDate = (s) => s
    ? new Intl.DateTimeFormat('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' })
        .format(new Date(s + 'T00:00:00'))
    : '—';

const formatDateShort = (s) => s
    ? new Intl.DateTimeFormat('id-ID', { day:'numeric', month:'long', year:'numeric' })
        .format(new Date(s + 'T00:00:00'))
    : '—';

const formatRp = (n) =>
    new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 }).format(n);

const formatDuration = (m) => {
    const j = Math.floor(m / 60), s = m % 60;
    return (j ? j + ' jam' : '') + (s ? (j ? ' ' : '') + s + ' menit' : '');
};

/* ═══════════════════════════════════════════════════════════════════
   KALENDER
═══════════════════════════════════════════════════════════════════ */
function renderCalendar() {
    const raw = new Date(_calYear, _calMonth, 1)
        .toLocaleDateString('id-ID', { month:'long', year:'numeric' });
    document.getElementById('sc-cal-label').textContent =
        raw.charAt(0).toUpperCase() + raw.slice(1);

    const grid        = document.getElementById('sc-cal-days');
    grid.innerHTML    = '';

    const firstDay    = new Date(_calYear, _calMonth, 1).getDay();
    const offset      = firstDay === 0 ? 6 : firstDay - 1;
    const daysInMonth = new Date(_calYear, _calMonth + 1, 0).getDate();
    const prevDays    = new Date(_calYear, _calMonth, 0).getDate();
    const today       = new Date(); today.setHours(0,0,0,0);

    // Padding bulan sebelumnya
    for (let i = 0; i < offset; i++) {
        const cell = makeCalCell(prevDays - offset + 1 + i, true, false, false, false);
        grid.appendChild(cell);
    }

    // Hari bulan ini
    for (let d = 1; d <= daysInMonth; d++) {
        const dt     = new Date(_calYear, _calMonth, d);
        const str    = toDateStr(_calYear, _calMonth, d);
        const isPast = dt < today;
        const isSel  = str === _selectedDate;
        const isTod  = dt.toDateString() === today.toDateString();
        grid.appendChild(makeCalCell(d, false, isPast, isSel, isTod, str));
    }

    // Padding akhir
    const remaining = 42 - offset - daysInMonth;
    for (let i = 1; i <= remaining; i++) {
        grid.appendChild(makeCalCell(i, true, false, false, false));
    }
}

function makeCalCell(num, isEmpty, isPast, isSel, isToday, dateStr) {
    const cell = document.createElement('div');
    cell.className = 'sch-cal-day';

    const circle = document.createElement('div');
    circle.className = 'sch-cal-circle';
    circle.textContent = num;

    if (isEmpty)        circle.classList.add('sc-day-empty');
    else if (isPast)    circle.classList.add('sc-day-past');
    else if (isSel)     circle.classList.add('sc-day-selected');
    else if (isToday)   circle.classList.add('sc-day-today');

    if (!isEmpty && !isPast && dateStr) {
        circle.onclick = () => selectDate(dateStr);
    }

    cell.appendChild(circle);
    return cell;
}

function toDateStr(y, m, d) {
    return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}

function selectDate(dateStr) {
    _selectedDate = dateStr;
    renderCalendar();
    fetchSlots(dateStr);

    // Jika mode reschedule step 3, update jadwal baru
    if (_rsMode && _rsSelected) {
        _rsNewDate = dateStr;
        _rsNewTime = null;
        updateRsChosen();
    }
}

document.getElementById('sc-prev').onclick = () => {
    _calMonth--;
    if (_calMonth < 0) { _calMonth = 11; _calYear--; }
    renderCalendar();
};

document.getElementById('sc-next').onclick = () => {
    _calMonth++;
    if (_calMonth > 11) { _calMonth = 0; _calYear++; }
    renderCalendar();
};

/* ═══════════════════════════════════════════════════════════════════
   FETCH & RENDER SLOT
═══════════════════════════════════════════════════════════════════ */
async function fetchSlots(dateStr) {
    const placeholder = document.getElementById('sc-slot-placeholder');
    const loading     = document.getElementById('sc-slot-loading');
    const grid        = document.getElementById('sc-slot-grid');
    const header      = document.getElementById('sc-slot-header-date');

    placeholder.classList.add('d-none');
    grid.classList.add('d-none');
    loading.classList.remove('d-none');

    header.textContent = 'Memuat slot ' + formatDateShort(dateStr) + '...';

    try {
        const res  = await fetch(`/jadwal/slots?date=${dateStr}`, {
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
        });
        const data = await res.json();
        _slots = data.slots || [];
    } catch {
        _slots = [];
    }

    loading.classList.add('d-none');
    header.textContent = formatDate(dateStr);
    renderSlotGrid(dateStr);
}

function renderSlotGrid(dateStr) {
    const grid = document.getElementById('sc-slot-grid');
    grid.innerHTML = '';
    grid.classList.remove('d-none');

    if (_slots.length === 0) {
        grid.innerHTML = '<p class="text-muted small text-center py-4 w-100">Tidak ada data slot.</p>';
        return;
    }

    _slots.forEach(slot => {
        const btn = document.createElement('button');
        btn.type  = 'button';
        btn.className = 'sch-slot-btn';

        const sisa = slot.max - slot.booked;

        if (!slot.available) {
            btn.classList.add('sch-slot-full');
            btn.disabled = true;
            btn.innerHTML = `<span class="sch-slot-time">${slot.time}</span>
                             <span class="sch-slot-avail">Penuh</span>`;
        } else if (slot.booked === 1) {
            btn.classList.add('sch-slot-half');
            btn.innerHTML = `<span class="sch-slot-time">${slot.time}</span>
                             <span class="sch-slot-avail">Sisa 1</span>`;
        } else {
            btn.classList.add('sch-slot-available');
            btn.innerHTML = `<span class="sch-slot-time">${slot.time}</span>
                             <span class="sch-slot-avail">Tersedia</span>`;
        }

        // Mode reschedule: klik slot untuk memilih
        if (_rsMode && slot.available) {
            btn.classList.add('rs-mode');
            if (slot.time === _rsNewTime && dateStr === _rsNewDate) {
                btn.classList.add('rs-slot-selected');
            }
            btn.onclick = () => selectRsSlot(slot.time, dateStr);
        }

        grid.appendChild(btn);
    });
}

/* ═══════════════════════════════════════════════════════════════════
   RESCHEDULE — STEP 1: Cari Booking
═══════════════════════════════════════════════════════════════════ */
window.rsCariBooking = async function () {
    const identifier = document.getElementById('rs-identifier').value.trim();
    const errEl      = document.getElementById('rs-err-identifier');
    const textEl     = document.getElementById('rs-search-text');
    const loadEl     = document.getElementById('rs-search-loading');

    errEl.classList.add('d-none');
    document.getElementById('rs-identifier').style.borderColor = '';

    if (!identifier) {
        errEl.textContent = 'Masukkan nomor HP atau Order ID.';
        errEl.classList.remove('d-none');
        document.getElementById('rs-identifier').style.borderColor = 'var(--sc-danger)';
        return;
    }

    textEl.classList.add('d-none');
    loadEl.classList.remove('d-none');

    try {
        const res  = await fetch('/jadwal/cari-booking', {
            method  : 'POST',
            headers : {
                'Content-Type' : 'application/json',
                'Accept'       : 'application/json',
                'X-CSRF-TOKEN' : csrf(),
            },
            body: JSON.stringify({ identifier }),
        });

        const data = await res.json();

        if (!res.ok || !data.bookings?.length) {
            errEl.textContent = data.message || 'Booking tidak ditemukan.';
            errEl.classList.remove('d-none');
            document.getElementById('rs-identifier').style.borderColor = 'var(--sc-danger)';
            return;
        }

        _rsIdentifier = identifier;
        _rsBookings   = data.bookings;
        renderBookingList();

    } catch {
        errEl.textContent = 'Terjadi kesalahan. Coba lagi.';
        errEl.classList.remove('d-none');
    } finally {
        textEl.classList.remove('d-none');
        loadEl.classList.add('d-none');
    }
};

/* ═══════════════════════════════════════════════════════════════════
   RESCHEDULE — STEP 2: Render Daftar Booking
═══════════════════════════════════════════════════════════════════ */
function renderBookingList() {
    const list = document.getElementById('rs-booking-list');
    list.innerHTML = '';

    _rsBookings.forEach(b => {
        const item = document.createElement('div');
        item.className = 'rs-booking-item';

        const statusClass = b.status === 'sudah dibayar' ? 'rs-item-status-paid' : 'rs-item-status-unpaid';
        const statusLabel = b.status === 'sudah dibayar' ? 'Sudah Dibayar' : 'Belum Dibayar';

        item.innerHTML = `
            <p class="rs-item-order">${b.order_id}</p>
            <p class="rs-item-packet">${b.packet_name} · ${formatDuration(b.duration)}</p>
            <div class="rs-item-schedule">
                <span class="rs-item-badge">${formatDateShort(b.session_date)}</span>
                <span class="rs-item-badge">${b.session_time} WIB</span>
                <span class="rs-item-badge ${statusClass}">${statusLabel}</span>
            </div>
        `;

        item.onclick = () => selectBookingForReschedule(b, item);
        list.appendChild(item);
    });

    document.getElementById('rs-step-1').classList.add('d-none');
    document.getElementById('rs-step-2').classList.remove('d-none');
}

/* ═══════════════════════════════════════════════════════════════════
   RESCHEDULE — STEP 3: Pilih jadwal baru
═══════════════════════════════════════════════════════════════════ */
function selectBookingForReschedule(booking, itemEl) {
    // Highlight item terpilih
    document.querySelectorAll('.rs-booking-item').forEach(i => i.classList.remove('rs-item-selected'));
    itemEl.classList.add('rs-item-selected');

    _rsSelected = booking;
    _rsNewDate  = null;
    _rsNewTime  = null;
    _rsMode     = true;

    // Isi info badge
    document.getElementById('rs-selected-info').innerHTML =
        `<strong>${booking.packet_name}</strong>
         ${formatDateShort(booking.session_date)} · ${booking.session_time} WIB
         <br><span style="font-size:11px;opacity:.8">Klik tanggal & waktu baru di sebelah kanan →</span>`;

    updateRsChosen();

    document.getElementById('rs-step-2').classList.add('d-none');
    document.getElementById('rs-step-3').classList.remove('d-none');

    // Jika sudah ada tanggal terpilih, refresh slot dalam mode rs
    if (_selectedDate) {
        renderSlotGrid(_selectedDate);
    }
}

function selectRsSlot(time, dateStr) {
    _rsNewDate = dateStr;
    _rsNewTime = time;
    updateRsChosen();
    renderSlotGrid(dateStr); // re-render agar highlight slot terpilih
}

function updateRsChosen() {
    document.getElementById('rs-chosen-date').textContent =
        _rsNewDate ? formatDateShort(_rsNewDate) : '—';
    document.getElementById('rs-chosen-time').textContent =
        _rsNewTime ? _rsNewTime + ' WIB' : '—';
}

/* ═══════════════════════════════════════════════════════════════════
   RESCHEDULE — STEP 4: Simpan
═══════════════════════════════════════════════════════════════════ */
window.rsSimpan = async function () {
    const errEl   = document.getElementById('rs-err-slot');
    const textEl  = document.getElementById('rs-save-text');
    const loadEl  = document.getElementById('rs-save-loading');

    errEl.classList.add('d-none');

    if (!_rsNewDate || !_rsNewTime) {
        errEl.textContent = 'Pilih tanggal dan waktu baru terlebih dahulu.';
        errEl.classList.remove('d-none');
        return;
    }

    textEl.classList.add('d-none');
    loadEl.classList.remove('d-none');

    try {
        const res  = await fetch('/jadwal/reschedule', {
            method  : 'POST',
            headers : {
                'Content-Type' : 'application/json',
                'Accept'       : 'application/json',
                'X-CSRF-TOKEN' : csrf(),
            },
            body: JSON.stringify({
                transaction_id : _rsSelected.transaction_id,
                new_date       : _rsNewDate,
                new_time       : _rsNewTime,
                identifier     : _rsIdentifier,
            }),
        });

        const data = await res.json();

        if (!res.ok) {
            errEl.textContent = data.message || 'Gagal menyimpan. Coba lagi.';
            errEl.classList.remove('d-none');
            return;
        }

        // Tampilkan sukses
        document.getElementById('rs-success-detail').innerHTML =
            `<div><strong>Paket:</strong> ${_rsSelected.packet_name}</div>
             <div><strong>Dari:</strong> ${formatDateShort(data.old_date)} · ${data.old_time} WIB</div>
             <div><strong>Ke:</strong> ${formatDateShort(data.new_date)} · ${data.new_time} WIB</div>
             <div><strong>Order ID:</strong> ${data.order_id}</div>`;

        document.getElementById('rs-step-3').classList.add('d-none');
        document.getElementById('rs-step-4').classList.remove('d-none');

        // Refresh slot di kalender
        _rsMode = false;
        if (_selectedDate) fetchSlots(_selectedDate);

    } catch {
        errEl.textContent = 'Terjadi kesalahan. Coba lagi.';
        errEl.classList.remove('d-none');
    } finally {
        textEl.classList.remove('d-none');
        loadEl.classList.add('d-none');
    }
};

/* ═══════════════════════════════════════════════════════════════════
   RESCHEDULE — Reset & navigasi
═══════════════════════════════════════════════════════════════════ */
window.rsReset = function () {
    _rsMode       = false;
    _rsBookings   = [];
    _rsSelected   = null;
    _rsNewDate    = null;
    _rsNewTime    = null;
    _rsIdentifier = '';

    document.getElementById('rs-identifier').value = '';
    document.getElementById('rs-identifier').style.borderColor = '';
    document.getElementById('rs-err-identifier').classList.add('d-none');

    ['rs-step-2','rs-step-3','rs-step-4'].forEach(id =>
        document.getElementById(id).classList.add('d-none'));
    document.getElementById('rs-step-1').classList.remove('d-none');

    if (_selectedDate) renderSlotGrid(_selectedDate);
};

window.rsBackToList = function () {
    _rsMode    = false;
    _rsNewDate = null;
    _rsNewTime = null;
    _rsSelected = null;

    document.getElementById('rs-step-3').classList.add('d-none');
    document.getElementById('rs-step-2').classList.remove('d-none');

    if (_selectedDate) renderSlotGrid(_selectedDate);
};


renderCalendar();

// Auto-load hari ini
const todayStr = toDateStr(_calYear, _calMonth, new Date().getDate());
selectDate(todayStr);

})();
</script>

