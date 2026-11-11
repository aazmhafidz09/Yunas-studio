@extends('layouts.master')

@section('title')
    Pilihan Foto Edit
@endsection

@php
    $tabs = [
        'submitted' => ['label' => 'Menunggu Review', 'icon' => 'uil-clock', 'badge' => 'bg-warning'],
        'approved'  => ['label' => 'Disetujui', 'icon' => 'uil-check-circle', 'badge' => 'bg-success'],
        'rejected'  => ['label' => 'Ditolak', 'icon' => 'uil-times-circle', 'badge' => 'bg-danger'],
        'draft'     => ['label' => 'Belum Diajukan', 'icon' => 'uil-edit', 'badge' => 'bg-secondary'],
    ];
@endphp

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Transaksi @endslot
        @slot('title') Pilihan Foto Edit @endslot
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

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Pengajuan Foto untuk Diedit</h4>
                            <p class="card-title-desc mb-0">Review foto yang dipilih pelanggan untuk diedit, lalu setujui atau tolak.</p>
                        </div>
                        <form method="GET" action="{{ route('photo-selections.index') }}" class="d-flex gap-2">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nota / nama / no. HP">
                            <button class="btn btn-sm btn-primary"><i class="uil-search"></i></button>
                        </form>
                    </div>

                    {{-- TAB STATUS --}}
                    <ul class="nav nav-tabs nav-tabs-custom mb-3 flex-nowrap overflow-auto">
                        @foreach($tabs as $key => $tab)
                            <li class="nav-item">
                                <a class="nav-link text-nowrap {{ $status === $key ? 'active' : '' }}"
                                   href="{{ route('photo-selections.index', ['status' => $key, 'q' => request('q')]) }}">
                                    <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                                    <span class="badge rounded-pill {{ $tab['badge'] }} ms-1">{{ $counts[$key] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Nota</th>
                                    <th>Pelanggan</th>
                                    <th>Paket</th>
                                    <th class="text-center">Foto Dipilih</th>
                                    <th>{{ $status === 'submitted' ? 'Diajukan' : ($status === 'draft' ? 'Terakhir Diubah' : 'Direview') }}</th>
                                    <th>Status Proses</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    @php
                                        $limit = $trx->retouch_limit;
                                        $time = match ($status) {
                                            'submitted' => $trx->edit_selection_submitted_at,
                                            'draft' => $trx->updated_at,
                                            default => $trx->edit_selection_reviewed_at,
                                        };
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">#{{ $trx->receipt_code }}</td>
                                        <td>
                                            {{ $trx->customer_name }}
                                            <div class="text-muted font-size-12">{{ $trx->phone_number }}</div>
                                        </td>
                                        <td>
                                            {{ $trx->packet?->product?->name ?? '-' }}
                                            <div class="text-muted font-size-12">{{ $trx->packet?->name ?? '-' }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $limit > 0 && $trx->edit_photos_count > $limit ? 'bg-danger' : 'bg-soft-primary text-primary' }} font-size-12">
                                                {{ $trx->edit_photos_count }}{{ $limit > 0 ? ' / ' . $limit : '' }}
                                            </span>
                                        </td>
                                        <td class="font-size-13">{{ $time ? $time->format('d M Y H:i') : '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $trx->process_status }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('photo-selections.show', $trx->transaction_id) }}" class="btn btn-sm btn-primary">
                                                <i class="uil-eye me-1"></i>{{ $status === 'submitted' ? 'Review' : 'Lihat' }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="uil-images font-size-24 d-block mb-2"></i>
                                            Tidak ada data untuk kategori <strong>{{ $tabs[$status]['label'] }}</strong>.
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
