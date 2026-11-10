@extends('layouts.master')
@section('title')
    @lang('translation.User')
@endsection

@section('content')
     @component('common-components.breadcrumb', [
        'title' => 'Create User',
        'pagetitle' => 'User Management',
        'breadcrumbs' => [
            ['text' => 'User Management', 'url' => route('users.index')],
            ['text' => 'Create User', 'url' => '']
        ]
    ])
    @endcomponent
    <div class="row">
        <div class="">
            <div class="card">
                <div class="card-body">
                    <h5 class="font-size-14 mb-4">
                        <i class="mdi mdi-arrow-right text-primary me-1"></i> User Information
                    </h5>

                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="floatingNameInput" name="name" placeholder="Enter Full Name" 
                                   value="{{ old('name') }}" required>
                            <label for="floatingNameInput">Full Name</label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="floatingUsernameInput" name="username" placeholder="Enter Username" 
                                   value="{{ old('username') }}" required>
                            <label for="floatingUsernameInput">Username</label>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="floatingRoleSelect" name="role_id" required>
                                <option value="" selected disabled>Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="floatingRoleSelect">User Role</label>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <button type="submit" class="btn btn-primary waves-effect waves-light w-md">
                                <i class="mdi mdi-content-save me-1"></i> Save
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-danger waves-effect waves-light w-md">
                                <i class="mdi mdi-close me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection