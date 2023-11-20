@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Login') }}</h2>
            <form action="{{ route('login') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label required">{{ __('Email Address') }}</label>
                    <input type="email" placeholder="Enter email" id="email"  class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label required">{{ __('Password') }}
                               <span class="form-label-description">
                                <a href="{{ route('password.request') }}">
                                    {{ __('I forgot password') }}
                                </a>
                            </span>
                    </label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <input type="password" placeholder="Enter password" id="password"  class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="password" autofocus>
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
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
                        <span class="form-check-label">Remember me</span>
                    </label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Login') }}</button>
                </div>
            </form>
        </div>
        <div class="hr-text">or</div>
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <a href="#" class="btn w-100">
                        <i class="ti ti-brand-github"></i>
                        Login with Github
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="btn w-100">
                        <i class="ti ti-brand-twitter"></i>
                        Login with Twitter
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Don't have account yet? <a href="{{ route('register') }}" tabindex="-1">Register</a>
    </div>
@endsection         