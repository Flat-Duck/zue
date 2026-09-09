@extends('layouts.app', ['page' => 'employees'])
@section('content')
    <div class="row row-cards">
        @forelse($employees as $employee)
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <span class="avatar avatar-xl mb-3 rounded">SA</span>
                    <h3 class="m-0 mb-1"><a href="#">{{ $employee->english_name ?? '-' }}</a></h3>
                    <div class="text-secondary">Nuclear Power Engineer</div>
                    <div class="text-secondary">{{ $employee->number ?? '-' }}</div>
                    <div class="mt-3">
                    </div>
                </div>
                <div class="d-flex">
                    <a href="#" class="card-btn"><!-- Download SVG icon from http://tabler-icons.io/i/mail -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon me-2 text-muted">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z">
                            </path>
                            <path d="M3 7l9 6l9 -6"></path>
                        </svg>
                        Email</a>
                    <a href="#" class="card-btn"><!-- Download SVG icon from http://tabler-icons.io/i/phone -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon me-2 text-muted">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path
                                d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2">
                            </path>
                        </svg>
                        Call</a>
                </div>
            </div>
        </div>
        @empty
        @lang('crud.common.no_items_found')
        
    @endforelse

    <div class="card-footer d-flex align-items-left">
        {!! $employees->render() !!}
    </div>
    </div>
@endsection
