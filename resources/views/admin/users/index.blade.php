@extends('layouts.master')
@section('title')
    @lang('translation.User')
@endsection

@section('content')
    @component('common-components.breadcrumb', [
        'title' => 'Create User',
        'pagetitle' => 'User Management',
        'breadcrumbs' => [
            ['text' => 'User Management', 'url' => '']
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
                                @can('create', App\Models\User::class)
                                    <a href="{{ route('users.create') }}" class="btn btn-success waves-effect waves-light">
                                        <i class="mdi mdi-plus me-2"></i> Add New User
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-inline float-md-end mb-3">
                                <div class="search-box ms-2">
                                    <form action="{{ route('users.index') }}" method="GET">
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
                                    <th scope="col">Username</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>
                                        @if($user->avatar)
                                            <img src="{{ asset('storage/'.$user->avatar) }}" alt="" class="avatar-xs rounded-circle me-2">
                                        @else
                                            <div class="avatar-xs d-inline-block me-2">
                                                <div class="avatar-title bg-{{ $user->role_id == 1 ? 'primary' : 'secondary' }}-subtle rounded-circle text-{{ $user->role_id == 1 ? 'primary' : 'secondary' }}">
                                                    <i class="mdi mdi-account-circle m-0"></i>
                                                </div>
                                            </div>
                                        @endif
                                        <span class="text-body">{{ $user->name }}</span>
                                    </td>
                                    <td>{{ $user->username }}</td>
                                    <td>
                                        <span class="badge bg-{{ $user->role_id == 2 ? 'primary' : 'success' }}-subtle text-{{ $user->role_id == 2 ? 'primary' : 'success' }} font-size-12">
                                            {{ $user->role->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('users.toggle-status', $user->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="statusToggle{{ $user->id }}" 
                                                    onchange="this.form.submit()" {{ $user->is_active ? 'checked' : '' }}>
                                            </div>
                                        </form>
                                    </td>
                                    <td align='center'>
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="px-2 text-primary">
                                                <i class="uil uil-pen font-size-18"></i>
                                            </a>
                                            <form action="{{ route('reset.password.post', $user->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="submit" class="btn btn-danger" id="statusToggle{{ $user->id }}" value="Reset Password">
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-sm-6">
                            <div>
                                <p class="mb-sm-0">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} entries</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="float-sm-end">
                                {{ $users->links('') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->
@endsection

@section('script')
    <script>
        // Inisialisasi tooltip
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip()
        })
    </script>
@endsection