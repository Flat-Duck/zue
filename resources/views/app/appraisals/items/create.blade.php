@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">Create Item</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('appraisals.items.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Key (unique)</label>
                            <input name="key" class="form-control" value="{{ old('key') }}"
                                placeholder="attendance, teamwork..." required>
                            <div class="form-hint">يفضل slug بالإنجليزي</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Default Section</label>
                            <select name="default_section" class="form-select" required>
                                @foreach(['job_performance' => 'الأداء', 'personal_traits' => 'الصفات', 'initiative' => 'المبادرة'] as $k => $v)
                                    <option value="{{ $k }}" {{ old('default_section') === $k ? 'selected' : '' }}>{{ $k }} -
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Default Label (Arabic)</label>
                            <input name="default_label" class="form-control" value="{{ old('default_label') }}" required>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">Save</button>
                        <a href="{{ route('appraisals.items.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection