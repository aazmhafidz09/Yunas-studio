@extends('layouts.master')

@section('title')
    Approval Additional Booking
@endsection

@php
    $tabs = [
        'pending'  => ['label' => 'Menunggu Approval', 'icon' => 'uil-clock', 'badge' => 'bg-warning'],
        'approved' => ['label' => 'Disetujui', 'icon' => 'uil-check-circle', 'badge' => 'bg-success'],
        'rejected' => ['label' => 'Ditolak', 'icon' => 'uil-times-circle', 'badge' => 'bg-danger'],
    ];
@endphp

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Management Data @endslot
        @slot('title') Approval Additional Booking @endslot
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
                            <h4 class="card-title mb-1">Persetujuan Tambahan Ekstra (Additional) Booking</h4>
                            <p class="card-title-desc mb-0">
                                Review tambahan ekstra yang diajukan pelanggan saat booking online. Biaya tambahan baru masuk ke total tagihan setelah di-<strong>Approve</strong>.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('booking-additionals.index') }}" class="d-flex gap-2">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari invoice / nama / item">
                            <button class="btn btn-sm btn-primary"><i class="uil-search"></i></button>
                        </form>
                    </div>

                    <ul class="nav nav-tabs nav-tabs-custom mb-3 flex-nowrap overflow-auto">
                        @foreach($tabs as $key => $tab)
                            <li class="nav-item">
                                <a class="nav-link text-nowrap {{ $status === $key ? 'active' : '' }}"
                                   href="{{ route('booking-additionals.index', ['status' => $key, 'q' => request('q')]) }}">
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
                                    <th>No. Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Jadwal Sesi</th>
                                    <th>Tambahan Diajukan</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    @php
                                        $gw = $req->gateway;
                                        $itemName = $req->additional?->name ?? 'Item Dihapus';
                                        $subtotal = $req->subtotal;
                                        $phone = \App\Models\Promo::normalizePhone($gw?->phone_number);
                                        $waUrl = null;
                                        if ($phone && $req->status !== 'pending') {
                                            $invCode = $gw?->receipt_code ?? $gw?->order_id ?? '-';
                                            if ($req->status === 'approved') {
                                                $waText = "Halo Kak *{$gw->customer_name}*, pengajuan tambahan ekstra *{$req->quantity}x {$itemName}* (Rp " . number_format($subtotal, 0, ',', '.') . ") untuk booking *{$invCode}* telah *DISETUJUI* oleh admin Yunas Studio.\n\nTotal tagihan booking terbaru Anda: *Rp " . number_format($gw->total_price, 0, ',', '.') . "*.\n\nTerima kasih!";
                                            } else {
                                                $alasan = $req->admin_note ? "\nAlasan: {$req->admin_note}" : '';
                                                $waText = "Halo Kak *{$gw->customer_name}*, mohon maaf pengajuan tambahan ekstra *{$req->quantity}x {$itemName}* untuk booking *{$invCode}* *belum dapat disetujui*.{$alasan}\n\nTotal tagihan booking Anda tetap sesuai harga paket. Terima kasih!";
                                            }
                                            $waUrl = "https://api.whatsapp.com/send?phone={$phone}&text=" . rawurlencode($waText);
                                        }
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $gw?->receipt_code ?? $gw?->order_id ?? ('#' . $req->transaction_id) }}
                                            <div class="text-muted font-size-11">{{ $req->created_at->format('d M Y H:i') }}</div>
                                        </td>
                                        <td>
                                            {{ $gw?->customer_name ?? '-' }}
                                            <div class="text-muted font-size-12">{{ $gw?->phone_number ?? '-' }}</div>
                                        </td>
                                        <td class="font-size-13">
                                            {{ $gw?->session_date ? \Carbon\Carbon::parse($gw->session_date)->format('d M Y') : '-' }}
                                            <div class="text-muted font-size-12">{{ $gw?->session_time ? substr($gw->session_time, 0, 5) . ' WIB' : '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $itemName }}</div>
                                            <div class="text-muted font-size-12">Rp {{ number_format($req->price, 0, ',', '.') }} / item</div>
                                            @if($req->admin_note)
                                                <div class="text-danger font-size-12 mt-1"><strong>Catatan:</strong> {{ $req->admin_note }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center fw-semibold">{{ $req->quantity }}</td>
                                        <td class="text-end fw-semibold text-nowrap">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                        <td>
                                            @if($req->status === 'pending')
                                                <span class="badge bg-warning">Menunggu Approval</span>
                                            @elseif($req->status === 'approved')
                                                <span class="badge bg-success">Disetujui</span>
                                                <div class="text-muted font-size-11">{{ $req->reviewed_at?->format('d M H:i') }}</div>
                                            @else
                                                <span class="badge bg-danger">Ditolak</span>
                                                <div class="text-muted font-size-11">{{ $req->reviewed_at?->format('d M H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            @if($req->status === 'pending')
                                                <form method="POST"
                                                      action="{{ route('booking-additionals.approve', $req->id) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Setujui tambahan {{ addslashes($itemName) }} ({{ $req->quantity }}x)? Total tagihan booking akan bertambah Rp {{ number_format($subtotal, 0, ',', '.') }}.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="uil-check me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <button type="button"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="openRejectAdditionalModal({{ $req->id }}, @json($itemName))">
                                                    <i class="uil-times me-1"></i>Reject
                                                </button>
                                            @elseif($waUrl)
                                                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                                                    <i class="uil uil-whatsapp me-1"></i>Kabari WA
                                                </a>
                                            @else
                                                <span class="text-muted font-size-12">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="uil-clipboard-notes font-size-24 d-block mb-2"></i>
                                            Tidak ada pengajuan tambahan untuk kategori <strong>{{ $tabs[$status]['label'] }}</strong>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $requests->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL REJECT ADDITIONAL --}}
    <div class="modal fade" id="rejectAdditionalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="rejectAdditionalForm" action="" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan Tambahan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Item: <strong id="rejectAdditionalItemName">-</strong></p>
                    <label class="form-label">Alasan penolakan (opsional)</label>
                    <textarea name="admin_note" class="form-control" rows="3" maxlength="500" placeholder="Contoh: Stok/slot tambahan sedang tidak tersedia pada jam tersebut."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="uil-times me-1"></i>Tolak Tambahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    function openRejectAdditionalModal(id, itemName) {
        const form = document.getElementById('rejectAdditionalForm');
        form.action = @json(route('booking-additionals.reject', ['bookingAdditionalRequest' => '__ID__'])).replace('__ID__', id);
        document.getElementById('rejectAdditionalItemName').textContent = itemName;
        new bootstrap.Modal(document.getElementById('rejectAdditionalModal')).show();
    }
</script>
@endsection
