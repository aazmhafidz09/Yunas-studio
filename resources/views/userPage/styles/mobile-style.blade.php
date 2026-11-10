@push('styles')
<style>
    /* =========================================
       MOBILE APP STYLES
    ========================================= */

    /* Hide bottom nav di desktop */
    .mobile-bottom-nav {
        display: none;
    }

    /* =========================================
       MOBILE VIEW
    ========================================= */
    @media (max-width: 767.98px) {

        /* =====================================
           BODY
        ===================================== */
        body {
            margin: 0;
            min-height: 100vh;
            background: #f5f5f5;

            /* ruang untuk bottom nav */
            padding-bottom: 64px;
        }

        /* Hide desktop */
        .desktop-only {
            display: none !important;
        }

        /* =====================================
           HEADER
        ===================================== */
        .mobile-header {
            position: sticky;
            top: 0;
            z-index: 1040;

            background: #1a1a2e;

            padding: 12px 16px;

            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }

        /* =====================================
           BANNER
        ===================================== */

        /* WRAPPER */
        .mobile-banner-wrap {
            width: 100%;

            background: #1a1a2e;

            padding: 0 16px 16px;

            margin-top: -1px;

            border: none !important;
            box-shadow: none !important;
        }

        /* CAROUSEL */
        #mobileBanner {
            width: 100%;

            border-radius: 20px;
            overflow: hidden;

            border: none !important;
            box-shadow: none !important;
            background: transparent;
        }

        /* INNER */
        #mobileBanner .carousel-inner,
        #mobileBanner .carousel-item {
            border: none !important;
            box-shadow: none !important;
            background: transparent;
        }

        /* ITEM */
        .mobile-banner-item {
            position: relative;

            width: 100%;

            /* responsive ratio */
            aspect-ratio: 16 / 8;

            min-height: 180px;
            max-height: 320px;

            overflow: hidden;
            border-radius: 20px;
        }

        /* IMAGE */
        .mobile-banner-image {
            width: 100%;
            height: 100%;

            object-fit: cover;
            display: block;
        }

        /* OVERLAY */
        .mobile-banner-overlay {
            position: absolute;
            inset: 0;

            background:
                linear-gradient(
                    to top,
                    rgba(0,0,0,0.65) 0%,
                    transparent 55%
                );
        }

        /* TEXT */
        .mobile-banner-content {
            position: absolute;
            left: 14px;
            bottom: 14px;
        }

        .banner-badge {
            background: rgba(255,255,255,0.18);

            color: #fff;

            font-size: 10px;

            border-radius: 20px;

            padding: 3px 10px;

            display: inline-block;

            margin-bottom: 4px;

            backdrop-filter: blur(6px);
        }

        .banner-title {
            color: #fff;

            font-weight: 600;

            line-height: 1.3;

            font-size: clamp(14px, 4vw, 18px);
        }

        .banner-sub {
            color: rgba(255,255,255,0.78);

            margin-top: 2px;

            font-size: clamp(10px, 2.8vw, 12px);
        }

        /* INDICATOR */
        .carousel-indicators {
            margin-bottom: 8px;
        }

        .carousel-indicators button {
            border: none !important;
            transition: all .25s ease;
        }

        /* =====================================
           MAIN CONTENT
        ===================================== */
        .main-content-scrollable {
            width: 100%;
            background: #f5f5f5;
        }

        /* =====================================
           CATEGORY MENU
        ===================================== */
        .mobile-menu-wrap {
            padding: 16px 16px 8px;
        }

        .mobile-menu-grid {
            background: #fff;

            border-radius: 16px;

            padding: 16px;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.04);
        }

        .mobile-menu-grid .category-grid {
            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 16px 12px;
        }

        .mobile-menu-item {
            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 6px;

            text-decoration: none;

            color: #343a40;
        }

        .mobile-menu-icon {
            width: 48px;
            height: 48px;

            background: #f8f9fa;

            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;

            transition: all .2s ease;
        }

        .mobile-menu-item:active .mobile-menu-icon {
            background: #e9f5ee;
            transform: scale(.96);
        }

        /* =====================================
           PROMO CARD
        ===================================== */
        .mobile-promo-wrap {
            padding: 8px 16px 24px;
        }

        .mobile-promo-card {
            background: #fff;

            border-radius: 16px;

            overflow: hidden;

            margin-bottom: 16px;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.04);
        }

        .mobile-section-title {
            font-size: 14px;

            font-weight: 600;

            color: #212529;

            margin-bottom: 12px;
        }

        /* =====================================
           BOTTOM NAV
        ===================================== */
        .mobile-bottom-nav {
            display: flex;

            position: fixed;

            bottom: 0;
            left: 0;
            right: 0;

            z-index: 1050;

            background: #fff;

            height: 64px;

            border-top: 1px solid #e9ecef;

            box-shadow:
                0 -4px 12px rgba(0,0,0,0.05);
        }

        .mobile-bottom-nav a {
            flex: 1;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 4px;

            text-decoration: none;

            color: #adb5bd;

            font-size: 10px;

            transition: color .2s ease;
        }

        .mobile-bottom-nav a.active,
        .mobile-bottom-nav a:hover {
            color: #23315e;
        }

        .mobile-bottom-nav a i {
            font-size: 22px;
        }
    }

    /* =========================================
       TABLET
    ========================================= */
    @media (min-width: 768px) and (max-width: 1199px) {

        .mobile-banner-item {
            aspect-ratio: 16 / 6;
            max-height: 380px;
        }
    }

    /* =========================================
       DESKTOP
    ========================================= */
    @media (min-width: 1200px) {

        .mobile-banner-item {
            aspect-ratio: 16 / 5;
            max-height: 500px;
        }
    }

    /* =========================================
       DESKTOP ONLY
    ========================================= */
    @media (min-width: 768px) {

        .mobile-only {
            display: none !important;
        }
    }


    
</style>


@endpush