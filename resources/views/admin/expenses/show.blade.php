@extends('layouts.master')

@section('title')
    Expense Details
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Expense Details @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Expense Details</h4>
                        <div>
                            <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-primary me-2">
                                <i class="bx bx-edit me-1"></i> Edit
                            </a>
                            <a href="{{ route('expenses.index', request()->query()) }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Number</th>
                                    <td>{{ $expense->number }}</td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $expense->name }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td>{{ $expense->formatted_amount }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $expense->expense_date->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td>{{ $expense->category ? $expense->category->name : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $expense->keterangan ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $expense->created_at->format('d F Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $expense->updated_at->format('d F Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($expense->receipt_image)
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">Receipt Image</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <img src="{{ asset('storage/' . $expense->receipt_image) }}" alt="Receipt Image" class="img-fluid img-thumbnail" style="max-height: 400px;">
                                    </div>
                                </div>
                            @else
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0">Receipt Image</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <p class="text-muted">No receipt image available</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bx bx-trash me-1"></i> Delete Expense
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection