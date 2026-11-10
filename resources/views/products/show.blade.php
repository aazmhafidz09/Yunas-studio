@extends('layouts.master')

@section('title')
    {{ $product->name }} - Product Details
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Product Details @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 text-center mb-3 mb-md-0">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 300px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                                    <i class="bx bx-image-alt text-secondary" style="font-size: 5rem;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-7">
                            <h4 class="card-title">{{ $product->name }}</h4>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary rounded-pill fs-6 mb-2">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-danger' }} rounded-pill fs-6">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <h5 class="text-muted fs-6">Product</h5>
                                <p>{{ $product->category ? $product->category->name : 'Uncategorized' }}</p>
                            </div>
                            
                            @if($product->description)
                                <div class="mb-4">
                                    <h5 class="text-muted fs-6">Description</h5>
                                    <p>{{ $product->description }}</p>
                                </div>
                            @endif
                            
                            <div class="d-flex mt-4">
                                <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Products
                                </a>
                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary me-2">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bx bx-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Packets</h5>
                    
                    @if($product->packets->count() > 0)
                        <div class="list-group">
                            @foreach($product->packets as $packet)
                                <a href="{{ route('packets.show', $packet->id) }}" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $packet->name }}</h6>
                                        <span class="text-primary">Rp {{ number_format($packet->price, 0, ',', '.') }}</span>
                                    </div>
                                    <p class="mb-1 text-muted small">{{ \Illuminate\Support\Str::limit($packet->description, 50) }}</p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info" role="alert">
                            No packets have been created for this product yet.
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <a href="{{ route('packets.create') }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus me-1"></i> Add New Packet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 