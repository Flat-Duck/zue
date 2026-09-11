@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.appraisal_items')</h2>
                    <div class="text-muted">@lang('appraisals.shared_items_library')</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.items.create') }}" class="btn btn-primary">@lang('appraisals.item')</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2" method="GET">
                    <div class="col">
                        <input type="text" name="q" value="{{ $q }}" class="form-control"
                            placeholder="@lang('appraisals.search_by_key_or_name')">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary">@lang('appraisals.search')</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>@lang('appraisals.section')</th>
                            <th>@lang('appraisals.key')</th>
                            <th>@lang('appraisals.label')</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $i->default_section }}</span></td>
                                <td class="fw-bold">{{ $i->key }}</td>
                                <td>{{ $i->default_label }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('appraisals.items.edit', $i->id) }}">@lang('appraisals.edit')</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $items->links() }}
            </div>
        </div>
    </div>
@endsection