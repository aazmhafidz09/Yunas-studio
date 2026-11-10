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
                                            <select class="form-select @error('duration_minutes') is-invalid @enderror"
                                                    id="duration_minutes"
                                                    name="duration_minutes"
                                                    required>
                                                @php
                                                    $durations = [
                                                        30  => '30 menit',
                                                        60  => '1 jam',
                                                        90  => '1 jam 30 menit',
                                                        120 => '2 jam',
                                                        150 => '2 jam 30 menit',
                                                        180 => '3 jam',
                                                        210 => '3 jam 30 menit',
                                                        240 => '4 jam',
                                                    ];
                                                @endphp
                                                <option value="" disabled {{ old('duration_minutes') ? '' : 'selected' }}>
                                                    -- Pilih Durasi --
                                                </option>
                                                @foreach($durations as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('duration_minutes', 60) == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('duration_minutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Product</label>
                                            <select class="form-select" id="product_id" name="product_id" required>
                                                <option value="" selected disabled>Select Product</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
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
                            <a href="{{ route('packets.index') }}" class="btn btn-secondary">Cancel</a>
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