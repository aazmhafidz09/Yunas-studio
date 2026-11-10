<section id="paket" class="desktop-only bg-light py-5">
    <div class="container py-4">

        {{-- Heading --}}
        <div class="text-center mb-5">
            <span class="badge bg-secondary text-uppercase fw-semibold px-3 py-2 mb-3 rounded-pill"
                style="font-size: 0.7rem; letter-spacing: 0.15em;">
                Layanan Kami
            </span>
            <h2 class="display-5 fw-bold text-dark mb-3">
                Pilihan Paket Foto
            </h2>
            <p class="text-muted mx-auto" style="max-width: 500px;">
                Pilih paket foto yang sesuai dengan kebutuhan Anda.
                Semua paket sudah termasuk peralatan studio dan editing profesional.
            </p>
        </div>

        {{-- Product List --}}
        <div class="row g-4">
            @foreach($packets as $productName => $packetGroup)
                @php
                    $product = $packetGroup->first()->product;
                    $imageUrl = $product?->image
                        ? asset('storage/' . $product->image)
                        : asset('images/no-image.jpg');
                @endphp

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 product-card">

                        {{-- Image --}}
                        <div class="overflow-hidden" style="height: 256px;">
                            <img src="{{ $imageUrl }}"
                                class="card-img-top w-100 h-100 object-fit-cover product-image"
                                alt="{{ $productName }}"
                                loading="lazy">
                        </div>

                        {{-- Body --}}
                        <div class="card-body p-4">
                            <h5 class="card-title fw-semibold mb-2">{{ $productName }}</h5>
                            <p class="card-text text-muted small mb-3">
                                {{ $product?->description ?? '' }}
                            </p>
                            <button class="btn btn-dark"
                                onclick="showProductDetail({{ $packetGroup->first()->product_id }}); return false;">
                                Lihat Paket
                            </button>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>

<style>
    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .product-image {
        transition: transform 0.5s ease;
    }

    .product-card:hover .product-image {
        transform: scale(1.05);
    }
</style>