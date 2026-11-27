@extends('layouts.master')

@section('title')
    Kapasitas Editing Per Hari
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Management Data @endslot
        @slot('title') Kapasitas Editing Per Hari @endslot
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

    {{-- RINGKASAN KAPASITAS HARI INI --}}
    @php
        $todayCap = $schedule['today_capacity'];
        $todayUsed = $schedule['today_used'];
        $todayPct = $todayCap > 0 ? min(100, (int) round(($todayUsed / $todayCap) * 100)) : 100;
        $todayBarClass = $todayCap === 0 ? 'bg-secondary' : ($todayPct >= 100 ? 'bg-danger' : ($todayPct >= 75 ? 'bg-warning' : 'bg-success'));
    @endphp
    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-20">
                                <i class="uil-sliders-v-alt"></i>
                            </span>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">{{ $todayCap }} <span class="font-size-14 fw-normal text-muted">antrian</span></h4>
                        <p class="text-muted mb-0">
                            Kapasitas Hari Ini
                            @if($schedule['today_is_override'])
                                <span class="badge bg-soft-warning text-warning ms-1">Khusus</span>
                            @else
                                <span class="badge bg-soft-secondary text-secondary ms-1">Default</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-soft-success text-success font-size-20">
                                <i class="uil-check-circle"></i>
                            </span>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">{{ $schedule['today_completed'] }}</h4>
                        <p class="text-muted mb-0">Selesai Diedit Hari Ini</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-20">
                                <i class="uil-clock"></i>
                            </span>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">{{ $schedule['today_queued'] }} <span class="font-size-14 fw-normal text-muted">/ sisa {{ $schedule['today_remaining'] }}</span></h4>
                        <p class="text-muted mb-1">Antrian Dijadwalkan Hari Ini</p>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar {{ $todayBarClass }}" role="progressbar" style="width: {{ $todayPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="float-end mt-2">
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-soft-info text-info font-size-20">
                                <i class="uil-layers-alt"></i>
                            </span>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-1 mt-1">{{ $schedule['total_queued'] }}</h4>
                        <p class="text-muted mb-0">
                            Total Antrian Aktif
                            <span class="d-block font-size-12 text-primary">
                                Berikutnya: #{{ $schedule['next_queue_number'] }} ({{ $schedule['next_estimated_date_label'] }})
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PENGATURAN KAPASITAS DEFAULT & TANGGAL KHUSUS --}}
    <div class="row">
        {{-- CARD 1: DEFAULT KAPASITAS HARIAN --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-1">
                        <i class="uil-cog text-primary me-1"></i>Kapasitas Default Per Hari
                    </h4>
                    <p class="card-title-desc mb-3">
                        Jumlah maksimal transaksi yang dapat diedit per hari secara otomatis. Perubahan akan langsung memperbarui jadwal antrian pelanggan.
                    </p>

                    <form method="POST" action="{{ route('editing-capacity.update-default') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="defaultCapacityInput" class="form-label fw-semibold">Kapasitas Editing / Hari <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number"
                                       id="defaultCapacityInput"
                                       name="capacity"
                                       class="form-control"
                                       min="1"
                                       max="500"
                                       required
                                       value="{{ old('capacity', $schedule['default_capacity']) }}">
                                <span class="input-group-text">antrian / hari</span>
                            </div>
                            <div class="form-text">Contoh: isi 20 jika tim editor sanggup mengerjakan 20 antrian per hari.</div>
                        </div>

                        <div class="mb-3">
                            <label for="defaultNoteInput" class="form-label">Catatan (Opsional)</label>
                            <input type="text"
                                   id="defaultNoteInput"
                                   name="note"
                                   class="form-control"
                                   maxlength="255"
                                   placeholder="Misal: Kapasitas standar 2 editor"
                                   value="{{ old('note', $defaultRecord?->note) }}">
                        </div>

                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-muted font-size-12">
                                @if($defaultRecord && $defaultRecord->updated_at)
                                    Terakhir diubah: {{ $defaultRecord->updated_at->format('d M Y H:i') }}
                                    @if($defaultRecord->updater)
                                        oleh {{ $defaultRecord->updater->name }}
                                    @endif
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i class="uil-save me-1"></i>Simpan Default
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CARD 2: OVERRIDE PER TANGGAL --}}
        <div class="col-lg-7 mt-3 mt-lg-0">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="card-title mb-1">
                        <i class="uil-calendar-alt text-warning me-1"></i>Atur Kapasitas Tanggal Tertentu
                    </h4>
                    <p class="card-title-desc mb-3">
                        Ubah kapasitas khusus untuk hari ini atau tanggal tertentu (misal saat ramai, editor cuti, atau isi <strong>0</strong> untuk hari libur).
                    </p>

                    <form method="POST" action="{{ route('editing-capacity.store-date') }}" id="dateOverrideForm">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-4">
                                <label for="overrideDateInput" class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                                <input type="date"
                                       id="overrideDateInput"
                                       name="date"
                                       class="form-control"
                                       min="{{ \Carbon\Carbon::today()->toDateString() }}"
                                       value="{{ old('date', \Carbon\Carbon::today()->toDateString()) }}"
                                       required>
                            </div>
                            <div class="col-sm-3">
                                <label for="overrideCapacityInput" class="form-label fw-semibold">Kapasitas <span class="text-danger">*</span></label>
                                <input type="number"
                                       id="overrideCapacityInput"
                                       name="capacity"
                                       class="form-control"
                                       min="0"
                                       max="500"
                                       value="{{ old('capacity', $schedule['today_capacity']) }}"
                                       required>
                            </div>
                            <div class="col-sm-5">
                                <label for="overrideNoteInput" class="form-label">Catatan (Opsional)</label>
                                <div class="d-flex gap-2">
                                    <input type="text"
                                           id="overrideNoteInput"
                                           name="note"
                                           class="form-control"
                                           maxlength="255"
                                           placeholder="Misal: Libur / Lembur"
                                           value="{{ old('note') }}">
                                    <button type="submit" class="btn btn-warning text-nowrap">
                                        <i class="uil-check me-1"></i>Simpan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if($dateOverrides->isNotEmpty())
                        <div class="table-responsive mt-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th class="text-center">Kapasitas</th>
                                        <th>Catatan</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dateOverrides as $ov)
                                        <tr>
                                            <td class="fw-semibold">{{ $ov->date ? $ov->date->format('d M Y') : $ov->date_key }}</td>
                                            <td class="text-center">
                                                @if($ov->capacity === 0)
                                                    <span class="badge bg-danger">0 (Libur)</span>
                                                @else
                                                    <span class="badge bg-soft-warning text-warning">{{ $ov->capacity }} antrian</span>
                                                @endif
                                            </td>
                                            <td class="text-muted font-size-13">{{ $ov->note ?: '-' }}</td>
                                            <td class="text-end text-nowrap">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary py-0 px-2"
                                                        onclick="fillOverrideForm('{{ $ov->date_key }}', '{{ $ov->capacity }}', @json($ov->note ?? ''))"
                                                        title="Ubah">
                                                    <i class="uil-edit"></i>
                                                </button>
                                                <form method="POST"
                                                      action="{{ route('editing-capacity.destroy-date', $ov->id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Reset tanggal ini ke kapasitas default ({{ $schedule['default_capacity'] }}/hari)?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Reset ke Default">
                                                        <i class="uil-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted font-size-13 mt-3 mb-0">
                            <i class="uil-info-circle me-1"></i>Belum ada pengaturan khusus per tanggal. Semua hari menggunakan kapasitas default (<strong>{{ $schedule['default_capacity'] }} antrian/hari</strong>).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- RINGKASAN JADWAL 7 HARI KE DEPAN --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">Ringkasan Slot 7 Hari ke Depan</h4>
                    <p class="card-title-desc mb-3">Distribusi otomatis antrian editing berdasarkan kapasitas harian yang aktif.</p>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-center">Kapasitas</th>
                                    <th class="text-center">Selesai</th>
                                    <th class="text-center">Antrian</th>
                                    <th class="text-center">Sisa Slot</th>
                                    <th style="min-width: 160px;">Terpakai</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedule['daily_breakdown'] as $day)
                                    @php
                                        $pct = $day['capacity'] > 0 ? min(100, (int) round(($day['used'] / $day['capacity']) * 100)) : 100;
                                        $barColor = $day['capacity'] === 0 ? 'bg-secondary' : ($pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success'));
                                    @endphp
                                    <tr class="{{ $day['is_today'] ? 'table-primary bg-opacity-10' : '' }}">
                                        <td class="fw-semibold">
                                            {{ $day['label'] }}
                                            @if($day['is_override'])
                                                <span class="badge bg-soft-warning text-warning ms-1 font-size-11">Khusus</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">{{ $day['capacity'] }}</td>
                                        <td class="text-center">{{ $day['completed'] }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-soft-primary text-primary font-size-12">{{ $day['queued'] }}</span>
                                        </td>
                                        <td class="text-center">{{ $day['remaining'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span class="font-size-12 text-muted text-nowrap">{{ $day['used'] }}/{{ $day['capacity'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($day['capacity'] === 0)
                                                <span class="badge bg-secondary">Libur</span>
                                            @elseif($day['remaining'] === 0)
                                                <span class="badge bg-danger">Penuh</span>
                                            @else
                                                <span class="badge bg-success">Tersedia</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning py-0 px-2"
                                                    onclick="fillOverrideForm('{{ $day['date_string'] }}', '{{ $day['capacity'] }}', '')"
                                                    title="Ubah kapasitas tanggal ini">
                                                <i class="uil-edit"></i> Atur
                                            </button>
                                            @if($day['queued'] > 0)
                                                <a href="{{ route('editing-capacity.index', ['date' => $day['date_string']]) }}#queueListCard"
                                                   class="btn btn-sm btn-outline-primary py-0 px-2"
                                                   title="Lihat antrian tanggal ini">
                                                    <i class="uil-list-ul"></i>
                                                </a>
                                            @endif
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

    {{-- DAFTAR ANTRIAN EDITING AKTIF --}}
    <div class="row mt-1">
        <div class="col-12">
            <div class="card" id="queueListCard">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Daftar Antrian Editing Aktif ({{ $queueItems->count() }})</h4>
                            <p class="card-title-desc mb-0">Urutan otomatis (FIFO) berdasarkan waktu pengajuan/masuk proses edit. Nomor antrian ini tampil langsung di halaman pelanggan.</p>
                        </div>
                        <form method="GET" action="{{ route('editing-capacity.index') }}#queueListCard" class="d-flex flex-wrap gap-2">
                            <input type="date" name="date" value="{{ $filterDate }}" class="form-control form-control-sm" style="width: auto;" title="Filter estimasi tanggal">
                            <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" style="width: 200px;" placeholder="Cari nota / nama / HP">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="uil-search"></i></button>
                            @if($filterDate !== '' || $search !== '')
                                <a href="{{ route('editing-capacity.index') }}#queueListCard" class="btn btn-sm btn-light">Reset</a>
                            @endif
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 90px;">Antrian</th>
                                    <th>Estimasi Tanggal Edit</th>
                                    <th>No. Nota</th>
                                    <th>Pelanggan</th>
                                    <th>Paket</th>
                                    <th>Status</th>
                                    <th>Masuk Antrian</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($queueItems as $item)
                                    @php $trx = $item['transaksi']; @endphp
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge bg-primary font-size-13 px-2 py-1">#{{ $item['queue_number'] }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold {{ $item['is_today'] ? 'text-success' : 'text-dark' }}">
                                                {{ $item['estimated_date_label'] }}
                                            </div>
                                            <div class="text-muted font-size-12">Slot {{ $item['day_slot'] }} / {{ $item['day_capacity'] }}</div>
                                        </td>
                                        <td class="fw-semibold">#{{ $trx->receipt_code }}</td>
                                        <td>
                                            {{ $trx->customer_name }}
                                            <div class="text-muted font-size-12">{{ $trx->phone_number }}</div>
                                        </td>
                                        <td>
                                            {{ $trx->packet?->product?->name ?? '-' }}
                                            <div class="text-muted font-size-12">{{ $trx->packet?->name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            @if($trx->edit_selection_status === 'submitted')
                                                <span class="badge bg-warning">Menunggu Review</span>
                                            @elseif($trx->edit_selection_status === 'approved')
                                                <span class="badge bg-success">Disetujui</span>
                                            @endif
                                            <span class="badge bg-secondary">{{ $trx->process_status }}</span>
                                        </td>
                                        <td class="font-size-13">
                                            {{ $item['queued_at'] ? $item['queued_at']->format('d M Y H:i') : '-' }}
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('photo-selections.show', $trx->transaction_id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="uil-eye me-1"></i>Review
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="uil-check-circle font-size-24 d-block mb-2 text-success"></i>
                                            @if($filterDate !== '' || $search !== '')
                                                Tidak ada antrian editing yang cocok dengan filter.
                                            @else
                                                Belum ada transaksi dalam antrian editing saat ini.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    function fillOverrideForm(dateStr, capacity, note) {
        const dateEl = document.getElementById('overrideDateInput');
        const capEl = document.getElementById('overrideCapacityInput');
        const noteEl = document.getElementById('overrideNoteInput');

        if (dateEl) dateEl.value = dateStr;
        if (capEl) capEl.value = capacity;
        if (noteEl) noteEl.value = note || '';

        const form = document.getElementById('dateOverrideForm');
        if (form) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (capEl) capEl.focus();
        }
    }
</script>
@endsection
