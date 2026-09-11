@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Reset Password') }}</h2>
            <p class="text-secondary mb-4">@lang('auth.enter_your_email_address_and_your_password')</p>
            <form action="{{ route('password.email') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label required">{{ __('Email Address') }}</label>
                    <input type="email" placeholder="@lang('auth.enter_email')" id="email"  class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Send Password Reset Link') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3"> @lang('auth.forget_it_send_me_back_to_the_sign_in_screen') <a href="{{ route('login') }}">@lang('auth.login')</a>
    </div>
@endsection         
