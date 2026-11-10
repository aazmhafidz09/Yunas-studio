@extends('layouts.master')

@section('title')
    Expense Categories
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Expense Categories @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title">Expense Categories</h4>
                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary btn-sm mt-2">
                                <i class="bx bx-arrow-back me-1"></i> Back to Expenses
                            </a>
                        </div>
                        <a href="{{ route('expense-categories.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Add New Category
                        </a>
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
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Name</th>
                                    <th width="15%">Type</th>
                                    <th width="15%">Monthly Default</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $key => $category)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $category->name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $category->type_color }}">{{ $category->type_label }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($category->is_monthly_default)
                                                <span class="badge bg-success">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('expense-categories.edit', $category->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <form action="{{ route('expense-categories.toggle-monthly-default', $category->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm {{ $category->is_monthly_default ? 'btn-warning' : 'btn-success' }}" title="{{ $category->is_monthly_default ? 'Remove from monthly default' : 'Set as monthly default' }}">
                                                        <i class="bx {{ $category->is_monthly_default ? 'bx-x' : 'bx-check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('expense-categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
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
                                        <td colspan="5" class="text-center">No categories found</td>
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