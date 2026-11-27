@extends('layouts.master')

@section('title')
    Bagikan Promo
@endsection

@section('css')
<style>
    .wa-preview-box {
        background-color: #e7f8ee;
        border: 1px solid #b7e4c7;
        border-radius: 0.5rem;
        padding: 0.85rem 1rem;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 13px;
        color: #1b4332;
        min-height: 76px;
    }
    .promo-item-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 0.5rem;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .promo-item-card.is-editing {
        border-color: #5b73e8;
        background-color: rgba(91, 115, 232, 0.06);
    }
    .placeholder-chip {
        cursor: pointer;
        user-select: none;
    }
    .placeholder-chip:hover {
        opacity: 0.85;
    }
    .wa-promo-btn {
        position: relative;
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .wa-promo-btn .wa-sent-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background-color: #34c38f;
        color: #fff;
        font-size: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1.5px solid #fff;
        line-height: 1;
    }
</style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Management Data @endslot
        @slot('title') Bagikan Promo @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-all me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-block-helper me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-block-helper me-2"></i>{{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    </div>

    @php
        $sampleTransaksi = $transactions->first();
        $sampleCustomerName = $sampleTransaksi?->customer_name ?? 'Budi';
        $sampleProductName = $sampleTransaksi?->packet?->product?->name ?? 'Studio Foto';
        $samplePacketName = $sampleTransaksi?->packet?->name ?? 'Paket Wisuda';
        $sampleDate = $sampleTransaksi?->created_at ? $sampleTransaksi->created_at->format('d M Y') : now()->format('d M Y');
        $sampleNota = $sampleTransaksi?->receipt_code ?? 'INV/20261127/001';
    @endphp

    {{-- BAGIAN ATAS: FORM TAMBAH/EDIT PROMO & DAFTAR SEMUA PROMO TERSIMPAN --}}
    <div class="row">
        {{-- FORM NAMA PROMO & ISI PROMO (DEFAULT: TAMBAH PROMO BARU) --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">
                                <i class="uil-megaphone text-primary me-1"></i>
                                <span id="promoFormHeading">Tambah Promo Baru</span>
                            </h4>
                            <p class="card-title-desc mb-0">
                                Tambahkan satu atau lebih promo di sini. Setiap promo yang disimpan otomatis muncul sebagai tombol WhatsApp di kolom <strong>Bagikan WhatsApp</strong> pada tabel pelanggan.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnCancelEdit" onclick="resetPromoForm()">
                            <i class="uil-times me-1"></i>Batal Edit
                        </button>
                    </div>

                    <form method="POST"
                          id="promoForm"
                          action="{{ route('promos.store') }}"
                          data-store-url="{{ route('promos.store') }}"
                          data-update-url-template="{{ route('promos.update', ['promo' => '__ID__']) }}">
                        @csrf
                        <input type="hidden" name="_method" id="promoFormMethod" value="POST">

                        <div class="mb-3">
                            <label for="promoNameInput" class="form-label fw-semibold">Nama Promo <span class="text-danger">*</span></label>
                            <input type="text"
                                   id="promoNameInput"
                                   name="name"
                                   class="form-control"
                                   maxlength="255"
                                   placeholder="Contoh: Promo Lebaran / Diskon Wisuda 20%"
                                   value="{{ old('name') }}"
                                   required>
                        </div>

                        <div class="mb-2">
                            <label for="promoContentInput" class="form-label fw-semibold">Isi Promo <span class="text-danger">*</span></label>
                            <textarea id="promoContentInput"
                                      name="content"
                                      class="form-control"
                                      rows="4"
                                      maxlength="5000"
                                      placeholder="Tulis isi pesan promo yang akan langsung dikirim ke WhatsApp pelanggan..."
                                      required>{{ old('content') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="font-size-12 text-muted mb-1">Sisipkan data transaksi otomatis (opsional):</div>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-soft-primary text-primary placeholder-chip px-2 py-1" onclick="insertPlaceholder('{nama}')">+ {nama}</span>
                                <span class="badge bg-soft-primary text-primary placeholder-chip px-2 py-1" onclick="insertPlaceholder('{produk}')">+ {produk}</span>
                                <span class="badge bg-soft-primary text-primary placeholder-chip px-2 py-1" onclick="insertPlaceholder('{paket}')">+ {paket}</span>
                                <span class="badge bg-soft-primary text-primary placeholder-chip px-2 py-1" onclick="insertPlaceholder('{tanggal}')">+ {tanggal}</span>
                                <span class="badge bg-soft-primary text-primary placeholder-chip px-2 py-1" onclick="insertPlaceholder('{nota}')">+ {nota}</span>
                            </div>
                        </div>

                        {{-- PREVIEW PESAN WHATSAPP --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold font-size-13 text-success mb-1">
                                <i class="uil-whatsapp me-1"></i>Preview Pesan WhatsApp
                            </label>
                            <div id="waMessagePreview" class="wa-preview-box"></div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary waves-effect waves-light" id="promoSubmitBtn">
                                <i class="uil-plus-circle me-1"></i>Tambah Promo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- DAFTAR SEMUA PROMO TERSIMPAN --}}
        <div class="col-lg-5 mt-3 mt-lg-0">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="card-title mb-1">Promo Tersimpan ({{ $promos->count() }})</h4>
                            <p class="card-title-desc mb-0">Semua promo di bawah otomatis tampil sebagai tombol WhatsApp di tabel pelanggan.</p>
                        </div>
                    </div>

                    @if($promos->isEmpty())
                        <div class="text-center text-muted my-auto py-4">
                            <i class="uil-megaphone font-size-24 d-block mb-2"></i>
                            Belum ada promo tersimpan.<br>
                            <small>Tambahkan promo pertama Anda di form samping.</small>
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto;">
                            @foreach($promos as $promo)
                                <div class="p-3 promo-item-card" data-promo-card-id="{{ $promo->id }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <div class="fw-semibold text-truncate" title="{{ $promo->name }}">
                                                <i class="uil uil-whatsapp text-success me-1"></i>{{ $promo->name }}
                                            </div>
                                            <div class="text-muted font-size-12 mt-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ $promo->content }}
                                            </div>
                                            <div class="text-muted font-size-11 mt-1">
                                                <span class="js-promo-share-count" data-promo-id="{{ $promo->id }}">{{ $promo->shares_count }}</span>x dibagikan
                                                &middot; {{ $promo->updated_at->format('d M Y H:i') }}
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2"
                                                    onclick="editPromo({{ $promo->id }}, @json($promo->name), @json($promo->content))"
                                                    title="Edit Promo">
                                                <i class="uil-edit"></i>
                                            </button>
                                            <form method="POST"
                                                  action="{{ route('promos.destroy', $promo->id) }}"
                                                  onsubmit="return confirm('Hapus promo &quot;{{ addslashes($promo->name) }}&quot;?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus Promo">
                                                    <i class="uil-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN BAWAH: DATA TRANSAKSI PELANGGAN & TOMBOL SHARE WHATSAPP PER PROMO --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Data Pelanggan dari Transaksi (Termasuk Transaksi Sebelumnya)</h4>
                            <p class="card-title-desc mb-0">
                                Ditemukan <strong>{{ $totalMatchedTransactions }}</strong> transaksi
                                (<strong>{{ $totalWithPhone }}</strong> memiliki nomor WhatsApp).
                                Arahkan kursor ke ikon <span class="badge bg-success"><i class="uil uil-whatsapp"></i></span> untuk melihat nama promonya, lalu klik untuk langsung mengirim ke WhatsApp pelanggan.
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @php
                                $baseFilterParams = request()->except(['start_date', 'end_date', 'page']);
                                $isAllTime = !request()->filled('start_date') && !request()->filled('end_date');
                            @endphp
                            <a href="{{ route('promos.index', $baseFilterParams) }}"
                               class="btn btn-sm {{ $isAllTime ? 'btn-primary' : 'btn-outline-secondary' }}">
                                Semua Waktu
                            </a>
                            <a href="{{ route('promos.index', array_merge($baseFilterParams, ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString()])) }}"
                               class="btn btn-sm btn-outline-secondary">
                                Bulan Ini
                            </a>
                            <a href="{{ route('promos.index', array_merge($baseFilterParams, ['start_date' => now()->subMonths(3)->toDateString(), 'end_date' => now()->toDateString()])) }}"
                               class="btn btn-sm btn-outline-secondary">
                                3 Bulan Terakhir
                            </a>
                        </div>
                    </div>

                    {{-- FILTER DATA TRANSAKSI --}}
                    <form method="GET" action="{{ route('promos.index') }}" class="bg-light rounded p-3 mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label font-size-12 fw-semibold mb-1">Cari Pelanggan / HP / Nota</label>
                                <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nama, no. HP, atau no. nota">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label font-size-12 fw-semibold mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label font-size-12 fw-semibold mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label font-size-12 fw-semibold mb-1">Produk / Paket</label>
                                <select name="packet_id" class="form-select form-select-sm">
                                    <option value="">Semua Paket</option>
                                    @foreach($packetsForFilter as $productName => $packets)
                                        <optgroup label="{{ $productName }}">
                                            @foreach($packets as $packet)
                                                <option value="{{ $packet->id }}" {{ (string) request('packet_id') === (string) $packet->id ? 'selected' : '' }}>
                                                    {{ $packet->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-1">
                                <label class="form-label font-size-12 fw-semibold mb-1">Per Hal</label>
                                <select name="per_page" class="form-select form-select-sm">
                                    @foreach($perPageOptions as $opt)
                                        <option value="{{ $opt }}" {{ $perPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="uil-filter me-1"></i>Filter
                                </button>
                                <a href="{{ route('promos.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                                    <i class="uil-redo"></i>
                                </a>
                            </div>
                        </div>
                        <div class="row g-2 align-items-center mt-2">
                            <div class="col-6 col-md-3">
                                <select name="payment_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Pembayaran</option>
                                    @foreach($paymentStatuses as $ps)
                                        <option value="{{ $ps }}" {{ request('payment_status') === $ps ? 'selected' : '' }}>{{ ucwords($ps) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select name="process_status" class="form-select form-select-sm">
                                    <option value="">Semua Status Pengerjaan</option>
                                    @foreach($processStatuses as $prs)
                                        <option value="{{ $prs }}" {{ request('process_status') === $prs ? 'selected' : '' }}>{{ $prs }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="unique_phone" value="1" id="uniquePhoneCheck" {{ request()->boolean('unique_phone') ? 'checked' : '' }} onchange="this.form.submit()">
                                    <label class="form-check-label font-size-13" for="uniquePhoneCheck">
                                        Gabungkan nomor HP yang sama (tampilkan 1 transaksi terbaru per pelanggan)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- TABEL DATA TRANSAKSI + MULTI LOGO WHATSAPP PER PROMO --}}
                    <div class="table-responsive">
                        <table class="table table-hover table-centered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Pelanggan</th>
                                    <th>Produk & Paket</th>
                                    <th>Total Biaya</th>
                                    <th>Tanggal Transaksi</th>
                                    <th>Status</th>
                                    <th style="min-width: 150px;">Bagikan WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    @php
                                        $normalizedPhone = \App\Models\Promo::normalizePhone($trx->phone_number);
                                        $productName = $trx->packet?->product?->name ?? '-';
                                        $packetName = $trx->packet?->name ?? '-';
                                        $trxSharedPromos = $sharedMap[$trx->transaction_id] ?? [];
                                    @endphp
                                    <tr>
                                        <td>{{ $transactions->firstItem() + $loop->index }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $trx->customer_name }}</div>
                                            <div class="text-muted font-size-12">
                                                <i class="bx bx-phone me-1"></i>{{ $trx->phone_number ?: '-' }}
                                                <span class="ms-1 text-secondary">&middot; #{{ $trx->receipt_code }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $productName }}</span>
                                            <div class="text-muted font-size-12">{{ $packetName }}</div>
                                        </td>
                                        <td class="fw-semibold text-nowrap">Rp {{ number_format($trx->total_price ?? 0, 0, ',', '.') }}</td>
                                        <td class="text-nowrap font-size-13">
                                            {{ $trx->created_at ? $trx->created_at->format('d M Y, H:i') : '-' }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $trx->status === 'sudah dibayar' ? 'bg-success' : ($trx->status === 'dp' ? 'bg-info' : 'bg-warning') }}">
                                                {{ ucwords($trx->status) }}
                                            </span>
                                            <div class="mt-1">
                                                <span class="badge bg-soft-secondary text-secondary font-size-11">{{ $trx->process_status }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if(!$normalizedPhone)
                                                <span class="badge bg-soft-secondary text-muted">No. HP kosong</span>
                                            @elseif($promos->isEmpty())
                                                <span class="text-muted font-size-12">
                                                    <i class="uil-info-circle me-1"></i>Tambahkan promo di atas terlebih dahulu
                                                </span>
                                            @else
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($promos as $promo)
                                                        @php
                                                            $waUrl = \App\Models\Promo::buildWhatsappUrl($promo->name, $promo->content, $trx);
                                                            $sentAt = $trxSharedPromos[$promo->id] ?? null;
                                                            $tooltipText = 'Promo: ' . $promo->name . ($sentAt ? ' (Terkirim ' . $sentAt->format('d M H:i') . ')' : '');
                                                        @endphp
                                                        <a href="{{ $waUrl }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="btn btn-sm {{ $sentAt ? 'btn-outline-success' : 'btn-success' }} wa-promo-btn js-wa-share-btn"
                                                           data-promo-id="{{ $promo->id }}"
                                                           data-promo-name="{{ $promo->name }}"
                                                           data-transaction-id="{{ $trx->transaction_id }}"
                                                           data-bs-toggle="tooltip"
                                                           data-bs-placement="top"
                                                           title="{{ $tooltipText }}"
                                                           aria-label="{{ $tooltipText }}">
                                                            <i class="uil uil-whatsapp font-size-18"></i>
                                                            @if($sentAt)
                                                                <span class="wa-sent-badge js-sent-icon"><i class="uil uil-check"></i></span>
                                                            @endif
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="uil-receipt font-size-24 d-block mb-2"></i>
                                            Tidak ada data transaksi yang cocok dengan filter.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $transactions->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    const LOG_SHARE_URL = @json(route('promos.log-share'));
    const CSRF_TOKEN = @json(csrf_token());
    const SAMPLE_DATA = {
        customerName: @json($sampleCustomerName),
        productName: @json($sampleProductName),
        packetName: @json($samplePacketName),
        trxDate: @json($sampleDate),
        receiptCode: @json($sampleNota),
    };

    function buildPromoMessage(promoName, promoContent, data) {
        const name = (promoName || '').trim();
        const content = (promoContent || '').trim();
        if (!name && !content) {
            return '';
        }

        const replacements = {
            '{nama}': data.customerName || 'Pelanggan',
            '{produk}': data.productName || '-',
            '{paket}': data.packetName || '-',
            '{tanggal}': data.trxDate || '-',
            '{nota}': data.receiptCode || '-',
        };

        const applyVars = (str) => {
            let out = str;
            for (const [k, v] of Object.entries(replacements)) {
                out = out.split(k).join(v);
            }
            return out;
        };

        const hasNamePlaceholder = content.includes('{nama}');
        const renderedName = applyVars(name);
        const renderedContent = applyVars(content);

        const parts = [];
        if (!hasNamePlaceholder) {
            parts.push('Halo Kak *' + (data.customerName || 'Pelanggan') + '*');
        }
        if (renderedName) {
            parts.push('*' + renderedName + '*');
        }
        if (renderedContent) {
            parts.push(renderedContent);
        }

        return parts.join('\n\n');
    }

    function refreshPreview() {
        const promoName = document.getElementById('promoNameInput')?.value || '';
        const promoContent = document.getElementById('promoContentInput')?.value || '';
        const previewEl = document.getElementById('waMessagePreview');
        if (!previewEl) return;

        const sampleMsg = buildPromoMessage(promoName, promoContent, SAMPLE_DATA);
        previewEl.textContent = sampleMsg || 'Isi Nama Promo dan Isi Promo untuk melihat preview pesan WhatsApp...';
    }

    function insertPlaceholder(token) {
        const textarea = document.getElementById('promoContentInput');
        if (!textarea) return;
        const start = textarea.selectionStart ?? textarea.value.length;
        const end = textarea.selectionEnd ?? textarea.value.length;
        textarea.value = textarea.value.slice(0, start) + token + textarea.value.slice(end);
        textarea.focus();
        textarea.setSelectionRange(start + token.length, start + token.length);
        refreshPreview();
    }

    function editPromo(id, name, content) {
        const form = document.getElementById('promoForm');
        const methodInput = document.getElementById('promoFormMethod');
        const nameInput = document.getElementById('promoNameInput');
        const contentInput = document.getElementById('promoContentInput');
        const heading = document.getElementById('promoFormHeading');
        const submitBtn = document.getElementById('promoSubmitBtn');
        const cancelBtn = document.getElementById('btnCancelEdit');

        form.action = form.dataset.updateUrlTemplate.replace('__ID__', id);
        methodInput.value = 'PUT';
        nameInput.value = name || '';
        contentInput.value = content || '';
        heading.textContent = 'Edit Promo';
        submitBtn.innerHTML = '<i class="uil-save me-1"></i>Simpan Perubahan';
        cancelBtn.classList.remove('d-none');

        document.querySelectorAll('.promo-item-card').forEach((c) => {
            c.classList.toggle('is-editing', String(c.dataset.promoCardId) === String(id));
        });

        refreshPreview();
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        nameInput.focus();
    }

    function resetPromoForm() {
        const form = document.getElementById('promoForm');
        const methodInput = document.getElementById('promoFormMethod');
        const nameInput = document.getElementById('promoNameInput');
        const contentInput = document.getElementById('promoContentInput');
        const heading = document.getElementById('promoFormHeading');
        const submitBtn = document.getElementById('promoSubmitBtn');
        const cancelBtn = document.getElementById('btnCancelEdit');

        form.action = form.dataset.storeUrl;
        methodInput.value = 'POST';
        nameInput.value = '';
        contentInput.value = '';
        heading.textContent = 'Tambah Promo Baru';
        submitBtn.innerHTML = '<i class="uil-plus-circle me-1"></i>Tambah Promo';
        cancelBtn.classList.add('d-none');

        document.querySelectorAll('.promo-item-card').forEach((c) => c.classList.remove('is-editing'));

        refreshPreview();
        nameInput.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.getElementById('promoNameInput');
        const contentInput = document.getElementById('promoContentInput');

        if (nameInput) nameInput.addEventListener('input', refreshPreview);
        if (contentInput) contentInput.addEventListener('input', refreshPreview);

        refreshPreview();

        // Inisialisasi Bootstrap tooltip agar keterangan nama promo langsung muncul saat hover ikon WA
        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }

        document.querySelectorAll('.js-wa-share-btn').forEach((btn) => {
            btn.addEventListener('click', function () {
                const promoId = this.dataset.promoId;
                const promoName = this.dataset.promoName || '';
                const transactionId = this.dataset.transactionId;
                const clickedBtn = this;

                if (promoId && transactionId) {
                    fetch(LOG_SHARE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        body: JSON.stringify({
                            promo_id: promoId,
                            transaction_id: transactionId,
                        }),
                    })
                    .then((r) => r.json())
                    .then((res) => {
                        if (res && res.success) {
                            clickedBtn.classList.remove('btn-success');
                            clickedBtn.classList.add('btn-outline-success');
                            if (!clickedBtn.querySelector('.js-sent-icon')) {
                                const badge = document.createElement('span');
                                badge.className = 'wa-sent-badge js-sent-icon';
                                badge.innerHTML = '<i class="uil uil-check"></i>';
                                clickedBtn.appendChild(badge);
                            }
                            const newTitle = 'Promo: ' + promoName + ' (Terkirim ' + res.shared_at + ')';
                            clickedBtn.setAttribute('title', newTitle);
                            clickedBtn.setAttribute('data-bs-original-title', newTitle);
                            if (window.bootstrap && bootstrap.Tooltip) {
                                const tip = bootstrap.Tooltip.getInstance(clickedBtn);
                                if (tip) {
                                    tip.setContent ? tip.setContent({ '.tooltip-inner': newTitle }) : tip.dispose();
                                    bootstrap.Tooltip.getOrCreateInstance(clickedBtn);
                                }
                            }
                            const countEl = document.querySelector('.js-promo-share-count[data-promo-id="' + promoId + '"]');
                            if (countEl) {
                                countEl.textContent = String((parseInt(countEl.textContent, 10) || 0) + 1);
                            }
                        }
                    })
                    .catch(() => {});
                }
            });
        });
    });
</script>
@endsection
