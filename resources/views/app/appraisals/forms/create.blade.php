@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">Create Form</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.forms.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Code</label>
                            <input name="code" class="form-control" value="{{ old('code') }}" placeholder="FORM_3_ADMIN_FIN"
                                required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Name (Arabic)</label>
                            <input name="name_ar" class="form-control" value="{{ old('name_ar') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">Save</button>
                        <a href="{{ route('appraisals.forms.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection