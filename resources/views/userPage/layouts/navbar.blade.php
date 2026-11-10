<nav id="main-navbar" class="navbar navbar-expand-md bg-white border-bottom px-4 px-md-5 sticky-top"
    style="height: 56px;">

    <div class="container-fluid px-0">
        {{-- Logo --}}
        <a href="{{ url('/') }}" class="navbar-brand fw-bold text-uppercase text-dark"
            style="font-size: 1.25 rem; letter-spacing: 0.15em;">
            Yunas Studio
        </a>

        {{-- Toggler untuk layar kecil (opsional, bisa dihapus kalau tidak mau hamburger) --}}
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
            data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Menu --}}
        <div class="collapse navbar-collapse" id="navMenu">

            {{-- Menu Tengah --}}
            <ul class="navbar-nav mx-auto gap-md-4">
                <li class="nav-item">
                    <a href="{{ route('userPage.home') }}#paket"
                        class="nav-link text-dark {{ request()->is('paket') ? 'fw-semibold' : 'fw-normal' }}">
                        Paket
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('userPage.home') }}#galeri" class="nav-link text-dark">
                        Galeri
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('userPage.home') }}#cara-booking"
                        class="nav-link text-dark {{ request()->is('cara-booking') ? 'fw-semibold' : 'fw-normal' }}">
                        Cara Booking
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('kontak') }}"
                        class="nav-link text-dark">
                        Kontak
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('schedule') }}"
                        class="nav-link text-dark">
                        Jadwal / Pesanan
                    </a>
                </li>
            </ul>

            {{-- Tombol CTA --}}
            <a class="btn btn-dark rounded-2 px-4 py-2 ms-md-3" href="{{ route('userPage.home') }}#cara-booking"
                style="font-size: 0.875rem;">
                Booking Sekarang
            </a>

        </div>
    </div>
</nav>