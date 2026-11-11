@extends('layouts.master')
@section('title')
    @lang('translation.Additional')
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
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
                                    <button type="button" class="btn btn-success waves-effect waves-light" id="btn-add-additional">
                                        <i class="mdi mdi-plus me-2"></i> Add New Additional
                                    </button>
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
                        <table class="table table-centered table-nowrap mb-0" id="additionals-table">
                            <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Price (IDR)</th>
                                <th scope="col" style="width: 150px;">Action</th>
                            </tr>
                            </thead>
                            <tbody id="additionals-table-body">
                            @if($additionals->isEmpty())
                                <tr id="no-data-row">
                                    <td colspan="3" class="text-center">
                                        No data found
                                    </td>
                                </tr>
                            @else
                                @foreach($additionals as $additional)
                                    <tr id="additional-row-{{ $additional->id }}" data-id="{{ $additional->id }}" data-name="{{ $additional->name }}" data-price="{{ $additional->price }}">
                                        <td class="additional-name">
                                            <span class="text-body">{{ $additional->name }}</span>
                                        </td>
                                        <td class="additional-price">Rp {{ number_format($additional->price, 0, ',', '.') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @can('update', $additional)
                                                    <button type="button" class="btn btn-link px-2 text-primary btn-edit-additional" data-id="{{ $additional->id }}" data-name="{{ $additional->name }}" data-price="{{ $additional->price }}" data-bs-toggle="tooltip" title="Edit">
                                                        <i class="uil uil-pen font-size-18"></i>
                                                    </button>
                                                @endcan
                                                @can('delete', $additional)
                                                    <button type="button" class="btn btn-link px-2 text-danger btn-delete-additional" data-id="{{ $additional->id }}" data-name="{{ $additional->name }}" data-bs-toggle="tooltip" title="Delete">
                                                        <i class="uil uil-trash font-size-18"></i>
                                                    </button>
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
                                    <p class="mb-sm-0" id="pagination-info">Showing {{ $additionals->firstItem() }} to {{ $additionals->lastItem() }} of {{ $additionals->total() }} entries</p>
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

    <!-- Additional Modal (Create & Edit) -->
    <div class="modal fade" id="additionalModal" tabindex="-1" aria-labelledby="additionalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="additionalModalLabel">Add New Additional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="additionalForm">
                    @csrf
                    <input type="hidden" id="additional_id" name="id">
                    <input type="hidden" id="additional_method" name="_method" value="POST">

                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingNameInput" name="name" placeholder="Enter Additional Name" required>
                            <label for="floatingNameInput">Additional Name</label>
                            <div class="invalid-feedback" id="error-name"></div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="floatingPriceInput" name="price" placeholder="Enter Price (IDR)" required oninput="formatCurrency(this)">
                            <label for="floatingPriceInput">Price (IDR)</label>
                            <div class="invalid-feedback" id="error-price"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-additional">
                            <i class="mdi mdi-content-save me-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        // Initialize tooltip helper
        function initTooltips() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        $(function () {
            initTooltips();
        });

        // Format price input with IDR formatting
        function formatCurrency(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if(value.length > 0) {
                value = parseInt(value, 10).toLocaleString('id-ID');
            }
            input.value = value;
        }

        function formatIDR(number) {
            return 'Rp ' + parseInt(number, 10).toLocaleString('id-ID');
        }

        const additionalModalEl = document.getElementById('additionalModal');
        const additionalModal = new bootstrap.Modal(additionalModalEl);
        const form = document.getElementById('additionalForm');

        function clearFormErrors() {
            $('#floatingNameInput').removeClass('is-invalid');
            $('#floatingPriceInput').removeClass('is-invalid');
            $('#error-name').text('');
            $('#error-price').text('');
        }

        // Open Modal for Add
        $('#btn-add-additional').on('click', function() {
            $('#additionalModalLabel').text('Add New Additional');
            $('#additional_id').val('');
            $('#additional_method').val('POST');
            form.reset();
            clearFormErrors();
            additionalModal.show();
        });

        // Open Modal for Edit
        $(document).on('click', '.btn-edit-additional', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const price = $(this).data('price');

            $('#additionalModalLabel').text('Edit Additional');
            $('#additional_id').val(id);
            $('#additional_method').val('PUT');

            $('#floatingNameInput').val(name);
            const priceEl = document.getElementById('floatingPriceInput');
            priceEl.value = price;
            formatCurrency(priceEl);

            clearFormErrors();
            additionalModal.show();
        });

        // Submit Form (Create or Update) via AJAX
        $('#additionalForm').on('submit', function(e) {
            e.preventDefault();
            clearFormErrors();

            const id = $('#additional_id').val();
            const isEdit = !!id;
            const url = isEdit ? `/additionals/${id}` : `{{ route('additionals.store') }}`;

            const rawPrice = $('#floatingPriceInput').val().replace(/[^0-9]/g, '');
            const postData = {
                _token: '{{ csrf_token() }}',
                name: $('#floatingNameInput').val(),
                price: rawPrice
            };
            if (isEdit) {
                postData._method = 'PUT';
            }

            const $btn = $('#btn-save-additional');
            const originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.ajax({
                url: url,
                type: 'POST',
                data: postData,
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    additionalModal.hide();
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    if (isEdit) {
                        // Update existing row
                        const $row = $(`#additional-row-${id}`);
                        $row.data('name', response.data.name);
                        $row.data('price', response.data.price);
                        $row.find('.additional-name span').text(response.data.name);
                        $row.find('.additional-price').text(formatIDR(response.data.price));
                        $row.find('.btn-edit-additional').data('name', response.data.name).data('price', response.data.price);
                        $row.find('.btn-delete-additional').data('name', response.data.name);
                        
                        // Flash background effect
                        $row.addClass('table-success');
                        setTimeout(() => $row.removeClass('table-success'), 1500);
                    } else {
                        // Add new row to top of table
                        $('#no-data-row').remove();
                        const newId = response.data.id;
                        const newRowHtml = `
                            <tr id="additional-row-${newId}" data-id="${newId}" data-name="${response.data.name}" data-price="${response.data.price}" class="table-success">
                                <td class="additional-name">
                                    <span class="text-body">${response.data.name}</span>
                                </td>
                                <td class="additional-price">${formatIDR(response.data.price)}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-link px-2 text-primary btn-edit-additional" data-id="${newId}" data-name="${response.data.name}" data-price="${response.data.price}" data-bs-toggle="tooltip" title="Edit">
                                            <i class="uil uil-pen font-size-18"></i>
                                        </button>
                                        <button type="button" class="btn btn-link px-2 text-danger btn-delete-additional" data-id="${newId}" data-name="${response.data.name}" data-bs-toggle="tooltip" title="Delete">
                                            <i class="uil uil-trash font-size-18"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#additionals-table-body').prepend(newRowHtml);
                        setTimeout(() => $(`#additional-row-${newId}`).removeClass('table-success'), 1500);
                        initTooltips();
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
                        if (errors.name) {
                            $('#floatingNameInput').addClass('is-invalid');
                            $('#error-name').text(errors.name[0]);
                        }
                        if (errors.price) {
                            $('#floatingPriceInput').addClass('is-invalid');
                            $('#error-price').text(errors.price[0]);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to save additional.',
                        });
                    }
                }
            });
        });

        // Delete Additional via AJAX
        $(document).on('click', '.btn-delete-additional', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Are you sure?',
                text: `Delete additional "${name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/additionals/${id}`,
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
                            const $row = $(`#additional-row-${id}`);
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                if ($('#additionals-table-body tr').length === 0) {
                                    $('#additionals-table-body').html(`
                                        <tr id="no-data-row">
                                            <td colspan="3" class="text-center">No data found</td>
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
                                text: xhr.responseJSON?.message || 'Failed to delete additional.',
                            });
                        }
                    });
                }
            });
        });
    </script>
@endsection
