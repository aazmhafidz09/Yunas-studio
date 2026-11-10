@extends('layouts.master')

@section('title')
    Create Expense Category
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Create Expense Category @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Create New Expense Category</h4>
                    
                    <form action="{{ route('expense-categories.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Category Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                                <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                <option value="debt" {{ old('type') == 'debt' ? 'selected' : '' }}>Hutang</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <span class="badge bg-danger me-1">Pengeluaran</span> untuk biaya keluar,
                                <span class="badge bg-success me-1">Pemasukan</span> untuk pendapatan,
                                <span class="badge bg-warning me-1">Hutang</span> untuk kewajiban yang belum dibayar
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_monthly_default" value="0">
                            <input type="checkbox" class="form-check-input" id="is_monthly_default" name="is_monthly_default" value="1" {{ old('is_monthly_default') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_monthly_default">Set as Monthly Default</label>
                            <div class="form-text">If checked, this category will be automatically generated each month.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection