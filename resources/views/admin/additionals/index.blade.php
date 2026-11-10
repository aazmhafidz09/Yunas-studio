@extends('layouts.master')
@section('title')
    @lang('translation.Additional')
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Additional Management',
        'pagetitle' => 'Additional Management',
        'breadcrumbs' => [
            ['text' => 'Additional Management', 'url' => '']
        ]
    ])
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="mb-3">
                                @can('create', App\Models\Additional::class)
                                    <a href="{{ route('additionals.create') }}" class="btn btn-success waves-effect waves-light">
                                        <i class="mdi mdi-plus me-2"></i> Add New Additional
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-inline float-md-end mb-3">
                                <div class="search-box ms-2">
                                    <form action="{{ route('additionals.index') }}" method="GET">
                                        <div class="position-relative">
                                            <input type="text" name="search" class="form-control rounded bg-light border-0"
                                                   placeholder="Search..." value="{{ request('search') }}">
                                            <i class="mdi mdi-magnify search-icon"></i>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->

                    <div class="table-responsive mb-4">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Price (IDR)</th>
                                <th scope="col" style="width: 150px;">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($additionals->isEmpty())
                                <tr>
                                    <td colspan="3" class="text-center">
                                        No data found
                                    </td>
                                </tr>
                            @else
                                @foreach($additionals as $additional)
                                    <tr>
                                        <td>
                                            <span class="text-body">{{ $additional->name }}</span>
                                        </td>
                                        <td>Rp {{ number_format($additional->price, 0, ',', '.') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @can('update', $additional)
                                                    <a href="{{ route('additionals.edit', $additional) }}" class="px-2 text-primary" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="uil uil-pen font-size-18"></i>
                                                    </a>
                                                @endcan
                                                @can('delete', $additional)
                                                    <form action="{{ route('additionals.destroy', $additional) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link px-2 text-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Are you sure you want to delete this additional?')">
                                                            <i class="uil uil-trash font-size-18"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        @if($additionals->total() > 0)
                            <div class="col-sm-6">
                                <div>
                                    <p class="mb-sm-0">Showing {{ $additionals->firstItem() }} to {{ $additionals->lastItem() }} of {{ $additionals->total() }} entries</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="float-sm-end">
                                    {{ $additionals->withQueryString()->links() }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script>
        // Initialize tooltip
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip()
        })

        // Format price input with IDR formatting
        function formatCurrency(input) {
            // Remove non-numeric characters
            let value = input.value.replace(/[^0-9]/g, '');

            // Format with thousand separators
            if(value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }

            input.value = value;
        }
    </script>
@endsection
