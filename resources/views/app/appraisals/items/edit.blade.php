@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">@lang('appraisals.edit_item')</h2>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.items.update', $item->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.key')</label>
                            <input name="key" class="form-control" value="{{ old('key', $item->key) }}" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">@lang('appraisals.type')</label>
                            <select name="type" class="form-select" required>
                                <option value="score" {{ old('type', $item->type) === 'score' ? 'selected' : '' }}>@lang('appraisals.score_numeric')</option>
                                <option value="text" {{ old('type', $item->type) === 'text' ? 'selected' : '' }}>@lang('appraisals.text_comment')</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.default_section')</label>
                            <select name="default_section" class="form-select" required>
                                @foreach(['job_performance', 'personal_traits', 'initiative'] as $k)
                                    <option value="{{ $k }}" {{ old('default_section', $item->default_section) === $k ? 'selected' : '' }}>{{ $k }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">@lang('appraisals.default_label')</label>
                            <input name="default_label" class="form-control"
                                value="{{ old('default_label', $item->default_label) }}" required>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">@lang('appraisals.update')</button>
                        <a href="{{ route('appraisals.items.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection