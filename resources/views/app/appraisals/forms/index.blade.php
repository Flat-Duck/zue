@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Appraisal Forms</h2>
                    <div class="text-muted">نماذج متعددة حسب نوع الوظيفة</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.forms.create') }}" class="btn btn-primary">+ Form</a>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div> @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Active</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forms as $f)
                            <tr>
                                <td class="fw-bold">{{ $f->code }}</td>
                                <td>{{ $f->name_ar }}</td>
                                <td>{!! $f->is_active ? '<span class="badge bg-green">YES</span>' : '<span class="badge bg-secondary">NO</span>' !!}
                                </td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('appraisals.forms.edit', $f->id) }}">Edit</a>
                                    <a class="btn btn-sm btn-outline-success"
                                        href="{{ route('appraisals.versions.index', $f->id) }}">Versions</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('appraisals.items.index') }}" class="btn btn-outline-secondary">Go to Items</a>
        </div>
    </div>
@endsection