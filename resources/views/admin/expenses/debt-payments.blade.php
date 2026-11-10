@extends('layouts.master')

@section('title')
    Riwayat Pembayaran Hutang
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Riwayat Pembayaran Hutang @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Riwayat Pembayaran - {{ $expense->name }}</h4>
                        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Kembali
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Informasi Hutang</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <tbody>
                                                <tr>
                                                    <th style="width: 40%">Nama</th>
                                                    <td>{{ $expense->name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Kategori</th>
                                                    <td>{{ $expense->category ? $expense->category->name : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Total Hutang</th>
                                                    <td>{{ $expense->formatted_amount }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Jumlah Terbayar</th>
                                                    <td>{{ $expense->formattedPaidAmount }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Sisa Hutang</th>
                                                    <td>{{ $expense->formattedRemainingAmount }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Status</th>
                                                    <td>
                                                        <span class="badge bg-{{ $expense->payment_status_color }}">{{ $expense->payment_status_label }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h6>Progress Pembayaran</h6>
                                        <div class="progress" style="height: 15px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: {{ $expense->paymentProgress }}%" 
                                                aria-valuenow="{{ $expense->paymentProgress }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                                {{ $expense->paymentProgress }}%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($expense->remaining_amount > 0)
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Tambah Pembayaran Baru</h5>
                                    <form action="{{ route('expenses.partial-payment', $expense->id) }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Jumlah Pembayaran <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   min="1" max="{{ $expense->remaining_amount }}" step="0.01" required>
                                            <div class="form-text">Maksimum: Rp {{ number_format($expense->remaining_amount, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="payment_date" class="form-label">Tanggal Pembayaran</label>
                                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="notes" class="form-label">Catatan</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Catatan pembayaran (opsional)"></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-money me-1"></i> Proses Pembayaran
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal Pembayaran</th>
                                    <th>Jumlah</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $key => $payment)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                        <td>{{ $payment->formatted_amount }}</td>
                                        <td>{{ $payment->notes ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            Belum ada riwayat pembayaran untuk hutang ini.
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