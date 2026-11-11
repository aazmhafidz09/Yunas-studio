@extends('layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('title')
    Expenses
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* Dropdown styling */
        .btn-group .dropdown-menu {
            min-width: 6rem;
        }
        .btn-group .dropdown-item.active {
            background-color: #f8f9fa;
            color: #495057;
        }
        .btn-sm.dropdown-toggle {
            padding: 0.25rem 0.5rem;
            font-size: 0.76563rem;
            border-radius: 0.2rem;
        }
        
        /* Filter status indicators */
        .filter-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Expenses @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title">Expenses List</h4>
                        </div>
                        <div>
                            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#generateMonthlyModal">
                                <i class="bx bx-refresh me-1"></i> Generate Monthly
                            </button>
                            <button type="button" class="btn btn-primary" id="btn-add-expense">
                                <i class="bx bx-plus me-1"></i> Add New Expense
                            </button>
                            <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary me-2">
                                <i class="bx bx-category me-1"></i> Manage Categories
                            </a>
                        </div>
                    </div>
                    
                    <!-- Generate Monthly Expenses Modal -->
                    <div class="modal fade" id="generateMonthlyModal" tabindex="-1" aria-labelledby="generateMonthlyModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="generateMonthlyModalLabel">Generate Monthly Expenses</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="generateMonthlyForm" action="{{ route('expenses.generate-monthly') }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <p>This will generate default monthly expenses for the selected month and year.</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="monthly_month" class="form-label">Month</label>
                                                    <select class="form-select" id="monthly_month" name="month" required>
                                                        @foreach(range(1, 12) as $m)
                                                            <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="monthly_year" class="form-label">Year</label>
                                                    <select class="form-select" id="monthly_year" name="year" required>
                                                        @foreach(range(date('Y')-2, date('Y')+2) as $y)
                                                            <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>
                                                                {{ $y }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="btn-submit-generate-monthly">Generate</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card mini-stats-wid">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <p class="text-muted fw-medium">Current Balance</p>
                                                            <h4 class="mb-0 {{ ($currentBalance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="stat-current-balance">
                                                                Rp {{ number_format($currentBalance ?? 0, 0, ',', '.') }}
                                                            </h4>
                                                        </div>
                                                        <div>
                                                            <i class="bx bx-wallet font-size-24 {{ ($currentBalance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}" id="stat-balance-icon"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card mini-stats-wid">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <p class="text-muted fw-medium">Total Income</p>
                                                            <h4 class="mb-0 text-success" id="stat-total-income">Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}</h4>
                                                        </div>
                                                        <div>
                                                            <i class="bx bx-trending-up font-size-24 text-success"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card mini-stats-wid">
                                                <div class="card-body">
                                                    <div class="d-flex">
                                                        <div class="flex-grow-1">
                                                            <p class="text-muted fw-medium">Total Expenses</p>
                                                            <h4 class="mb-0 text-danger" id="stat-total-expenses">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
                                                        </div>
                                                        <div>
                                                            <i class="bx bx-trending-down font-size-24 text-danger"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Expenses by Category</h5>
                                    
                                    @if(isset($expensesByCategory) && count($expensesByCategory) > 0)
                                        @foreach($expensesByCategory->take(3) as $category)
                                            <div class="mb-3">
                                                <p class="mb-1 d-flex justify-content-between">
                                                    <span>{{ $category->category_name }}</span>
                                                    <span>Rp {{ number_format($category->total, 0, ',', '.') }}</span>
                                                </p>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                        style="width: {{ $totalExpenses > 0 ? ($category->total / $totalExpenses) * 100 : 0 }}%" 
                                                        aria-valuenow="{{ $totalExpenses > 0 ? ($category->total / $totalExpenses) * 100 : 0 }}" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No category data available</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">Filter Expenses</h5>
                                    <form action="{{ route('expenses.index') }}" method="GET" class="row g-3">
                                        <div class="col-md-2">
                                            <label for="filter_month" class="form-label">Month</label>
                                            <select name="month" id="filter_month" class="form-select">
                                                <option value="">All Months</option>
                                                @foreach($months as $key => $m)
                                                    <option value="{{ $key }}" {{ request('month') == $key ? 'selected' : '' }}>
                                                        {{ $m }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="filter_year" class="form-label">Year</label>
                                            <select name="year" id="filter_year" class="form-select">
                                                <option value="">All Years</option>
                                                @foreach($years as $y)
                                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                                        {{ $y }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label for="filter_category" class="form-label">Category</label>
                                            <select name="category_id" id="filter_category" class="form-select">
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <label for="filter_type" class="form-label">Type</label>
                                            <select name="type" id="filter_type" class="form-select">
                                                <option value="">All Types</option>
                                                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                                                <option value="debt" {{ request('type') == 'debt' ? 'selected' : '' }}>Hutang</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-filter-alt me-1"></i> Filter
                                            </button>
                                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                                                <i class="bx bx-reset me-1"></i> Reset
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="expenses-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Status Pembayaran</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="expenses-table-body">
                                @forelse($expenses as $expense)
                                    <tr id="expense-row-{{ $expense->id }}" class="{{ $expense->row_color }}" data-id="{{ $expense->id }}">
                                        <td>{{ $expense->id }}</td>
                                        <td class="expense-name fw-medium">{{ $expense->name }}</td>
                                        <td class="expense-amount fw-bold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                        <td class="expense-date">{{ $expense->expense_date->format('d M Y') }}</td>
                                        <td class="expense-type">
                                            <span class="badge bg-{{ $expense->type == 'income' ? 'success' : ($expense->type == 'expense' ? 'danger' : 'warning') }} type-badge">
                                                {{ $expense->type_label }}
                                            </span>
                                        </td>
                                        <td class="expense-category">{{ $expense->category ? $expense->category->name : '-' }}</td>
                                        <td class="expense-debt-status">
                                            @if($expense->type == 'debt')
                                                <div class="mb-1">
                                                    <span class="badge bg-{{ $expense->payment_status_color }} debt-status-badge">{{ $expense->payment_status_label }}</span>
                                                </div>
                                                @if($expense->remaining_amount > 0)
                                                    <div class="debt-progress-container">
                                                        <div class="progress" style="height: 8px; width: 100px;">
                                                            <div class="progress-bar bg-success debt-progress-bar" role="progressbar" 
                                                                style="width: {{ $expense->paymentProgress }}%" 
                                                                aria-valuenow="{{ $expense->paymentProgress }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <small class="text-muted debt-progress-text">{{ $expense->formattedPaidAmount }} / {{ $expense->formatted_amount }}</small>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td class="expense-description">{{ Str::limit($expense->description ?? '-', 30, '...') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center action-buttons">
                                                <a href="{{ route('expenses.show', $expense->id) }}" class="btn btn-sm btn-info me-1" title="Show">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary me-1 btn-edit-expense"
                                                        data-id="{{ $expense->id }}"
                                                        data-name="{{ $expense->name }}"
                                                        data-amount="{{ $expense->amount }}"
                                                        data-date="{{ $expense->expense_date->format('Y-m-d') }}"
                                                        data-type="{{ $expense->type }}"
                                                        data-category="{{ $expense->category_id }}"
                                                        data-description="{{ $expense->description }}"
                                                        data-image="{{ $expense->receipt_image ? Storage::url($expense->receipt_image) : '' }}"
                                                        title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                @if($expense->type == 'debt')
                                                    @if(!$expense->is_paid)
                                                        @if($expense->remaining_amount > 0)
                                                            <button type="button" class="btn btn-sm btn-warning me-1 btn-partial-modal" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#partialPaymentModal{{ $expense->id }}" 
                                                                    title="Partial Payment">
                                                                <i class="bx bx-money"></i>
                                                            </button>
                                                        @else
                                                            <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Pembayaran sudah lunas">
                                                                <i class="bx bx-money"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Pembayaran sudah lunas">
                                                            <i class="bx bx-money"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    <a href="{{ route('expenses.debt-payments', $expense->id) }}" class="btn btn-sm btn-info me-1" title="Payment History">
                                                        <i class="bx bx-history"></i>
                                                    </a>
                                                    
                                                    @php
                                                        $hasPartialPayments = $expense->paid_amount > 0;
                                                    @endphp
                                                    @if($hasPartialPayments && !$expense->is_paid)
                                                        <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Tidak dapat digunakan setelah pembayaran parsial">
                                                            <i class="bx bx-check"></i>
                                                        </button>
                                                    @elseif($expense->is_paid)
                                                        <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Pembayaran sudah lunas">
                                                            <i class="bx bx-check"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-success me-1 btn-confirm-paid" 
                                                                data-id="{{ $expense->id }}"
                                                                data-name="{{ $expense->name }}"
                                                                title="Mark as Paid">
                                                            <i class="bx bx-check"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                                <button type="button" class="btn btn-sm btn-danger btn-delete-expense"
                                                        data-id="{{ $expense->id }}"
                                                        data-name="{{ $expense->name }}"
                                                        title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-expenses-row">
                                        <td colspan="9" class="text-center">
                                            No expenses found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $expenses->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Expense Modal (Create & Edit) -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseModalLabel">Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="expenseForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="expense_id" name="id">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expense_name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="expense_name" name="name" required placeholder="Enter expense name">
                                <div class="invalid-feedback" id="error-expense-name"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expense_amount" class="form-label">Amount (IDR) *</label>
                                <input type="text" class="form-control" id="expense_amount" name="amount" required placeholder="Enter amount" oninput="formatExpenseCurrency(this)">
                                <div class="invalid-feedback" id="error-expense-amount"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="expense_date" class="form-label">Expense Date *</label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="error-expense-date"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="expense_type" class="form-label">Type *</label>
                                <select class="form-select" id="expense_type" name="type" required>
                                    <option value="expense">Pengeluaran</option>
                                    <option value="income">Pemasukan</option>
                                    <option value="debt">Hutang</option>
                                </select>
                                <div class="invalid-feedback" id="error-expense-type"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="expense_category_id" class="form-label">Category</label>
                                <select class="form-select" id="expense_category_id" name="category_id">
                                    <option value="">-- None --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="error-expense-category"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="expense_description" class="form-label">Description</label>
                            <textarea class="form-control" id="expense_description" name="description" rows="3" placeholder="Enter description (optional)"></textarea>
                            <div class="invalid-feedback" id="error-expense-description"></div>
                        </div>

                        <div class="mb-3">
                            <label for="expense_receipt_image" class="form-label">Receipt Image</label>
                            <input type="file" class="form-control" id="expense_receipt_image" name="receipt_image" accept="image/*" onchange="previewExpenseImage(this)">
                            <small class="form-text text-muted">Upload receipt (optional, max 15MB).</small>
                            <div class="invalid-feedback" id="error-expense-image"></div>
                            <div class="mt-2 text-center">
                                <img id="expense-image-preview" src="#" alt="Preview" class="img-fluid img-thumbnail rounded" style="max-height: 150px; display: none;">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-expense">
                            <i class="mdi mdi-content-save me-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modals for Debt Partial Payment -->
    @foreach($expenses as $expense)
        @if($expense->type == 'debt' && $expense->remaining_amount > 0)
            <div class="modal fade" id="partialPaymentModal{{ $expense->id }}" tabindex="-1" aria-labelledby="partialPaymentModalLabel{{ $expense->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="partialPaymentModalLabel{{ $expense->id }}">Partial Payment - {{ $expense->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="partial-payment-form" data-id="{{ $expense->id }}">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Debt Information</label>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <p class="mb-1"><strong>Total Amount:</strong> {{ $expense->formatted_amount }}</p>
                                            <p class="mb-1"><strong>Paid Amount:</strong> <span class="modal-paid-text">{{ $expense->formattedPaidAmount }}</span></p>
                                            <p class="mb-0"><strong>Remaining Amount:</strong> <span class="modal-remaining-text">{{ $expense->formattedRemainingAmount }}</span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="amount{{ $expense->id }}" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="amount{{ $expense->id }}" name="amount" 
                                           min="1" max="{{ $expense->remaining_amount }}" step="0.01" required>
                                    <div class="form-text max-amount-hint">Maximum: Rp {{ number_format($expense->remaining_amount, 0, ',', '.') }}</div>
                                </div>
                                <div class="mb-3">
                                    <label for="payment_date{{ $expense->id }}" class="form-label">Payment Date</label>
                                    <input type="date" class="form-control" id="payment_date{{ $expense->id }}" name="payment_date" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="notes{{ $expense->id }}" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes{{ $expense->id }}" name="notes" rows="3" placeholder="Optional payment notes"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-submit-partial">Make Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('script')
    <script src="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const expenseModalEl = document.getElementById('expenseModal');
        const expenseModal = new bootstrap.Modal(expenseModalEl);
        const expenseForm = document.getElementById('expenseForm');

        function formatExpenseCurrency(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            input.value = value;
        }

        function formatIDR(number) {
            return 'Rp ' + parseInt(number, 10).toLocaleString('id-ID');
        }

        function previewExpenseImage(input) {
            const preview = document.getElementById('expense-image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'inline-block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        function clearExpenseErrors() {
            $('#expense_name').removeClass('is-invalid');
            $('#expense_amount').removeClass('is-invalid');
            $('#expense_date').removeClass('is-invalid');
            $('#expense_type').removeClass('is-invalid');
            $('#error-expense-name').text('');
            $('#error-expense-amount').text('');
            $('#error-expense-date').text('');
            $('#error-expense-type').text('');
        }

        function updateTotalsInUi(totals) {
            if (!totals) return;
            if (totals.current_balance_formatted) {
                $('#stat-current-balance').text(totals.current_balance_formatted);
                if (totals.current_balance >= 0) {
                    $('#stat-current-balance').removeClass('text-danger').addClass('text-success');
                    $('#stat-balance-icon').removeClass('text-danger').addClass('text-success');
                } else {
                    $('#stat-current-balance').removeClass('text-success').addClass('text-danger');
                    $('#stat-balance-icon').removeClass('text-success').addClass('text-danger');
                }
            }
            if (totals.total_income_formatted) {
                $('#stat-total-income').text(totals.total_income_formatted);
            }
            if (totals.total_expenses_formatted) {
                $('#stat-total-expenses').text(totals.total_expenses_formatted);
            }
        }

        // Open Modal for Add
        $('#btn-add-expense').on('click', function() {
            $('#expenseModalLabel').text('Add New Expense');
            $('#expense_id').val('');
            expenseForm.reset();
            $('#expense_date').val('{{ date('Y-m-d') }}');
            $('#expense-image-preview').hide().attr('src', '#');
            clearExpenseErrors();
            expenseModal.show();
        });

        // Open Modal for Edit
        $(document).on('click', '.btn-edit-expense', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const amount = $(this).data('amount');
            const date = $(this).data('date');
            const type = $(this).data('type');
            const categoryId = $(this).data('category');
            const description = $(this).data('description') || '';
            const imageUrl = $(this).data('image');

            $('#expenseModalLabel').text('Edit Expense');
            $('#expense_id').val(id);
            $('#expense_name').val(name);
            
            const amountEl = document.getElementById('expense_amount');
            amountEl.value = amount;
            formatExpenseCurrency(amountEl);

            $('#expense_date').val(date);
            $('#expense_type').val(type);
            $('#expense_category_id').val(categoryId || '');
            $('#expense_description').val(description);
            $('#expense_receipt_image').val('');

            if (imageUrl) {
                $('#expense-image-preview').attr('src', imageUrl).show();
            } else {
                $('#expense-image-preview').hide().attr('src', '#');
            }

            clearExpenseErrors();
            expenseModal.show();
        });

        // Submit Expense Form via AJAX
        $('#expenseForm').on('submit', function(e) {
            e.preventDefault();
            clearExpenseErrors();

            const id = $('#expense_id').val();
            const isEdit = !!id;
            const url = isEdit ? `/expenses/${id}` : `{{ route('expenses.store') }}`;

            const formData = new FormData(this);
            // Replace formatted amount with raw numeric
            const rawAmount = $('#expense_amount').val().replace(/[^0-9]/g, '');
            formData.set('amount', rawAmount);

            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            const $btn = $('#btn-save-expense');
            const originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    expenseModal.hide();
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    const data = response.data;
                    updateTotalsInUi(response.totals);

                    const truncatedDesc = data.description ? (data.description.length > 30 ? data.description.substring(0, 30) + '...' : data.description) : '-';

                    if (isEdit) {
                        const $row = $(`#expense-row-${id}`);
                        $row.removeClass('table-success table-danger table-warning table-info').addClass(data.row_color);
                        $row.find('.expense-name').text(data.name);
                        $row.find('.expense-amount').text(data.formatted_amount);
                        $row.find('.expense-date').text(data.formatted_date);
                        $row.find('.expense-type').html(`<span class="badge bg-${data.type_color} type-badge">${data.type_label}</span>`);
                        $row.find('.expense-category').text(data.category_name);
                        $row.find('.expense-description').text(truncatedDesc);

                        const $editBtn = $row.find('.btn-edit-expense');
                        $editBtn.data('name', data.name);
                        $editBtn.data('amount', data.amount);
                        $editBtn.data('date', data.expense_date);
                        $editBtn.data('type', data.type);
                        $editBtn.data('category', data.category_id);
                        $editBtn.data('description', data.description || '');
                        if (data.receipt_image_url) {
                            $editBtn.data('image', data.receipt_image_url);
                        }
                        $row.find('.btn-delete-expense').data('name', data.name);

                        $row.addClass('table-primary');
                        setTimeout(() => $row.removeClass('table-primary'), 1500);
                    } else {
                        $('#no-expenses-row').remove();
                        const newRowHtml = `
                            <tr id="expense-row-${data.id}" class="${data.row_color} table-success" data-id="${data.id}">
                                <td>${data.id}</td>
                                <td class="expense-name fw-medium">${data.name}</td>
                                <td class="expense-amount fw-bold">${data.formatted_amount}</td>
                                <td class="expense-date">${data.formatted_date}</td>
                                <td class="expense-type">
                                    <span class="badge bg-${data.type_color} type-badge">${data.type_label}</span>
                                </td>
                                <td class="expense-category">${data.category_name}</td>
                                <td class="expense-debt-status">
                                    ${data.type === 'debt' ? `<span class="badge bg-${data.payment_status_color} debt-status-badge">${data.payment_status_label}</span>` : '<span class="badge bg-secondary">-</span>'}
                                </td>
                                <td class="expense-description">${truncatedDesc}</td>
                                <td>
                                    <div class="d-flex align-items-center action-buttons">
                                        <a href="/expenses/${data.id}" class="btn btn-sm btn-info me-1" title="Show">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary me-1 btn-edit-expense"
                                                data-id="${data.id}"
                                                data-name="${data.name}"
                                                data-amount="${data.amount}"
                                                data-date="${data.expense_date}"
                                                data-type="${data.type}"
                                                data-category="${data.category_id}"
                                                data-description="${data.description || ''}"
                                                data-image="${data.receipt_image_url || ''}"
                                                title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-expense"
                                                data-id="${data.id}"
                                                data-name="${data.name}"
                                                title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#expenses-table-body').prepend(newRowHtml);
                        setTimeout(() => $(`#expense-row-${data.id}`).removeClass('table-success'), 1500);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        if (errors) {
                            if (errors.name) {
                                $('#expense_name').addClass('is-invalid');
                                $('#error-expense-name').text(errors.name[0]);
                            }
                            if (errors.amount) {
                                $('#expense_amount').addClass('is-invalid');
                                $('#error-expense-amount').text(errors.amount[0]);
                            }
                            if (errors.expense_date) {
                                $('#expense_date').addClass('is-invalid');
                                $('#error-expense-date').text(errors.expense_date[0]);
                            }
                            if (errors.type) {
                                $('#expense_type').addClass('is-invalid');
                                $('#error-expense-type').text(errors.type[0]);
                            }
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to save expense.',
                        });
                    }
                }
            });
        });

        // Delete Expense via AJAX
        $(document).on('click', '.btn-delete-expense', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Are you sure?',
                text: `Delete expense "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/expenses/${id}`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            updateTotalsInUi(response.totals);
                            const $row = $(`#expense-row-${id}`);
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                if ($('#expenses-table-body tr').length === 0) {
                                    $('#expenses-table-body').html(`
                                        <tr id="no-expenses-row">
                                            <td colspan="9" class="text-center">No expenses found.</td>
                                        </tr>
                                    `);
                                }
                            });

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Failed to delete expense.',
                            });
                        }
                    });
                }
            });
        });

        // Mark Debt as Fully Paid via AJAX
        $(document).on('click', '.btn-confirm-paid', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Konfirmasi Pembayaran Penuh',
                text: `Tandai hutang "${name}" sebagai lunas?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#34c38f',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Ya, Tandai Lunas',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/expenses/${id}/toggle-payment`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'PUT'
                        },
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        success: function(response) {
                            updateTotalsInUi(response.totals);
                            const $row = $(`#expense-row-${id}`);
                            $row.find('.debt-status-badge')
                                .removeClass('bg-danger')
                                .addClass('bg-success')
                                .text('Sudah Dibayar');
                            $row.find('.debt-progress-container').remove();
                            $row.find('.btn-confirm-paid').replaceWith(`
                                <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Pembayaran sudah lunas">
                                    <i class="bx bx-check"></i>
                                </button>
                            `);
                            $row.find('.btn-partial-modal').replaceWith(`
                                <button type="button" class="btn btn-sm btn-secondary me-1" disabled title="Pembayaran sudah lunas">
                                    <i class="bx bx-money"></i>
                                </button>
                            `);

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Gagal mengubah status pembayaran.',
                            });
                        }
                    });
                }
            });
        });

        // Partial Payment Submission via AJAX
        $(document).on('submit', '.partial-payment-form', function(e) {
            e.preventDefault();
            const $form = $(this);
            const id = $form.data('id');
            const $modal = $(`#partialPaymentModal${id}`);
            const $btn = $form.find('.btn-submit-partial');

            $btn.prop('disabled', true).text('Processing...');

            $.ajax({
                url: `/expenses/${id}/partial-payment`,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Make Payment');
                    $modal.modal('hide');
                    updateTotalsInUi(response.totals);

                    const $row = $(`#expense-row-${id}`);
                    $row.find('.debt-progress-bar').css('width', `${response.payment_progress}%`);
                    $row.find('.debt-progress-text').text(`${response.formatted_paid_amount} / ${response.formatted_remaining_amount}`);

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).text('Make Payment');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Gagal memproses pembayaran parsial.',
                    });
                }
            });
        });

        // Generate Monthly Submission via AJAX
        $('#generateMonthlyForm').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btn-submit-generate-monthly');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generating...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $btn.prop('disabled', false).text('Generate');
                    $('#generateMonthlyModal').modal('hide');
                    updateTotalsInUi(response.totals);

                    Swal.fire({
                        icon: 'success',
                        title: 'Generated!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Smoothly refresh current page to populate table with the generated list
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).text('Generate');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to generate monthly expenses.',
                    });
                }
            });
        });
    </script>
@endsection
