@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Confirm Password') }}</h2>
            <p class="text-secondary mb-4">{{ __('Please confirm your password before continuing.') }}</p>
            <form action="{{ route('password.confirm') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <# <div class="mb-4">
                    {{-- <span class="avatar avatar-xl mb-3" style="background-image: url(./static/avatars/000m.jpg)"></span>
                    <h3>Paweł Kuna</h3> --}}
                </div> #>
                <div class="mb-3">
                    <label class="form-label required">{{ __('Password') }}
                        @if (Route::has('password.request'))
                            <span class="form-label-description">
                                <a href="{{ route('password.request') }}">
                                    {{ __('I forgot password') }}
                                </a>
                            </span>
                        @endif
                    </label>
                    <div class="input-group input-group-flat @error('password') is-invalid @enderror">
                        <input type="password" placeholder="Enter password" id="password"  class="form-control @error('password') is-invalid @enderror" name="password" value="{{ old('password') }}" required autocomplete="current-password" autofocus>
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
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Confirm Password') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Forget it, send me back to the sign in screen. <a href="{{ route('register') }}">Login</a>
    </div>
@endsection