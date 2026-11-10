<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{url('index')}}" class="logo logo-dark">
                    <span class="logo-sm">
                        {{-- <img src="{{ URL::asset('/assets/images/logo-sm.png') }}" alt="" height="22"> --}}
                        <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        {{-- <img src="{{ URL::asset('/assets/images/logo-dark.png') }}" alt="" height="20"> --}}
                        <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="12">
                    </span>
                </a>

                <a href="{{url('index')}}" class="logo logo-light">
                    <span class="logo-sm">
                        {{-- <img src="{{ URL::asset('/assets/images/logo-sm.png') }}" alt="" height="22"> --}}
                        <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        {{-- <img src="{{ URL::asset('/assets/images/logo-light.png') }}" alt="" height="20"> --}}
                        <img src="{{ URL::asset('/assets/images/yunas_dark.png') }}" alt="" height="12">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img class="rounded-circle header-profile-user" src="{{ URL::asset('/assets/images/users/user.png') }}"
                        alt="Header Avatar">
                    <span class="d-none d-xl-inline-block ms-1 fw-medium font-size-15">{{ Auth::user()->name }}</span>
                    <i class="uil-angle-down d-none d-xl-inline-block font-size-15"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- Change Password Modal Trigger -->
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <i class="uil uil-lock-alt font-size-18 align-middle me-1 text-muted"></i>
                        <span class="align-middle">Change Password</span>
                    </a>

                    <!-- Logout Link -->
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="uil uil-sign-out-alt font-size-18 align-middle me-1 text-muted"></i>
                        <span class="align-middle">Sign Out</span>
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="uil uil-lock-alt me-2"></i>Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('change.password.post') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                        @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password *</label>
                        <input type="password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                        @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="uil uil-check-circle me-2"></i>Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="uil uil-check-circle text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3">{{ session('success') }}</h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="uil uil-exclamation-triangle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="uil uil-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                <h4 class="mt-3">{{ session('error') }}</h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<style>
    .modal {
        z-index: 99999 !important;
    }

    .modal-backdrop {
        z-index: 99998 !important;
    }

    #changePasswordModal .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    #changePasswordModal .modal-header {
        color: white;
        border-bottom: none;
        border-radius: 12px 12px 0 0;
    }

    #changePasswordModal .modal-title {
        font-weight: 600;
    }

    #changePasswordModal .btn-close {
        filter: invert(1);
        opacity: 0.8;
    }

    #changePasswordModal .btn-close:hover {
        opacity: 1;
    }

    #changePasswordModal .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    #changePasswordModal .form-text {
        font-size: 0.875rem;
        color: #6c757d;
    }

    /* Success Modal Styles */
    #successModal .modal-content {
        border-radius: 15px;
    }

    #successModal .modal-header {
        border-bottom: none;
        border-radius: 15px 15px 0 0;
    }

    /* Error Modal Styles */
    #errorModal .modal-content {
        border-radius: 15px;
    }

    #errorModal .modal-header {
        border-bottom: none;
        border-radius: 15px 15px 0 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto show modals based on session messages
        @if(session('success'))
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        @endif

        @if(session('error'))
        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
        @endif

        // Auto close modals after 3 seconds
        setTimeout(function() {
            var successModalEl = document.getElementById('successModal');
            var errorModalEl = document.getElementById('errorModal');

            if (successModalEl) {
                var successModalInstance = bootstrap.Modal.getInstance(successModalEl);
                if (successModalInstance) {
                    setTimeout(function() {
                        successModalInstance.hide();
                    }, 3000);
                }
            }

            if (errorModalEl) {
                var errorModalInstance = bootstrap.Modal.getInstance(errorModalEl);
                if (errorModalInstance) {
                    setTimeout(function() {
                        errorModalInstance.hide();
                    }, 3000);
                }
            }
        }, 100);

        // Clear form when password modal is hidden
        $('#changePasswordModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            // Clear validation errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        });

        // Auto open change password modal if there are validation errors
        @if($errors->has('current_password') || $errors->has('new_password'))
        var changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        changePasswordModal.show();
        @endif
    });
</script>
