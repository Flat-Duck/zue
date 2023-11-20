@extends('layouts.guest')
@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('Verify Your Email Address') }}</h2>
            
            <form action="{{ route('verification.resend') }}" method="POST" autocomplete="off" novalidate>
                @csrf
                @if (session('resent'))
                    <div class="alert alert-success">
                        {{ __('A fresh verification link has been sent to your email address.') }}
                    </div>
                @endif
                {{ __('Before proceeding, please check your email for a verification link.') }}
                {{ __('If you did not receive the email') }},
                
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">{{ __('click here to request another') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-secondary mt-3">
        Forget it, send me back to the sign in screen. <a href="{{ route('register') }}">Login</a>
    </div>
@endsection         