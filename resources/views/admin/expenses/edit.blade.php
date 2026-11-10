@extends('layouts.master')

@section('title')
    Edit Expense
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Finance @endslot
        @slot('title') Edit Expense @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Edit Expense</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="number" class="form-label">Number</label>
                                    <input type="text" class="form-control" id="number" value="{{ $expense->number }}" readonly disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Expense Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $expense->name) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount (Rp)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" value="{{ old('amount', $expense->amount) }}" min="0" step="0.01" required>
                                </div>

                                <div class="mb-3">
                                    <label for="expense_date" class="form-label">Expense Date</label>
                                    <input type="date" class="form-control" id="expense_date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="expense" {{ old('type', $expense->type) == 'expense' ? 'selected' : '' }}>
                                            <span class="text-danger">Pengeluaran</span>
                                        </option>
                                        <option value="income" {{ old('type', $expense->type) == 'income' ? 'selected' : '' }}>
                                            <span class="text-success">Pemasukan</span>
                                        </option>
                                        <option value="debt" {{ old('type', $expense->type) == 'debt' ? 'selected' : '' }}>
                                            <span class="text-warning">Hutang</span>
                                        </option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <span class="text-danger">Pengeluaran:</span> Uang keluar dari kas<br>
                                        <span class="text-success">Pemasukan:</span> Uang masuk ke kas<br>
                                        <span class="text-warning">Hutang:</span> Kewajiban yang harus dibayar
                                    </small>
                                    @error('type')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Ganti input text category dengan dropdown -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category</label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $expense->description) }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="receipt_image" class="form-label">Receipt Image</label>
                                    <input type="file" class="form-control" id="receipt_image" name="receipt_image" accept="image/*" onchange="previewImage(this)">
                                    <small class="form-text text-muted">Upload a receipt image (max 15MB). Supported formats: JPEG, PNG, JPG.</small>
                                </div>
                                <div class="mb-3">
                                    <div class="mt-3 text-center">
                                        @if($expense->receipt_image)
                                            <img id="image-preview" src="{{ asset('storage/' . $expense->receipt_image) }}" alt="Receipt Preview" class="img-fluid img-thumbnail rounded" style="max-height: 200px;">
                                        @else
                                            <img id="image-preview" src="#" alt="Receipt Preview" class="img-fluid img-thumbnail rounded" style="max-height: 200px; display: none;">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('expenses.index', request()->query()) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function previewImage(input) {
            var preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection