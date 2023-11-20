@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Create new account') }}</h2>
            <form action="{{ route('register') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label required">{{ __('Name') }}</label>
                    <input type="text" placeholder="Enter Name" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">{{ __('Email Address') }}</label>
                    <input type="email" placeholder="Enter email" id="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required ">{{ __('Password') }}</label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <input type="password" placeholder="Enter password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="password" autofocus>
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                                <i class="ti ti-eye"></i>
                            </a>
                        </span>
                    </div>
                    @error('password')
                        <span class="invalid-feedback">
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
                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
                        <span class="form-check-label">Agree the <a href="./terms-of-service.html" tabindex="-1">terms and policy</a>.</span>
                    </label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Register') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        You have an account already? <a href="{{ route('login') }}" tabindex="-1">Login</a>
    </div>
@endsection