@extends('layouts.master')

@section('title')
    @if(isset($product))
        {{ $product->name }} - Packets
    @else
        Packets
    @endif
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') 
            @if(isset($product))
                {{ $product->name }}
            @else
                Products List
            @endif
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Products List</h4>
                    
                    <div class="list-group">
                        <a href="{{ route('packets.index') }}" class="list-group-item list-group-item-action {{ request()->route()->getName() == 'packets.index' ? 'active' : '' }}">
                            All Products
                        </a>
                        @foreach($products as $prod)
                            <a href="{{ route('packets.product', $prod->id) }}" class="list-group-item list-group-item-action {{ isset($product) && $product->id == $prod->id ? 'active' : '' }}">
                                {{ $prod->name }}
                            </a>
                        @endforeach
                    </div>
                    
                    @auth
                        <div class="mt-4">
                            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bx bx-cog me-1"></i> Manage Products
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            @if(isset($product))
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 120px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 120px;">
                                    <i class="bx bx-image-alt text-secondary" style="font-size: 3rem;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-9">
                            <h4 class="card-title">{{ $product->name }}</h4>
                            <p class="text-muted">
                                @if($product->description)
                                    {{ $product->description }}
                                @else
                                    <span class="fst-italic">No description available</span>
                                @endif
                            </p>
                            <div class="badge bg-info text-white">
                                <i class="bx bx-package me-1"></i> {{ $packets->count() }} Packets
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">
                            @if(isset($product))
                                {{ $product->name }} Packets
                            @else
                                Packets List
                            @endif
                        </h4>
                        <a href="{{ route('packets.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Packet
                        </a>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    
                                    <th>Packet</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packets as $packet)
                                    <tr class="{{ $packet->is_active ? '' : 'table-secondary' }}">
                                        
                                        <td>{{ $packet->name }}</td>
                                        <td>{{ $packet->product ? $packet->product->name : 'Uncategorized' }}</td>
                                        <td class="text-primary fw-bold">Rp {{ number_format($packet->price, 0, ',', '.') }}</td>
                                        <td>
                                            @if($packet->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('packets.show', $packet->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('packets.edit', $packet->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <a href="{{ route('additional-defaults.index', ['packet_id' => $packet->id]) }}" class="btn btn-sm btn-success me-1" title="Manage Default Additionals">
                                                    <i class="bx bx-package"></i>
                                                </a>
                                                @auth
                                                <form action="{{ route('packets.toggle-status', $packet->id) }}" method="POST" class="me-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-{{ $packet->is_active ? 'warning' : 'success' }}">
                                                        <i class="bx bx-power-off"></i>
                                                    </button>
                                                </form>
                                                @endauth
                                                <form action="{{ route('packets.destroy', $packet->id) }}" method="POST" onsubmit="return confirm('delete this packet?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            No packets found. <a href="{{ route('packets.create') }}" class="alert-link">Add your packet</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 