<section id="galeri-mobile" class="py-4 bg-white">
    <div class="container-fluid px-3">

        {{-- Heading --}}
        <div class="text-center mb-4">
            <span class="badge bg-secondary text-uppercase fw-semibold px-3 py-2 mb-2 rounded-pill"
                style="font-size: 0.65rem; letter-spacing: 0.12em;">
                Galeri Foto
            </span>

            <h4 class="fw-bold text-dark mb-2">
                Hasil Karya Kami
            </h4>

            <p class="text-muted small mb-0 px-2">
                Beberapa hasil foto terbaik dari berbagai sesi yang telah kami kerjakan.
            </p>
        </div>

        {{-- Gallery Grid --}}
        <div class="row g-2">

            {{-- Item 1 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/Potrait.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 220px;"
                        alt="Portrait">
                </div>
            </div>

            {{-- Item 2 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/wedding.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 220px;"
                        alt="Wedding">
                </div>
            </div>

            {{-- Item 3 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/Family Package.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 170px;"
                        alt="Family">
                </div>
            </div>

            {{-- Item 4 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/Graduation Session.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 170px;"
                        alt="Graduation">
                </div>
            </div>

            {{-- Item 5 --}}
            <div class="col-12">
                <div class="overflow-hidden rounded-4 shadow-sm position-relative">
                    <img src="{{ asset('images/couple.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 230px;"
                        alt="Couple">

                    <div class="position-absolute bottom-0 start-0 w-100 p-3"
                        style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">

                        <h6 class="text-white fw-semibold mb-1">
                            Couple Session
                        </h6>

                        <small class="text-light">
                            Capture your beautiful moments
                        </small>
                    </div>
                </div>
            </div>

            {{-- Item 6 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/Groupband.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 170px;"
                        alt="Band">
                </div>
            </div>

            {{-- Item 7 --}}
            <div class="col-6">
                <div class="overflow-hidden rounded-4 shadow-sm">
                    <img src="{{ asset('images/Birthday.jpg') }}"
                        class="w-100 object-fit-cover"
                        style="height: 170px;"
                        alt="Birthday">
                </div>
            </div>
        </div>
    </div>
</section>