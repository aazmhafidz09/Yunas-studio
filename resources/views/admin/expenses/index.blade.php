@extends('layouts.master')

@php
    use Illuminate\Support\Str;
@endphp

@section('title')
    Expenses
@endsection

@section('css')
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

@section('script')
<script>
    // Script section available for future use
</script>
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
                    <!-- Tambahkan di bagian atas, misalnya di dekat tombol Add New Expense -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title">Expenses List</h4>
                            <!-- Filter status yang sudah ada -->
                        </div>
                        <div>
                            <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#generateMonthlyModal">
                                <i class="bx bx-refresh me-1"></i> Generate Monthly
                            </button>
                            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Add New Expense
                            </a>
                            <a href="{{ route('expense-categories.index') }}" class="btn btn-secondary me-2">
                                <i class="bx bx-category me-1"></i> Manage Categories
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tambahkan modal di bagian bawah file -->
                    <!-- Generate Monthly Expenses Modal -->
                    <div class="modal fade" id="generateMonthlyModal" tabindex="-1" aria-labelledby="generateMonthlyModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="generateMonthlyModalLabel">Generate Monthly Expenses</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="{{ route('expenses.generate-monthly') }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <p>This will generate default monthly expenses for the selected month and year.</p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="month" class="form-label">Month</label>
                                                    <select class="form-select" id="month" name="month" required>
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
                                                    <label for="year" class="form-label">Year</label>
                                                    <select class="form-select" id="year" name="year" required>
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
                                        <button type="submit" class="btn btn-primary">Generate</button>
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
                                                            <h4 class="mb-0 {{ ($currentBalance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($currentBalance ?? 0, 0, ',', '.') }}</h4>
                                                        </div>
                                                        <div>
                                                            <i class="bx bx-wallet font-size-24 {{ ($currentBalance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}"></i>
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
                                                            <h4 class="mb-0 text-success">Rp {{ number_format($totalIncome ?? 0, 0, ',', '.') }}</h4>
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
                                                            <h4 class="mb-0 text-danger">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
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
                                        <div class="col-md-3">
                                            <label for="month" class="form-label">Month</label>
                                            <select name="month" id="month" class="form-select">
                                                <option value="">All Months</option>
                                                @foreach($months as $key => $monthName)
                                                    <option value="{{ $key }}" {{ request('month') == $key ? 'selected' : '' }}>{{ $monthName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="year" class="form-label">Year</label>
                                            <select name="year" id="year" class="form-select">
                                                <option value="" {{ request('year') === null ? 'selected' : '' }}>All Years</option>
                                                @foreach($years as $yearOption)
                                                    <option value="{{ $yearOption }}" {{ request('year') == $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select name="category_id" id="category_id" class="form-select">
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="type" class="form-label">Type</label>
                                            <select name="type" id="type" class="form-select">
                                                <option value="">All Types</option>
                                                <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Pemasukan</option>
                                                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                                                <option value="debt" {{ request('type') == 'debt' ? 'selected' : '' }}>Hutang</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-filter-alt me-1"></i> Apply Filter
                                            </button>
                                            <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                                                <i class="bx bx-reset me-1"></i> Reset All Filters
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Number</th>
                                    <th>Name</th>
                                    <th>
                                        Amount
                                        <a href="{{ request()->fullUrlWithQuery(['sort_amount' => 'asc']) }}" class="text-decoration-none {{ request('sort_amount') == 'asc' ? 'text-primary' : 'text-muted' }}">
                                            <i class="bx bx-up-arrow-alt"></i>
                                        </a>
                                        <a href="{{ request()->fullUrlWithQuery(['sort_amount' => 'desc']) }}" class="text-decoration-none {{ request('sort_amount') == 'desc' ? 'text-primary' : 'text-muted' }}">
                                            <i class="bx bx-down-arrow-alt"></i>
                                        </a>
                                    </th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $key => $expense)
                                    <tr class="{{ $expense->row_color }}">
                                        <td>{{ $expenses->firstItem() + $key }}</td>
                                        <td>{{ $expense->name }}</td>
                                        <td>{{ $expense->formatted_amount }}</td>
                                        <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $expense->type == 'income' ? 'success' : ($expense->type == 'expense' ? 'danger' : 'warning') }}">{{ $expense->type_label }}</span>
                                        </td>
                                        <td>{{ $expense->category ? $expense->category->name : '-' }}</td>
                                        <td>
                                            @if($expense->type == 'debt')
                                                <div class="mb-1">
                                                    <span class="badge bg-{{ $expense->payment_status_color }}">{{ $expense->payment_status_label }}</span>
                                                </div>
                                                @if($expense->remaining_amount > 0)
                                                    <div class="progress" style="height: 8px; width: 100px;">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                            style="width: {{ $expense->paymentProgress }}%" 
                                                            aria-valuenow="{{ $expense->paymentProgress }}" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">{{ $expense->formattedPaidAmount }} / {{ $expense->formatted_amount }}</small>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($expense->description ?? '-', 30, '...') }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('expenses.show', $expense->id) }}" class="btn btn-sm btn-info me-1">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @if($expense->type == 'debt')
                                                    @if(!$expense->is_paid)
                                                        @if($expense->remaining_amount > 0)
                                                            <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#partialPaymentModal{{ $expense->id }}" title="Partial Payment">
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
                                                    <form action="{{ route('expenses.toggle-payment', $expense->id) }}" method="POST" class="d-inline me-1">
                                                        @csrf
                                                        @method('PUT')
                                                        @if($hasPartialPayments && !$expense->is_paid)
                                                            <button type="button" class="btn btn-sm btn-secondary" disabled title="Tidak dapat digunakan setelah pembayaran parsial">
                                                                <i class="bx bx-check"></i>
                                                            </button>
                                                        @elseif($expense->is_paid)
                                                            <button type="button" class="btn btn-sm btn-secondary" disabled title="Pembayaran sudah lunas">
                                                                <i class="bx bx-check"></i>
                                                            </button>
                                                        @else
                                                            <button type="submit" class="btn btn-sm btn-success" title="Mark as Paid" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal{{ $expense->id }}">
                                                                <i class="bx bx-check"></i>
                                                            </button>
                                                        @endif
                                                    </form>
                                                @endif
                                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');">
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
                                        <td colspan="9" class="text-center">
                                            No expenses found. <a href="{{ route('expenses.create') }}" class="alert-link">Add your first expense</a>.
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

    <!-- Modals -->
    @foreach($expenses as $expense)
        @if($expense->type == 'debt')
            <!-- Confirmation Payment Modal -->
            <div class="modal fade" id="confirmPaymentModal{{ $expense->id }}" tabindex="-1" aria-labelledby="confirmPaymentModalLabel{{ $expense->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmPaymentModalLabel{{ $expense->id }}">Konfirmasi Pembayaran Penuh</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="bx bx-error me-2"></i> Perhatian!
                            </div>
                            <p>Anda akan menandai hutang <strong>{{ $expense->name }}</strong> sebagai lunas.</p>
                            <p>Jika Anda ingin melakukan pembayaran parsial, silakan gunakan tombol <strong>"Partial Payment"</strong> sebagai gantinya.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <form action="{{ route('expenses.toggle-payment', $expense->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">Tandai Sebagai Lunas</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Partial Payment Modal -->
            @if($expense->remaining_amount > 0)
                <div class="modal fade" id="partialPaymentModal{{ $expense->id }}" tabindex="-1" aria-labelledby="partialPaymentModalLabel{{ $expense->id }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="partialPaymentModalLabel{{ $expense->id }}">Partial Payment - {{ $expense->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('expenses.partial-payment', $expense->id) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Debt Information</label>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <p class="mb-1"><strong>Total Amount:</strong> {{ $expense->formatted_amount }}</p>
                                                <p class="mb-1"><strong>Paid Amount:</strong> {{ $expense->formattedPaidAmount }}</p>
                                                <p class="mb-0"><strong>Remaining Amount:</strong> {{ $expense->formattedRemainingAmount }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount{{ $expense->id }}" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="amount{{ $expense->id }}" name="amount" 
                                               min="1" max="{{ $expense->remaining_amount }}" step="0.01" required>
                                        <div class="form-text">Maximum: Rp {{ number_format($expense->remaining_amount, 0, ',', '.') }}</div>
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
                                    <button type="submit" class="btn btn-primary">Make Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endforeach
@endsection