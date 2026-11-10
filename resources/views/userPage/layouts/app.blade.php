<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Yunas Studio') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('images/LogoYunas.png') }}">

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Alpine --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Material Design Icons --}}
    <link rel="stylesheet" 
      href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

    {{-- Midtrans Snap.js --}}
    <script src="{{ config('midtrans.is_production')
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js' }}" data-client-key="{{ config('midtrans.client_key') }}">
        </script>


    <!-- PWA  -->
    @pwaHead

    <style>
        html,
        body {
            max-width: 100%;
            scroll-behavior: smooth;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: #f0f0f0;
        }

        .object-fit-cover {
            object-fit: cover;
        }

        .mobile-card {
            border-radius: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 767.98px) {

            #main-navbar,
            #main-footer {
                display: none !important;
            }

            body {
                padding-top: 0 !important;
                padding-bottom: 70px;
            }
        }
    </style>

    @stack('styles')
</head>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Modal element:', document.getElementById('productDetailModal'));
        console.log('showProductDetail function:', typeof window.showProductDetail);
    });
</script>
<body class="antialiased">

    @include('userPage.layouts.navbar')

    <main>
        @yield('content')
    </main>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

    @laravelPwa
    @pwaUpdateNotifier
    @pwaInstallButton
</body>

</html>