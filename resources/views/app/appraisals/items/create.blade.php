@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">@lang('appraisals.create_item')</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.items.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.key_unique')</label>
                            <input name="key" class="form-control" value="{{ old('key') }}"
                                placeholder="@lang('appraisals.attendance_teamwork')" required>
                            <div class="form-hint">@lang('appraisals.prefer_english_slug')</div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">@lang('appraisals.type')</label>
                            <select name="type" class="form-select" required>
                                <option value="score" {{ old('type') === 'score' ? 'selected' : '' }}>@lang('appraisals.score_numeric')</option>
                                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>@lang('appraisals.text_comment')</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">@lang('appraisals.default_section')</label>
                            <select name="default_section" class="form-select" required>
                                @foreach(['job_performance' => 'الأداء', 'personal_traits' => 'الصفات', 'initiative' => 'المبادرة'] as $k => $v)
                                    <option value="{{ $k }}" {{ old('default_section') === $k ? 'selected' : '' }}>{{ $k }} -
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">@lang('appraisals.default_label_arabic')</label>
                            <input name="default_label" class="form-control" value="{{ old('default_label') }}" required>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">@lang('appraisals.save')</button>
                        <a href="{{ route('appraisals.items.index') }}" class="btn btn-outline-secondary">@lang('appraisals.back')</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection