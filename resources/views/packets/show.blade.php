@extends('layouts.master')

@section('title')
    {{ $packet->name }} - Packet Details
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Packet Details @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 text-center mb-3 mb-md-0">
                            @if($packet->image)
                                <img src="{{ Storage::url($packet->image) }}" alt="{{ $packet->name }}" class="img-fluid rounded" style="max-height: 300px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                                    <i class="bx bx-image-alt text-secondary" style="font-size: 5rem;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-7">
                            <h4 class="card-title">{{ $packet->name }}</h4>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary rounded-pill fs-6 mb-2">Rp {{ number_format($packet->price, 0, ',', '.') }}</span>
                                <span class="badge {{ $packet->is_active ? 'bg-success' : 'bg-danger' }} rounded-pill fs-6">
                                    {{ $packet->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <h5 class="text-muted fs-6">Product</h5>
                                <p>{{ $packet->product ? $packet->product->name : 'Uncategorized' }}</p>
                            </div>
                            
                            @if($packet->description)
                                <div class="mb-4">
                                    <h5 class="text-muted fs-6">Description</h5>
                                    <p>{{ $packet->description }}</p>
                                </div>
                            @endif
                            
                            <div class="d-flex mt-4">
                                <a href="{{ route('packets.index') }}" class="btn btn-secondary me-2">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Packets
                                </a>
                                <a href="{{ route('packets.edit', $packet->id) }}" class="btn btn-primary me-2">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                <form action="{{ route('packets.destroy', $packet->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this packet?');">
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

            <!-- Default Additionals Section -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">Default Additionals</h5>
                        <a href="{{ route('additional-defaults.index', ['packet_id' => $packet->id]) }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-cog me-1"></i> Manage Additionals
                        </a>
                    </div>
                    
                    @if($packet->additionalDefaults->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Additional</th>
                                        <th>Quantity</th>
                                        <th>Value</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($packet->combined_defaults as $default)
                                        <tr>
                                            <td>{{ $default->name }}</td>
                                            <td>{{ $default->quantity }}</td>
                                            <td>{{ $default->note ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="2" class="text-end">Total Value:</th>
                                        <th>
                                            Rp {{ number_format($packet->additionalDefaults->sum(function($default) {
                                                return $default->additional->price * $default->quantity;
                                            }), 0, ',', '.') }}
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            No default additionals have been added to this packet yet. 
                            <a href="{{ route('additional-defaults.index', ['packet_id' => $packet->id]) }}" class="alert-link">Add some now</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product Information</h5>
                    
                    @if($packet->product)
                        <div class="text-center mb-4">
                            @if($packet->product->image)
                                <img src="{{ Storage::url($packet->product->image) }}" alt="{{ $packet->product->name }}" class="img-fluid rounded" style="max-height: 150px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 150px;">
                                    <i class="bx bx-image-alt text-secondary" style="font-size: 3rem;"></i>
                                </div>
                            @endif
                            
                            <h5 class="mt-3">{{ $packet->product->name }}</h5>
                            
                            @if($packet->product->description)
                                <p class="text-muted small">{{ \Str::limit($packet->product->description, 100) }}</p>
                            @endif
                            
                            <!-- <a href="{{ route('products.show', $packet->product->id) }}" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bx bx-show me-1"></i> View Product Details
                            </a> -->
                        </div>
                    @else
                        <div class="alert alert-info" role="alert">
                            This packet is not assigned to any product.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Price Summary Card -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Price Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Base Price:</span>
                        <span class="fw-bold">Rp {{ number_format($packet->price, 0, ',', '.') }}</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Default Additionals:</span>
                        <span class="fw-bold">
                            Rp {{ number_format($packet->additionalDefaults->sum(function($default) {
                                return $default->additional->price * $default->quantity;
                            }), 0, ',', '.') }}
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Value:</span>
                        <span class="fw-bold text-primary">
                            Rp {{ number_format(
                                $packet->price + $packet->additionalDefaults->sum(function($default) {
                                    return $default->additional->price * $default->quantity;
                                }), 0, ',', '.'
                            ) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection 