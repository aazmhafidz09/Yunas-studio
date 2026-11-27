@extends('layouts.master')

@section('title')
    Edit Packet
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Edit Packet @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Edit Packet Details</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('packets.update', $packet->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row g-4">
                            {{-- ── Kolom kiri: detail paket ── --}}
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Packet Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $packet->name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $packet->description) }}</textarea>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="product_id" class="form-label">Product</label>
                                        <select class="form-select" id="product_id" name="product_id" required>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ old('product_id', $packet->product_id) == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="price" class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" id="price" name="price" value="{{ old('price', (int) $packet->price) }}" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_photos_for_edit" class="form-label">Max Photos for Edit</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="max_photos_for_edit" name="max_photos_for_edit" value="{{ old('max_photos_for_edit', $packet->max_photos_for_edit) }}" min="0" required>
                                            <span class="input-group-text">foto</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="duration_minutes" class="form-label">Durasi Pemotretan</label>
                                        <div class="input-group has-validation">
                                            <input type="number"
                                                   class="form-control js-duration-input @error('duration_minutes') is-invalid @enderror"
                                                   id="duration_minutes"
                                                   name="duration_minutes"
                                                   value="{{ old('duration_minutes', $packet->duration_minutes) }}"
                                                   min="15" max="480" step="1" required>
                                            <span class="input-group-text">menit</span>
                                            @error('duration_minutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted d-block mt-1 js-duration-hint">
                                            Slot jadwal dibulatkan per 30 menit (sisa &le; 15 menit dibulatkan ke bawah).
                                        </small>
                                        <script>
                                            (function () {
                                                const input = document.currentScript.parentElement.querySelector('.js-duration-input');
                                                const hint  = document.currentScript.parentElement.querySelector('.js-duration-hint');
                                                const fmt = m => (Math.floor(m / 60) ? Math.floor(m / 60) + ' jam' : '') + (m % 60 ? (m >= 60 ? ' ' : '') + (m % 60) + ' menit' : '');
                                                const round = m => { let b = Math.floor(m / 30); if (m % 30 > 15) b++; return Math.max(1, b) * 30; };
                                                const update = () => {
                                                    const m = parseInt(input.value, 10);
                                                    if (!m || m < 1) return;
                                                    const r = round(m);
                                                    hint.textContent = 'Menempati slot jadwal: ' + fmt(r) + ' (' + (r / 30) + ' slot)' + (r !== m ? ' — dibulatkan dari ' + fmt(m) : '');
                                                };
                                                input.addEventListener('input', update);
                                                update();
                                            })();
                                        </script>
                                    </div>
                                </div>
                            </div>

                            {{-- ── Kolom kanan: gambar paket ── --}}
                            <div class="col-lg-4">
                                <label for="image" class="form-label">Packet Image</label>
                                <div class="border rounded p-2 mb-2 text-center bg-light">
                                    @if($packet->image)
                                        <img src="{{ Storage::url($packet->image) }}" alt="{{ $packet->name }}" class="img-fluid rounded" style="max-height: 180px; object-fit: cover;">
                                    @else
                                        <div class="text-muted py-5">
                                            <i class="bx bx-image-alt" style="font-size: 40px;"></i>
                                            <div class="small">Belum ada gambar</div>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('packets.product', $packet->product_id) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Packet Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Manage Included Prints</h4>

                    @if($packet->printOptions->isNotEmpty())
                        <ul class="list-group mb-4">
                            @foreach($packet->printOptions as $option)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $option->pivot->quantity }}x {{ $option->name }}</span>
                                    <form action="{{ route('packets.removePrintOption', ['packet' => $packet->id, 'print_size' => $option->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">&times;</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted text-center">No print options included in this packet yet.</p>
                    @endif

                    <hr>
                    <h5 class="font-size-14 mb-3">Add New Print Option</h5>
                    <form action="{{ route('packets.addPrintOption', $packet) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="print_size_id" class="form-label">Print Size</label>
                            <select class="form-select" name="print_size_id" required>
                                <option value="" disabled selected>Choose a size...</option>
                                @foreach($printSizes as $size)
                                    <option value="{{ $size->id }}">{{ $size->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Add Print Option</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function previewImage(input) {
            var preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
@endsection