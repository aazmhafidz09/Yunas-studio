@extends('layouts.master')

@section('title')
    Reschedule Pelanggan
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Reschedule Pelanggan @endslot
        @slot('title') Reschedule Pelanggan @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Daftar Reschedule Pelanggan</h3>
                    <p class="card-title-desc">Berikut adalah daftar pelanggan yang telah melakukan reschedule.</p>

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Nomor Transaksi</th>
                                    <th>Jadwal Lama</th>
                                    <th>Jadwal Baru</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $request)
                                    <tr>
                                        <td>{{ $request->id }}</td>
                                        <td>{{ $request->transaction->customer_name ?? '-' }}</td>
                                        <td>{{ $request->transaction->order_id ?? '-' }}</td>
                                        <td>
                                            {{ $request->old_schedule_date }}
                                            {{ $request->old_schedule_time }}
                                        </td>
                                        <td>
                                            {{ $request->new_schedule_date }}
                                            {{ $request->new_schedule_time }}
                                        </td>
                                        <td>{{ ucfirst($request->status) }}</td>
                                        <td>
                                            @if ($request->status === 'pending')
                                                <form action="{{ route('reschedule.approve', $request->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Setujui reschedule ini? Jadwal booking akan otomatis diperbarui.')">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form action="{{ route('reschedule.reject', $request->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Tolak reschedule ini?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">No actions available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada request reschedule pending.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $requests->links() }}

                </div> <!-- end card body-->
            </div> <!-- end card -->
        </div><!-- end col-->
    </div>
@endsection