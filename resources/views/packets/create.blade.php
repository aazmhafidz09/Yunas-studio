@extends('layouts.master')

@section('title')
    Create Packet
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Create Packet @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Create New Packet</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('packets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Packet Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price (Rp)</label>
                                            <input type="number" class="form-control" id="price" name="price" value="{{ old('price') }}" min="0" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_photos_for_edit" class="form-label">Max Photos for Edit</label>
                                            <input type="number" class="form-control" id="max_photos_for_edit" name="max_photos_for_edit" value="{{ old('max_photos_for_edit', 10) }}" min="0" required>
                                        </div>
                                    </div>
                                     <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_minutes" class="form-label">
                                                Durasi Pemotretan
                                            </label>
                                            <div class="input-group has-validation">
                                                <input type="number"
                                                       class="form-control js-duration-input @error('duration_minutes') is-invalid @enderror"
                                                       id="duration_minutes"
                                                       name="duration_minutes"
                                                       value="{{ old('duration_minutes', 60) }}"
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
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Product</label>
                                            <select class="form-select" id="product_id" name="product_id" required>
                                                <option value="" {{ empty(old('product_id', $selectedProductId ?? '')) ? 'selected' : '' }} disabled>Select Product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" {{ old('product_id', $selectedProductId ?? '') == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Packet Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                                    <small class="form-text text-muted">Upload a packet image (max 15MB). Supported formats: JPEG, PNG, JPG, GIF.</small>
                                </div>
                                <div class="mb-3">
                                    <div class="mt-3 text-center">
                                        <img id="image-preview" src="#" alt="Image Preview" class="img-fluid img-thumbnail rounded" style="max-height: 200px; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ !empty($selectedProductId) ? route('packets.product', $selectedProductId) : route('packets.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Packet</button>
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