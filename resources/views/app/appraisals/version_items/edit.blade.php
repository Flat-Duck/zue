@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Version Items</h2>
                    <div class="text-muted">
                        {{ $version->form->name_ar }} ({{ $version->form->code }}) — v{{ $version->version }}
                        @if($version->is_active) <span class="badge bg-green ms-2">ACTIVE</span> @endif
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a class="btn btn-outline-secondary"
                        href="{{ route('appraisals.versions.index', $version->form->id) }}">Back to Versions</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Add item to this version</h3>
            </div>
            <div class="card-body">
                <form class="row g-3" method="POST"
                    action="{{ route('appraisals.version-items.add', $version->id) }}">
                    @csrf

                    <div class="col-md-5">
                        <label class="form-label">Item</label>
                        <select name="item_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->default_section }} | {{ $i->key }} | {{ $i->default_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Max score</label>
                        <input type="number" name="max_score_override" class="form-control"
                            value="{{ old('max_score_override', 0) }}" min="0" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Sort</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 10) }}"
                            min="1" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Override Section (optional)</label>
                        <select name="section_override" class="form-select">
                            <option value="">-- default --</option>
                            @foreach(['job_performance', 'personal_traits', 'initiative'] as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Override Label (optional)</label>
                        <input name="label_override" class="form-control" value="{{ old('label_override') }}">
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_required" value="1" checked>
                            <span class="form-check-label">Required</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                            <span class="form-check-label">Active</span>
                        </label>
                        <button class="btn btn-primary ms-auto">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items in this version</h3>
            </div>

            <form method="POST" action="{{ route('appraisals.version-items.update', $version->id) }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width:140px">Section</th>
                                <th style="width:220px">Label override</th>
                                <th class="text-center" style="width:120px">Max</th>
                                <th class="text-center" style="width:90px">Sort</th>
                                <th class="text-center" style="width:90px">Req</th>
                                <th class="text-center" style="width:90px">Active</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versionItems as $vi)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $vi->item->key }}</div>
                                        <div class="text-muted">{{ $vi->item->default_label }}</div>
                                    </td>

                                    <td>
                                        <input type="hidden" name="rows[{{ $loop->index }}][id]" value="{{ $vi->id }}">
                                        <select class="form-select" name="rows[{{ $loop->index }}][section_override]">
                                            <option value="">default</option>
                                            @foreach(['job_performance', 'personal_traits', 'initiative'] as $s)
                                                <option value="{{ $s }}" {{ $vi->section_override === $s ? 'selected' : '' }}>{{ $s }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    <td>
                                        <input class="form-control" name="rows[{{ $loop->index }}][label_override]"
                                            value="{{ $vi->label_override }}">
                                    </td>

                                    <td class="text-center">
                                        <input type="number" class="form-control text-center"
                                            name="rows[{{ $loop->index }}][max_score_override]"
                                            value="{{ $vi->max_score_override ?? 0 }}" min="0" required>
                                    </td>

                                    <td class="text-center">
                                        <input type="number" class="form-control text-center"
                                            name="rows[{{ $loop->index }}][sort_order]" value="{{ $vi->sort_order }}" min="1"
                                            required>
                                    </td>

                                    <td class="text-center">
                                        <input type="hidden" name="rows[{{ $loop->index }}][is_required]" value="0">
                                        <input type="checkbox" class="form-check-input"
                                            name="rows[{{ $loop->index }}][is_required]" value="1" {{ $vi->is_required ? 'checked' : '' }}>
                                    </td>

                                    <td class="text-center">
                                        <input type="hidden" name="rows[{{ $loop->index }}][is_active]" value="0">
                                        <input type="checkbox" class="form-check-input"
                                            name="rows[{{ $loop->index }}][is_active]" value="1" {{ $vi->is_active ? 'checked' : '' }}>
                                    </td>

                                    <td>
                                        <form method="POST"
                                            action="{{ route('appraisals.version-items.destroy', [$version->id, $vi->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('حذف البند من الـ version؟')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            @if($versionItems->count() === 0)
                                <tr>
                                    <td colspan="8" class="text-center text-muted">مافيش بنود مربوطة</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary">Save all changes</button>
                    <a class="btn btn-outline-secondary" href="{{ route('appraisals.items.index') }}">Items
                        library</a>
                </div>
            </form>
        </div>
    </div>
@endsection