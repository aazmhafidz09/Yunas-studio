{{-- ===== MOBILE BOTTOM NAVIGATION BAR ===== --}}
<nav class="mobile-bottom-nav">

    {{-- Beranda --}}
    <a href="{{route('userPage.home')  }}" class="{{ request()->is('/') && request()->query('page') !== 'galeri' ? 'active' : '' }}">
        <i class="bi bi-house-fill"></i>
        <span>Beranda</span>
    </a>

    {{-- Galeri --}}
    <a href="{{route('galeri') }}" class="{{ request()->query('page') === 'galeri' ? 'active' : '' }}">
        <i class="bi bi-images"></i>
        <span>Galeri</span>
    </a>

    {{-- Booking --}}
    <a href="#" data-bs-toggle="modal" data-bs-target="#bookingModal">
        <i class="bi bi-calendar-plus-fill"></i>
        <span>Booking</span>
    </a>
    {{-- Jadwal --}}
    <a href="{{ route('schedule') }}">
        <i class="bi bi-calendar"></i>
        <span>Jadwal</span>
    </a>
    {{-- Kontak --}}
    <a href="{{ route('kontak') }}">
        <i class="bi bi-person-fill"></i>
        <span>Kontak</span>
    </a>

</nav>