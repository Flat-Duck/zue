@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Reset Password') }}</h2>
            <form action="{{ route('password.update') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                @if (session('status'))
                    <div class="alert alert-success" >
                        {{ session('status') }}
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label required">{{ __('Email Address') }}</label>
                    <input type="email" placeholder="Enter email" id="email"  class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" >
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">{{ __('Password') }}
                    </label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <input type="password" placeholder="Enter password" id="password"  class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="new-password" autofocus>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                                <i class="ti ti-eye"></i>
                            </a>
                        </span>
                    </div>
                    @error('password')
                        <span class="invalid-feedback" >
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">{{ __('Confirm Password') }}
                    </label>
                    <div class="input-group input-group-flat">
                        <input type="password" placeholder="Confirm password" id="password-confirm" class="form-control" name="password_confirmation" value="{{ old('password-confirm') }}" required autocomplete="new-password" autofocus>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                                <i class="ti ti-eye"></i>
                            </a>
                        </span>
                    </div>
                    @error('password_confirmation')
                        <span class="invalid-feedback" >
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Reset Password') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Don't have account yet? <a href="{{ route('register') }}" tabindex="-1">Register</a>
    </div>
@endsection         