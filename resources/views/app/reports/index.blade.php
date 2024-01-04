@extends('layouts.app', ['page' => 'permissions'])
@section('title', 'قائمة التقارير')
@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">
                {{ __('التقارير') }}
            </h2>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body border-bottom py-3">
                    <form method="POST" action="{{ route('admin.reports.minus') }}">
                        @csrf
                        <div class="row mb-3">
                            <label class="form-label">Only Employees With Minus</label>
                            <button type="submit" class="btn btn-primary">Get Run </button>
                        </div>
                    </form>                    
                </div>
            </div>
        </div>
    </div>
@endsection