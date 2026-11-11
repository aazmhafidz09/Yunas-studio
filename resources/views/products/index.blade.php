@extends('layouts.master')

@section('title')
    Products
@endsection

@section('css')
    <link href="{{ URL::asset('/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('common-components.breadcrumb')
        @slot('pagetitle') Products & Packets @endslot
        @slot('title') Products List @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="card-title">Products List</h4>
                        <div>
                            <button type="button" class="btn btn-primary me-2" id="btn-add-product">
                                <i class="bx bx-plus me-1"></i> Add New Product
                            </button>
                            <a href="{{ route('packets.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to Packets
                            </a>
                        </div>
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
                        <table class="table table-striped table-hover align-middle" id="products-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="products-table-body">
                                @forelse($products as $product)
                                    <tr id="product-row-{{ $product->id }}" data-id="{{ $product->id }}">
                                        <td class="product-id">{{ $product->id }}</td>
                                        <td class="product-name fw-medium">{{ $product->name }}</td>
                                        <td class="product-description">{{ \Illuminate\Support\Str::limit($product->description, 50) }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('packets.product', $product->id) }}" class="btn btn-sm btn-info me-1" title="View Packets">
                                                    <i class="bx bx-package"></i>
                                                </a>
                                                <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info me-1" title="Show Details">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary me-1 btn-edit-product"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        data-description="{{ $product->description }}"
                                                        data-image="{{ $product->image ? Storage::url($product->image) : '' }}"
                                                        title="Edit Product">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger btn-delete-product"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        title="Delete Product">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="no-products-row">
                                        <td colspan="4" class="text-center">
                                            No products found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal (Create & Edit) -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="productForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="product_id" name="id">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="product_name" name="name" required placeholder="Enter Product Name">
                                    <div class="invalid-feedback" id="error-name"></div>
                                </div>

                                <div class="mb-3">
                                    <label for="product_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="product_description" name="description" rows="4" placeholder="Enter Product Description"></textarea>
                                    <div class="invalid-feedback" id="error-description"></div>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="product_image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="product_image" name="image" accept="image/*" onchange="previewProductImage(this)">
                                    <small class="form-text text-muted">Upload an image (max 15MB). Formats: JPEG, PNG, JPG, GIF.</small>
                                    <div class="invalid-feedback" id="error-image"></div>
                                </div>
                                <div class="mb-3 text-center">
                                    <img id="product-image-preview" src="#" alt="Preview" class="img-fluid img-thumbnail rounded" style="max-height: 180px; display: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-product">
                            <i class="mdi mdi-content-save me-1"></i> Save Product
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
        const productModalEl = document.getElementById('productModal');
        const productModal = new bootstrap.Modal(productModalEl);
        const productForm = document.getElementById('productForm');

        function previewProductImage(input) {
            const preview = document.getElementById('product-image-preview');
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

        function clearProductErrors() {
            $('#product_name').removeClass('is-invalid');
            $('#product_description').removeClass('is-invalid');
            $('#product_image').removeClass('is-invalid');
            $('#error-name').text('');
            $('#error-description').text('');
            $('#error-image').text('');
        }

        // Open Modal for Add
        $('#btn-add-product').on('click', function() {
            $('#productModalLabel').text('Add New Product');
            $('#product_id').val('');
            productForm.reset();
            $('#product-image-preview').hide().attr('src', '#');
            clearProductErrors();
            productModal.show();
        });

        // Open Modal for Edit
        $(document).on('click', '.btn-edit-product', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const description = $(this).data('description') || '';
            const imageUrl = $(this).data('image');

            $('#productModalLabel').text('Edit Product');
            $('#product_id').val(id);
            $('#product_name').val(name);
            $('#product_description').val(description);
            $('#product_image').val('');

            if (imageUrl) {
                $('#product-image-preview').attr('src', imageUrl).show();
            } else {
                $('#product-image-preview').hide().attr('src', '#');
            }

            clearProductErrors();
            productModal.show();
        });

        // Submit Form via AJAX (multipart/form-data)
        $('#productForm').on('submit', function(e) {
            e.preventDefault();
            clearProductErrors();

            const id = $('#product_id').val();
            const isEdit = !!id;
            const url = isEdit ? `/products/${id}` : `{{ route('products.store') }}`;

            const formData = new FormData(this);
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            const $btn = $('#btn-save-product');
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
                    productModal.hide();
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    const data = response.data;
                    const truncatedDesc = data.description ? (data.description.length > 50 ? data.description.substring(0, 50) + '...' : data.description) : '';

                    if (isEdit) {
                        const $row = $(`#product-row-${id}`);
                        $row.find('.product-name').text(data.name);
                        $row.find('.product-description').text(truncatedDesc);
                        
                        const $editBtn = $row.find('.btn-edit-product');
                        $editBtn.data('name', data.name);
                        $editBtn.data('description', data.description || '');
                        if (data.image_url) {
                            $editBtn.data('image', data.image_url);
                        }
                        $row.find('.btn-delete-product').data('name', data.name);

                        $row.addClass('table-success');
                        setTimeout(() => $row.removeClass('table-success'), 1500);
                    } else {
                        $('#no-products-row').remove();
                        const newRowHtml = `
                            <tr id="product-row-${data.id}" data-id="${data.id}" class="table-success">
                                <td class="product-id">${data.id}</td>
                                <td class="product-name fw-medium">${data.name}</td>
                                <td class="product-description">${truncatedDesc}</td>
                                <td>
                                    <div class="d-flex">
                                        <a href="/packets/product/${data.id}" class="btn btn-sm btn-info me-1" title="View Packets">
                                            <i class="bx bx-package"></i>
                                        </a>
                                        <a href="/products/${data.id}" class="btn btn-sm btn-info me-1" title="Show Details">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary me-1 btn-edit-product"
                                                data-id="${data.id}"
                                                data-name="${data.name}"
                                                data-description="${data.description || ''}"
                                                data-image="${data.image_url || ''}"
                                                title="Edit Product">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-product"
                                                data-id="${data.id}"
                                                data-name="${data.name}"
                                                title="Delete Product">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#products-table-body').prepend(newRowHtml);
                        setTimeout(() => $(`#product-row-${data.id}`).removeClass('table-success'), 1500);
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
                                $('#product_name').addClass('is-invalid');
                                $('#error-name').text(errors.name[0]);
                            }
                            if (errors.description) {
                                $('#product_description').addClass('is-invalid');
                                $('#error-description').text(errors.description[0]);
                            }
                            if (errors.image) {
                                $('#product_image').addClass('is-invalid');
                                $('#error-image').text(errors.image[0]);
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'Validation failed.',
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to save product.',
                        });
                    }
                }
            });
        });

        // Delete Product via AJAX
        $(document).on('click', '.btn-delete-product', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Are you sure?',
                text: `Delete product "${name}"? This will also delete all associated packets.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f46a6a',
                cancelButtonColor: '#74788d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/products/${id}`,
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
                            const $row = $(`#product-row-${id}`);
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                if ($('#products-table-body tr').length === 0) {
                                    $('#products-table-body').html(`
                                        <tr id="no-products-row">
                                            <td colspan="4" class="text-center">No products found.</td>
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
                                text: xhr.responseJSON?.message || 'Failed to delete product.',
                            });
                        }
                    });
                }
            });
        });
    </script>
@endsection
