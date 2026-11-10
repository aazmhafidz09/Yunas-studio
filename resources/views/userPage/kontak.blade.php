@extends('userPage.layouts.app')
<title>Yunas Studio - Kontak</title>

@section('content')

    @include('userPage.styles.mobile-style')
    @include('userPage.components.modal.booking-modal')

    {{-- MOBILE VIEW --}}
    <div class="mobile-only bg-light min-vh-100">
        @include('userPage.components.mobile.header')
        @include('userPage.components.mobile.kontak-mobile')
        @include('userPage.layouts.bottom-navbar')
    </div>

    {{-- DESKTOP VIEW --}}
    <div class="desktop-only">
        @include('userPage.components.desktop.kontak')
        @include('userPage.components.desktop.footer')
    </div>

@endsection