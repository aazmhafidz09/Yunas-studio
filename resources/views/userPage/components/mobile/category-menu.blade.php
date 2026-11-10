{{-- =====================================================================
     MOBILE MENU — Kategori Paket (dinamis dari database)
     
     Alur klik:
     1. User klik produk (misal Wedding)
     2. Muncul modal detail → daftar paket + harga (Gold, Platinum, Silver...)
     3. User klik "Booking Sekarang" → modal booking terbuka, produk & paket
        sudah terpreselect otomatis
     ===================================================================== --}}

{{-- ── Grid Produk ─────────────────────────────────────────────────────── --}}
@php
    $productIcons = [
        'Birthday'           => ['icon' => 'mdi-party-popper',       'color' => '#FBEAF0', 'icolor' => '#993556'],
        'Couple'             => ['icon' => 'mdi-heart-outline',       'color' => '#FAECE7', 'icolor' => '#993C1D'],
        'Family Cetak'       => ['icon' => 'mdi-account-group',       'color' => '#E1F5EE', 'icolor' => '#0F6E56'],
        'Family Non Cetak'   => ['icon' => 'mdi-account-multiple',    'color' => '#E1F5EE', 'icolor' => '#0F6E56'],
        'Graduation Cetak'   => ['icon' => 'mdi-school-outline',      'color' => '#EEEDFE', 'icolor' => '#534AB7'],
        'Graduation Non Cetak'=> ['icon'=> 'mdi-school',              'color' => '#EEEDFE', 'icolor' => '#534AB7'],
        'Maternity'          => ['icon' => 'mdi-baby-carriage',       'color' => '#E6F1FB', 'icolor' => '#185FA5'],
        'Group'              => ['icon' => 'mdi-account-multiple-outline','color'=> '#EAF3DE','icolor'=> '#3B6D11'],
        'Personal'           => ['icon' => 'mdi-account-circle-outline','color'=> '#FAEEDA','icolor'=> '#854F0B'],
        'Prawedding'         => ['icon' => 'mdi-ring',                'color' => '#EEEDFE', 'icolor' => '#534AB7'],
        'Pas Foto'           => ['icon' => 'mdi-card-account-details-outline','color'=>'#F1EFE8','icolor'=>'#5F5E5A'],
        'Pas Photo'          => ['icon' => 'mdi-card-account-details-outline','color'=>'#F1EFE8','icolor'=>'#5F5E5A'],
    ];

    // Fallback jika nama produk tidak ada di mapping
    $defaultIcon = ['icon' => 'mdi-camera-outline', 'color' => '#F1EFE8', 'icolor' => '#5F5E5A'];
@endphp

<div class="mobile-menu-wrap">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="mobile-section-title mb-0">Kategori Paket</span>
        <!-- <small style="font-size:11px; color:#6c757d;">Lihat semua →</small> -->
    </div>

    <div class="mobile-menu-grid">
        <div class="row g-3 text-center">
            @foreach($packets as $productName => $packetGroup)
                @php
                    $product = $packetGroup->first()->product;
                    $meta    = $productIcons[$productName] ?? $defaultIcon;
                @endphp

                <div class="col-3">
                    <a href="#"
                       class="mobile-menu-item"
                       onclick="showProductDetail('{{ $product->id }}'); return false;"
                       title="{{ $productName }}">

                        <div class="mobile-menu-icon"
                             style="background:{{ $meta['color'] }}; border-radius:16px;">
                            <i class="mdi {{ $meta['icon'] }}"
                               style="font-size:26px; color:{{ $meta['icolor'] }};"></i>
                        </div>

                        <small class="d-block text-truncate"
                               style="max-width:70px; margin:0 auto; font-size:10px; color:#343a40;">
                            {{ $productName }}
                        </small>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     CSS
═══════════════════════════════════════════════════════════════════════ --}}
<style>
    /* Kartu paket di modal detail */
    .packet-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        transition: all .2s ease;
        position: relative;
    }
    .packet-card:hover {
        border-color: #198754;
        background: #f8fff9;
    }
    .packet-card.selected {
        border-color: #198754;
        background: #f0fdf4;
    }
    .packet-card.selected::after {
        content: '\F012C'; /* mdi-check-circle */
        font-family: 'Material Design Icons';
        position: absolute;
        top: 10px; right: 12px;
        color: #198754;
        font-size: 20px;
    }
    .packet-price-badge {
        background: #198754;
        color: white;
        border-radius: 20px;
        padding: 2px 12px;
        font-size: 13px;
        font-weight: 600;
    }
    .packet-feature-list {
        list-style: none;
        padding: 0; margin: 8px 0 0;
    }
    .packet-feature-list li {
        font-size: 12px;
        color: #6c757d;
        padding: 2px 0;
    }
    .packet-feature-list li::before {
        content: '✓ ';
        color: #198754;
        font-weight: 600;
    }
</style>

