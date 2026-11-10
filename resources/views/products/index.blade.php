@extends('layouts.master')

@section('title')
    Products
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Products List @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Products List</h4>
                        <div>
                            <a href="{{ route('products.create') }}" class="btn btn-primary me-2">
                                <i class="bx bx-plus me-1"></i> Add New Product
                            </a>
                            <a href="{{ route('packets.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Packets
                            </a>
                        </div>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>{{ $product->id }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($product->description, 50) }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('packets.product', $product->id) }}" class="btn btn-sm btn-info me-1" title="View Packets">
                                                    <i class="bx bx-package"></i>
                                                </a>
                                                <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <form action="{{ route('products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product? This will also delete all associated packets.');">
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
                                        <td colspan="4" class="text-center">
                                            No products found. <a href="{{ route('products.create') }}" class="alert-link">Add your first product</a>.
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