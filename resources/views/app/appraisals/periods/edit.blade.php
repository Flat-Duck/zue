@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <form
                    action="{{ isset($period) ? route('appraisals.periods.update', $period) : route('appraisals.periods.store') }}"
                    method="POST" class="card">
                    @csrf
                    @if(isset($period))
                        @method('PUT')
                    @endif

                    <div class="card-header">
                        <h4 class="card-title">{{ isset($period) ? 'Edit Period' : 'Create New Period' }}</h4>
                    </div>

                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">@lang('appraisals.year')</label>
                                    <input type="number" name="year" class="form-control"
                                        value="{{ old('year', $period->year ?? date('Y')) }}" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">@lang('appraisals.type')</label>
                                    <select name="type" class="form-select" id="typeSelect" required>
                                        <option value="quarter" {{ old('type', $period->type ?? '') == 'quarter' ? 'selected' : '' }}>@lang('appraisals.quarterly')</option>
                                        <option value="yearly" {{ old('type', $period->type ?? '') == 'yearly' ? 'selected' : '' }}>@lang('appraisals.yearly')</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="quarterField">
                                <div class="mb-3">
                                    <label class="form-label">@lang('appraisals.quarter')</label>
                                    <select name="quarter" class="form-select">
                                        <option value="">@lang('appraisals.select_quarter')</option>
                                        <option value="1" {{ old('quarter', $period->quarter ?? '') == '1' ? 'selected' : '' }}>Q1</option>
                                        <option value="2" {{ old('quarter', $period->quarter ?? '') == '2' ? 'selected' : '' }}>Q2</option>
                                        <option value="3" {{ old('quarter', $period->quarter ?? '') == '3' ? 'selected' : '' }}>Q3</option>
                                        <option value="4" {{ old('quarter', $period->quarter ?? '') == '4' ? 'selected' : '' }}>Q4</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('appraisals.window_open_from')</label>
                                    <input type="date" name="window_open_from" class="form-control"
                                        value="{{ old('window_open_from', isset($period) ? $period->window_open_from->format('Y-m-d') : '') }}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">@lang('appraisals.window_open_to')</label>
                                    <input type="date" name="window_open_to" class="form-control"
                                        value="{{ old('window_open_to', isset($period) ? $period->window_open_to->format('Y-m-d') : '') }}"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">@lang('appraisals.status')</label>
                            <select name="status" class="form-select" required>
                                <option value="planned" {{ old('status', $period->status ?? '') == 'planned' ? 'selected' : '' }}>@lang('appraisals.planned')</option>
                                <option value="open" {{ old('status', $period->status ?? '') == 'open' ? 'selected' : '' }}> @lang('appraisals.open')</option>
                                <option value="closed" {{ old('status', $period->status ?? '') == 'closed' ? 'selected' : '' }}>@lang('appraisals.closed')</option>
                                <option value="locked" {{ old('status', $period->status ?? '') == 'locked' ? 'selected' : '' }}>@lang('appraisals.locked')</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-footer text-end">
                        <a href="{{ route('appraisals.periods.index') }}" class="btn btn-link">@lang('appraisals.cancel')</a>
                        <button type="submit" class="btn btn-primary">@lang('appraisals.save_period')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @vite('resources/js/appraisal-period-form.js')
@endsection