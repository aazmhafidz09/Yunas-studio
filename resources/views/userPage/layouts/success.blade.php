{{-- resources/views/booking/success.blade.php --}}
@extends('userPage.layouts.app')

@section('title', 'Pembayaran Berhasil')

@section('content')
    <div class="min-vh-100 d-flex align-items-center py-5 bg-light">

        <div class="container">
            <div class="row justify-content-center">

                <div class="col-lg-6 col-md-8">

                    @php
                        $isPending = request('status') === 'pending';
                    @endphp

                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">

                        {{-- Header --}}
                        <div class="p-5 text-center
                                {{ $isPending ? 'bg-warning-subtle' : 'bg-success-subtle' }}">

                            @if($isPending)

                                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 90px; height: 90px; font-size: 42px;">
                                    ⏳
                                </div>

                                <h2 class="fw-bold mt-4 mb-2">
                                    Menunggu Pembayaran
                                </h2>

                                <p class="text-muted mb-0 px-md-5">
                                    Segera selesaikan pembayaran Anda.
                                    Booking akan otomatis dikonfirmasi setelah pembayaran diterima.
                                </p>

                            @else

                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 90px; height: 90px; font-size: 42px;">
                                    ✓
                                </div>

                                <h2 class="fw-bold mt-4 mb-2 text-success">
                                    Pembayaran Berhasil!
                                </h2>

                                <p class="text-muted mb-0 px-md-5">
                                    Booking sesi foto Anda telah berhasil dikonfirmasi.
                                    Sampai jumpa di studio ✨
                                </p>

                            @endif
                        </div>

                        {{-- Detail Booking --}}
                        @if($booking)
                            <div class="p-4 p-md-5">

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <small class="text-uppercase text-muted fw-semibold" style="letter-spacing: .12em;">
                                            Detail Booking
                                        </small>
                                    </div>

                                    <span class="badge bg-dark rounded-pill px-3 py-2">
                                        {{ $booking->order_id }}
                                    </span>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">

                                        <tr>
                                            <td class="text-muted border-0 ps-0">
                                                Nama
                                            </td>
                                            <td class="fw-semibold border-0 text-end pe-0">
                                                {{ $booking->customer_name }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">
                                                WhatsApp
                                            </td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->phone_number }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">
                                                Produk
                                            </td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->packet->product->name ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">
                                                Paket
                                            </td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->packet->name ?? '-' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">
                                                Tanggal
                                            </td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ \Carbon\Carbon::parse($booking->session_date)->translatedFormat('d F Y') }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="text-muted ps-0">
                                                Waktu
                                            </td>
                                            <td class="fw-semibold text-end pe-0">
                                                {{ $booking->session_time }} WIB
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold ps-0 pt-4">
                                                Total Dibayar
                                            </td>

                                            <td class="fw-bold text-success fs-5 text-end pe-0 pt-4">
                                                Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                                            </td>
                                        </tr>

                                    </table>
                                </div><br>
                                <a href="{{ route('booking.download.pdf', $booking->order_id) }}"
                                    class="btn btn-outline-dark btn-lg rounded-pill">

                                    <i class="bi bi-file-earmark-pdf"></i>
                                    Download Invoice PDF

                                </a>
                                {{-- Button --}}
                                <div class="d-grid mt-5">

                                    <a href="{{ route('userPage.home') }}"
                                        class="btn btn-dark btn-lg rounded-pill py-3 fw-semibold">

                                        Kembali ke Beranda

                                    </a>

                                </div>

                            </div>
                        @endif

                    </div>

                </div>

            </div>
        </div>

    </div>
@endsection