@extends('userPage.layouts.app')
@include('userPage.styles.mobile-style')

@section('content')

    {{-- Pindahkan include style ke DALAM section --}}
    @include('userPage.styles.mobile-style')

    @include('userPage.components.modal.booking-modal')

    {{-- MOBILE VIEW --}}
    <div class="mobile-only bg-light min-vh-100">

        @include('userPage.components.mobile.header')
        @include('userPage.components.mobile.galeri-mobile')
        @include('userPage.layouts.bottom-navbar')

    </div>


@endsection