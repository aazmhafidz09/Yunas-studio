@extends('layouts.master')
@section('title')
    @lang('translation.Additional')
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Edit Additional',
        'pagetitle' => 'Additional Management',
        'breadcrumbs' => [
            ['text' => 'Additional Management', 'url' => route('additionals.index')],
            ['text' => 'Edit Additional', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="font-size-14 mb-4">
                        <i class="mdi mdi-arrow-right text-primary me-1"></i> Additional Information
                    </h5>

                    <form method="POST" action="{{ route('additionals.update', $additional->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="floatingNameInput" name="name" placeholder="Enter Additional Name"
                                   value="{{ old('name', $additional->name) }}" required>
                            <label for="floatingNameInput">Additional Name</label>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('price') is-invalid @enderror"
                                   id="floatingPriceInput" name="price" placeholder="Enter Price (IDR)"
                                   value="{{ old('price', number_format($additional->price, 0, ',', '.')) }}" required
                                   oninput="formatCurrency(this)">
                            <label for="floatingPriceInput">Price (IDR)</label>
                            @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md">
                                <i class="mdi mdi-content-save me-1"></i> Save
                            </button>
                            <a href="{{ route('additionals.index') }}" class="btn btn-outline-danger waves-effect waves-light w-md">
                                <i class="mdi mdi-close me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function formatCurrency(input) {
            // Remove non-numeric characters
            let value = input.value.replace(/[^0-9]/g, '');

            // Format with thousand separators
            if(value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }

            input.value = value;
        }

        // Initialize form validation
        $(document).ready(function() {
            $('form').on('submit', function() {
                // Remove formatting before submission
                const priceInput = document.getElementById('floatingPriceInput');
                if(priceInput) {
                    priceInput.value = priceInput.value.replace(/[^0-9]/g, '');
                }
                return true;
            });
        });
    </script>
@endsection
