<div class="mobile-banner-wrap">

    <div id="mobileBanner" class="carousel slide overflow-hidden" data-bs-ride="carousel"
        style="border:none; box-shadow:none; border-radius:20px;">

        <div class="carousel-inner">
            @php
                $banners = [
                    ['image' => 'Birthday.jpg', 'label' => 'Promo Spesial', 'title' => 'Foto Birthday', 'sub' => 'Mulai Rp 400.000'],
                    ['image' => 'Groupband.jpg', 'label' => 'Populer', 'title' => 'Foto Group', 'sub' => 'Mulai Rp 130.000'],
                    ['image' => 'couple.jpg', 'label' => 'Romantis', 'title' => 'Foto Couple', 'sub' => 'Mulai Rp 250.000'],
                    ['image' => 'Graduation Session.jpg', 'label' => 'Wisuda', 'title' => 'Foto Graduation', 'sub' => 'Mulai Rp 400.000'],
                ];
            @endphp

            @foreach($banners as $i => $banner)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    <div class="mobile-banner-item">
                        <img src="{{ asset('images/' . $banner['image']) }}" class="mobile-banner-image"
                            alt="{{ $banner['title'] }}">

                        {{-- Overlay --}}
                        <div style="position:absolute; inset:0;
                                        background:linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 55%);
                                        border-radius:inherit;">
                        </div>

                        <div style="position:absolute; bottom:14px; left:14px;">

                            <span style="background:rgba(255,255,255,0.18);
                                             color:#fff;
                                             font-size:10px;
                                             border-radius:20px;
                                             padding:3px 10px;
                                             display:inline-block;
                                             margin-bottom:4px;">
                                {{ $banner['label'] }}
                            </span>

                            <div style="color:#fff;
                                            font-size:15px;
                                            font-weight:600;
                                            line-height:1.3;">
                                {{ $banner['title'] }}
                            </div>

                            <div style="color:rgba(255,255,255,0.75);
                                            font-size:11px;
                                            margin-top:2px;">
                                {{ $banner['sub'] }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="carousel-indicators" style="margin-bottom:8px;">
            @foreach($banners as $i => $banner)
                <button type="button" data-bs-target="#mobileBanner" data-bs-slide-to="{{ $i }}"
                    class="{{ $i === 0 ? 'active' : '' }}" style="width:{{ $i === 0 ? '16px' : '6px' }};
                                   height:6px;
                                   border-radius:3px;
                                   background:#fff;
                                   border:none;
                                   opacity:{{ $i === 0 ? '1' : '0.4' }};">
                </button>
            @endforeach
        </div>

    </div>
</div>