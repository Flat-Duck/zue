@extends('layouts.app', ['page' => 'employees'])

@section('content')
    @if (session('import_problems'))
        <div class="alert alert-warning">
          <div>
            <h4 class="alert-title">Some values could not be read</h4>
            <p class="text-muted mb-2">
                Everything else was imported. These values were left untouched rather than
                stored incorrectly.
            </p>
            <ul class="mb-0">
                @foreach (session('import_problems') as $problem)
                    <li>{{ $problem }}</li>
                @endforeach
            </ul>
          </div>
        </div>
    @endif

    {{-- The full personnel export, matched on employee number. --}}
    <form method="POST" action="{{ route('employees.import-profiles') }}" enctype="multipart/form-data" class="card mb-4">
        @csrf
        <div class="card-header">
            <a href="{{ route('employees.index') }}" class="me-3"><i class="ti ti-arrow-back"></i></a>
            <h3 class="card-title">Import employee data</h3>
        </div>

        <div class="card-body">
            <p class="text-muted">
                Upload the personnel export. Employees are matched on
                <strong>employee number</strong>: an existing record is updated and a new
                one is created, so the same file can be uploaded again whenever it changes.
            </p>

            <div class="alert alert-info">
              <div>
                Save the file as <strong>CSV UTF-8</strong> or <strong>.xlsx</strong>. Long
                identifiers such as bank account numbers must be stored as text in the
                spreadsheet — Excel otherwise rewrites them as <code>4.10E+13</code> and the
                real digits are lost. Any it finds in that state are reported instead of imported.
              </div>
            </div>

            <div class="col-md-6">
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                       accept=".csv,.xlsx,.xls,.txt" required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card-footer text-end">
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
            <button type="submit" class="btn btn-primary ms-2">
                <i class="ti ti-upload"></i> Import
            </button>
        </div>
    </form>

    {{-- Existing bulk archive import, unchanged. --}}
    <form method="POST" action="{{ route('employees.import-archived-employees') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-header">
            <h3 class="card-title">Archive employees from a file</h3>
        </div>
        <div class="card-body">
            <div class="col-md-6">
                <input type="file" name="file" class="form-control" accept=".xlsx" required>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-outline-primary">
                <i class="ti ti-archive"></i> Archive
            </button>
        </div>
    </form>
@endsection
