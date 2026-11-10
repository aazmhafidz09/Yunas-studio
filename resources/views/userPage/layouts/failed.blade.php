{{-- resources/views/booking/failed.blade.php --}}
@extends('userPage.layouts.app')

@section('title', 'Pembayaran Gagal')

@section('content')
    <div class="min-vh-100 d-flex align-items-center py-5 bg-light">

        <div class="container">
            <div class="row justify-content-center">

                <div class="col-lg-6 col-md-8">

                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">

                        {{-- Header --}}
                        <div class="p-5 text-center bg-danger-subtle">

                            <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm"
                                style="width: 90px; height: 90px; font-size: 42px;">
                                ✕
                            </div>

                            <h2 class="fw-bold mt-4 mb-2 text-danger">
                                Pembayaran Gagal
                            </h2>

                            <p class="text-muted mb-0 px-md-5">
                                Maaf, transaksi pembayaran Anda tidak berhasil diproses.
                                Silakan coba kembali atau hubungi dukungan pelanggan kami.
                            </p>
                        </div>

                        {{-- Detail --}}
                        <div class="p-4 p-md-5">

                            @if(isset($error_message))
                                <div class="alert alert-danger rounded-3 mb-4" role="alert">
                                    <strong>Kesalahan:</strong> {{ $error_message }}
                                </div>
                            @endif

                            @if(isset($booking))
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <small class="text-uppercase text-muted fw-semibold" style="letter-spacing: .12em;">
                                        Detail Booking
                                    </small>

                                    <span class="badge bg-dark rounded-pill px-3 py-2">
                                        {{ $booking->order_id }}
                                    </span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">

                                        <tr>
                                            <td class="text-muted border-0 ps-0">Nama</td>
                                            <td class="fw-semibold border-0 text-end pe-0">
                                                {{ $booking->customer_name }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">WhatsApp</td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->phone_number }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">Produk</td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->packet->product->name ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">Paket</td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->packet->name ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">Tanggal</td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ \Carbon\Carbon::parse($booking->created_at)->translatedFormat('d F Y H:i') }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold ps-0 pt-4">Total Tagihan</td>
                                            <td class="fw-bold text-danger fs-5 text-end pe-0 pt-4">
                                                Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                                            </td>
                                        </tr>

                                    </table>
                                </div>
                            @endif

                            {{-- Buttons --}}
                            <div class="d-grid gap-2 mt-5">

                                @if(isset($booking))
                                    <a href="{{ route('booking.retry', $booking->order_id) }}"
                                        class="btn btn-danger btn-lg rounded-pill py-3 fw-semibold">
                                        Coba Bayar Lagi
                                    </a>
                                @endif

                                <a href="{{ route('userPage.home') }}"
                                    class="btn btn-outline-dark btn-lg rounded-pill py-3 fw-semibold">
                                    Kembali ke Beranda
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>

    </div>
@endsection