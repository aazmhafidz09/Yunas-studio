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
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Packet Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $packet->name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $packet->description) }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price (Rp)</label>
                                            <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $packet->price) }}" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="max_photos_for_edit" class="form-label">Max Photos for Edit</label>
                                            <input type="number" class="form-control" id="max_photos_for_edit" name="max_photos_for_edit" value="{{ old('max_photos_for_edit', $packet->max_photos_for_edit) }}" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Product</label>
                                            <select class="form-select" id="product_id" name="product_id" required>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" {{ old('product_id', $packet->product_id) == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
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
                                                <option value="" disabled {{ old('duration_minutes', $packet->duration_minutes) ? '' : 'selected' }}>
                                                    -- Pilih Durasi --
                                                </option>
                                                @foreach($durations as $value => $label)
                                                    <option value="{{ $value }}"
                                                        {{ old('duration_minutes', $packet->duration_minutes) == $value ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('duration_minutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Packet Image</label>
                                    @if($packet->image)
                                        <div class="mb-2 text-center">
                                            <img src="{{ Storage::url($packet->image) }}" alt="{{ $packet->name }}" class="img-fluid img-thumbnail rounded" style="max-height: 150px;">
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('packets.index') }}" class="btn btn-secondary">Cancel</a>
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