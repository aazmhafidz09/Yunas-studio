@extends('layouts.master-without-nav')

@section('title')
    @lang('translation.Error_403')
@endsection

@section('content')
    <div class="my-5 pt-sm-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center">
                        <div>
                            <div class="row justify-content-center">
                                <div class="col-sm-4">
                                    <div class="error-img">
                                        <img src="{{ asset('assets/images/404-error.png') }}" alt="Access Denied" 
                                             class="img-fluid mx-auto d-block">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h4 class="text-uppercase mt-4">Access Denied</h4>
                        <p class="text-muted">
                            You don't have permission to access this page.
                            @auth
                                <br>Your role: <span class="badge bg-primary">{{ auth()->user()->role->name }}</span>
                            @endauth
                        </p>
                        <div class="mt-4">
                            <a class="btn btn-primary waves-effect waves-light me-2" href="{{ url()->previous() }}">
                                <i class="mdi mdi-arrow-left me-1"></i> Go Back
                            </a>
                            <a class="btn btn-outline-secondary waves-effect" href="{{ url('index') }}">
                                <i class="mdi mdi-home me-1"></i> Back to Dashboard
                            </a>
                        </div>
                        
                        @auth
                            @unless(auth()->user()->isSuperAdmin())
                                <div class="mt-4 alert alert-warning">
                                    <p class="mb-1">This action requires <span class="badge bg-danger">Super Admin</span> privileges.</p>
                                    <p class="mb-0">Please contact your system administrator if you need access.</p>
                                </div>
                            @endunless
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection