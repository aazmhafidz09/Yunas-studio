@extends('layouts.master')

@section('title')
    Default Additionals for {{ $packet->name }}
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Default Additionals @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Default Additionals for {{ $packet->name }}</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdditionalModal">
                            <i class="bx bx-plus me-1"></i> Add Default Additional
                        </button>
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
                                    <th>Additional</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total Value</th>
                                    <th>Note</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packet->additionalDefaults as $default)
                                    <tr>
                                        <td>{{ $default->id }}</td>
                                        <td>{{ $default->additional->name }}</td>
                                        <td>Rp {{ number_format($default->additional->price, 0, ',', '.') }}</td>
                                        <td>{{ $default->quantity }}</td>
                                        <td>Rp {{ number_format($default->additional->price * $default->quantity, 0, ',', '.') }}</td>
                                        <td>{{ $default->note ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <button type="button" class="btn btn-sm btn-primary me-2" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editAdditionalModal{{ $default->id }}">
                                                    <i class="bx bx-edit"></i> Edit
                                                </button>
                                                <form action="{{ route('additional-defaults.destroy', $default->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this default additional?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bx bx-trash"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editAdditionalModal{{ $default->id }}" tabindex="-1" aria-labelledby="editAdditionalModalLabel{{ $default->id }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form action="{{ route('additional-defaults.update', $default->id) }}" method="POST">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editAdditionalModalLabel{{ $default->id }}">Edit Default Additional</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="packet_id" value="{{ $packet->id }}">
                                                                <input type="hidden" name="additional_id" value="{{ $default->additional_id }}">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="additional" class="form-label">Additional</label>
                                                                    <input type="text" class="form-control" value="{{ $default->additional->name }} (Rp {{ number_format($default->additional->price, 0, ',', '.') }})" disabled>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="quantity{{ $default->id }}" class="form-label">Quantity</label>
                                                                    <input type="number" class="form-control" id="quantity{{ $default->id }}" name="quantity" value="{{ $default->quantity }}" min="1" required>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="note{{ $default->id }}" class="form-label">Note</label>
                                                                    <textarea class="form-control" id="note{{ $default->id }}" name="note" rows="2">{{ $default->note }}</textarea>
                                                                    <small class="text-muted">Optional: Add any notes about this additional</small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No default additionals found for this packet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th colspan="4" class="text-end">Total Value of Default Additionals:</th>
                                    <th>
                                        Rp {{ number_format($packet->additionalDefaults->sum(function($default) {
                                            return $default->additional->price * $default->quantity;
                                        }), 0, ',', '.') }}
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('packets.show', $packet->id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Packet Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Additional Modal -->
    <div class="modal fade" id="addAdditionalModal" tabindex="-1" aria-labelledby="addAdditionalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('additional-defaults.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAdditionalModalLabel">Add Default Additional</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="packet_id" value="{{ $packet->id }}">
                        
                        <div class="mb-3">
                            <label for="additional_id" class="form-label">Additional</label>
                            <select class="form-select" id="additional_id" name="additional_id" required>
                                <option value="" selected disabled>Select Additional</option>
                                @foreach($additionals as $additional)
                                    @if(!$packet->additionalDefaults->contains('additional_id', $additional->id))
                                        <option value="{{ $additional->id }}" data-price="{{ $additional->price }}">
                                            {{ $additional->name }} - Rp {{ number_format($additional->price, 0, ',', '.') }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="note" class="form-label">Note</label>
                            <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                            <small class="text-muted">Optional: Add any notes about this additional</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection 