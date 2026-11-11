@extends('layouts.master')

@section('title')
    Review Pilihan Foto
@endsection

@section('css')
<style>
    .sel-card { border: 1px solid rgba(0,0,0,.08); border-radius: .75rem; overflow: hidden; }
    .sel-card.is-missing { border-color: #f46a6a; }
    .sel-img { position: relative; padding-top: 75%; background: #f1f3f7; cursor: pointer; }
    .sel-img img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .sel-no { position: absolute; top: 6px; left: 6px; z-index: 2; }
    .modal-preview-img { max-height: 75vh; max-width: 100%; display: block; margin: 0 auto; border-radius: .5rem; }
</style>
@endsection

@php
    $statusMap = [
        'submitted' => ['Menunggu Review', 'bg-warning'],
        'approved'  => ['Disetujui', 'bg-success'],
        'rejected'  => ['Ditolak', 'bg-danger'],
    ];
    [$statusLabel, $statusBadge] = $statusMap[$transaksi->edit_selection_status] ?? ['Belum Diajukan', 'bg-secondary'];
    $count = $photos->count();
    $overLimit = $retouchLimit > 0 && $count > $retouchLimit;
@endphp

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Pilihan Foto Edit @endslot
        @slot('title') Review Pilihan Foto @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="mdi mdi-check-all me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="mdi mdi-block-helper me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- RINGKASAN & AKSI --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <a href="{{ route('photo-selections.index', ['status' => $transaksi->edit_selection_status ?? 'draft']) }}" class="text-muted font-size-13 text-decoration-none">
                                <i class="uil-arrow-left me-1"></i>Kembali ke daftar
                            </a>
                            <h4 class="mt-2 mb-1">#{{ $transaksi->receipt_code }} <span class="badge {{ $statusBadge }} font-size-12 align-middle">{{ $statusLabel }}</span></h4>
                            <div class="text-muted font-size-13">
                                <i class="uil-user me-1"></i>{{ $transaksi->customer_name }} · {{ $transaksi->phone_number }}<br>
                                <i class="uil-store me-1"></i>{{ $transaksi->packet?->product?->name ?? '-' }} — {{ $transaksi->packet?->name ?? '-' }}
                                @if(($transaksi->extra_edit_photos_count ?? 0) > 0)
                                    <span class="badge bg-soft-info text-info ms-1">+{{ $transaksi->extra_edit_photos_count }} Tambahan Edit</span>
                                @endif
                                <br>
                                <i class="uil-processing me-1"></i>Status proses: <span class="badge bg-secondary">{{ $transaksi->process_status }}</span>
                                @if($transaksi->edit_selection_submitted_at)
                                    <br><i class="uil-clock me-1"></i>Diajukan: {{ $transaksi->edit_selection_submitted_at->format('d M Y H:i') }}
                                @endif
                                @if($transaksi->edit_selection_reviewed_at)
                                    <br><i class="uil-check me-1"></i>Direview: {{ $transaksi->edit_selection_reviewed_at->format('d M Y H:i') }}
                                @endif
                            </div>
                            @if($transaksi->edit_selection_status === 'rejected' && $transaksi->edit_selection_note)
                                <div class="alert alert-danger py-2 mt-2 mb-0 font-size-13">
                                    <strong>Alasan penolakan:</strong> {{ $transaksi->edit_selection_note }}
                                </div>
                            @endif
                        </div>

                        <div class="text-lg-end">
                            <div class="mb-2">
                                <span class="badge {{ $overLimit ? 'bg-danger' : 'bg-soft-primary text-primary' }} font-size-14 px-3 py-2">
                                    {{ $count }}{{ $retouchLimit > 0 ? ' / ' . $retouchLimit : '' }} foto dipilih
                                </span>
                                @if($overLimit)
                                    <div class="text-danger font-size-12 mt-1">Melebihi batas retouch paket</div>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                @if($transaksi->edit_selection_status === 'submitted')
                                    <form method="POST" action="{{ route('photo-selections.approve', $transaksi->transaction_id) }}"
                                          onsubmit="return confirm('Setujui {{ $count }} foto ini? Pelanggan tidak dapat mengubah pilihan setelah disetujui.');">
                                        @csrf
                                        <button class="btn btn-success" {{ $count === 0 ? 'disabled' : '' }}>
                                            <i class="uil-check me-1"></i>Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="uil-times me-1"></i>Reject
                                    </button>
                                @elseif($transaksi->edit_selection_status === 'approved')
                                    <form method="POST" action="{{ route('photo-selections.reopen', $transaksi->transaction_id) }}"
                                          onsubmit="return confirm('Buka kembali pemilihan foto? Pelanggan dapat mengubah pilihannya lalu mengajukan ulang.');">
                                        @csrf
                                        <button class="btn btn-warning">
                                            <i class="uil-unlock me-1"></i>Buka Kembali Pilihan
                                        </button>
                                    </form>
                                @else
                                    <div class="text-muted font-size-13">
                                        {{ $transaksi->edit_selection_status === 'rejected'
                                            ? 'Menunggu pelanggan memilih ulang foto.'
                                            : 'Pelanggan belum mengajukan pilihan foto.' }}
                                    </div>
                                @endif
                            </div>

                            @if($transaksi->url_images)
                                <a href="{{ $transaksi->url_images }}" target="_blank" class="d-inline-block mt-2 font-size-13">
                                    <i class="mdi mdi-google-drive me-1"></i>Buka folder foto mentah
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($driveError)
                <div class="alert alert-warning font-size-13">
                    <i class="uil-exclamation-triangle me-1"></i>{{ $driveError }} Thumbnail mungkin tidak tampil.
                    <a href="{{ route('photo-selections.show', [$transaksi->transaction_id, 'refresh' => 1]) }}">Coba lagi</a>
                </div>
            @endif

            {{-- DAFTAR NAMA FILE (MUDAH DISALIN UNTUK EDITOR) --}}
            @if($count > 0)
                <div class="card">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold font-size-13"><i class="uil-list-ul me-1"></i>Daftar nama file</span>
                            <button type="button" class="btn btn-sm btn-light" onclick="copyNames(this)"><i class="uil-copy me-1"></i>Salin</button>
                        </div>
                        <div id="fileNames" class="text-muted font-size-13 mt-1" style="word-break: break-word;">{{ $photos->pluck('name')->implode(', ') }}</div>
                    </div>
                </div>
            @endif

            {{-- GRID FOTO TERPILIH --}}
            @if($count === 0)
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <i class="uil-images font-size-24 d-block mb-2"></i>
                        Belum ada foto yang dipilih pelanggan.
                    </div>
                </div>
            @else
                <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-2 g-md-3 mb-4">
                    @foreach($photos as $i => $photo)
                        <div class="col">
                            <div class="card sel-card h-100 mb-0 {{ $photo['missing'] ? 'is-missing' : '' }}">
                                <div class="sel-img" onclick="openPreview('{{ $photo['preview_url'] }}', '{{ addslashes($photo['name']) }}')">
                                    <span class="badge bg-primary sel-no">#{{ $i + 1 }}</span>
                                    @if($photo['thumbnail_url'])
                                        <img src="{{ $photo['thumbnail_url'] }}" alt="{{ $photo['name'] }}" loading="lazy" referrerpolicy="no-referrer"
                                             onerror="if(this.dataset.f!=='1'&&'{{ $photo['fallback_thumbnail'] }}'){this.dataset.f='1';this.src='{{ $photo['fallback_thumbnail'] }}';}">
                                    @endif
                                </div>
                                <div class="card-body p-2">
                                    <div class="font-size-13 fw-semibold text-truncate" title="{{ $photo['name'] }}">{{ $photo['name'] }}</div>
                                    @if($photo['missing'])
                                        <div class="text-danger font-size-11">File tidak ditemukan lagi di folder</div>
                                    @endif
                                    <div class="d-flex gap-1 mt-2">
                                        <a href="{{ $photo['web_view_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary w-100 py-1 font-size-12" title="Buka di Drive">
                                            <i class="mdi mdi-google-drive"></i>
                                        </a>
                                        <a href="{{ $photo['download_url'] }}" class="btn btn-sm btn-outline-success w-100 py-1 font-size-12" title="Unduh">
                                            <i class="uil-download-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL REJECT --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('photo-selections.reject', $transaksi->transaction_id) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pilihan Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning font-size-13 py-2">
                        Semua foto yang dipilih pelanggan akan <strong>direset</strong> dan pelanggan harus memilih foto kembali.
                    </div>
                    <label class="form-label">Alasan penolakan (akan ditampilkan ke pelanggan)</label>
                    <textarea name="note" class="form-control" rows="3" maxlength="1000" placeholder="Contoh: Ada foto yang mata tertutup, mohon pilih foto lain."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="uil-times me-1"></i>Reject & Reset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL PREVIEW --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title text-truncate" id="previewTitle"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <img id="previewImg" src="" alt="" referrerpolicy="no-referrer" class="modal-preview-img">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    function openPreview(url, name) {
        if (!url) return;
        document.getElementById('previewTitle').innerText = name;
        document.getElementById('previewImg').src = url;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    function copyNames(btn) {
        const text = document.getElementById('fileNames').innerText;
        navigator.clipboard.writeText(text).then(() => {
            const old = btn.innerHTML;
            btn.innerHTML = '<i class="uil-check me-1"></i>Tersalin';
            setTimeout(() => btn.innerHTML = old, 1500);
        });
    }
</script>
@endsection
