@extends('layouts.app', ['page' => 'employees'])

@section('content')
    @if (session('import_problems'))
        <div class="alert alert-warning">
          <div>
            <h4 class="alert-title">@lang('ui.some_values_could_not_be_read')</h4>
            <p class="text-muted mb-2"> @lang('ui.everything_else_was_imported_these_values') </p>
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
            <h3 class="card-title">@lang('ui.import_employee_data')</h3>
        </div>

        <div class="card-body">
            <p class="text-muted"> @lang('ui.upload_the_personnel_export_employees_are') <strong>@lang('ui.employee_number_2')</strong>@lang('ui.an_existing_record_is_updated_and_a_new_one') </p>

            <div class="alert alert-info">
              <div> @lang('ui.save_the_file_as') <strong>@lang('ui.csv_utf_8')</strong> @lang('ui.or') <strong>@lang('ui.xlsx')</strong>@lang('ui.long_identifiers_such_as_bank_account') <code>4.10E+13</code> @lang('ui.and_the_real_digits_are_lost_any_it_finds_in') </div>
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
                <i class="ti ti-upload"></i> @lang('ui.import') </button>
        </div>
    </form>

    {{-- Existing bulk archive import, unchanged. --}}
    <form method="POST" action="{{ route('employees.import-archived-employees') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-header">
            <h3 class="card-title">@lang('ui.archive_employees_from_a_file')</h3>
        </div>
        <div class="card-body">
            <div class="col-md-6">
                <input type="file" name="file" class="form-control" accept=".xlsx" required>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-outline-primary">
                <i class="ti ti-archive"></i> @lang('ui.archive') </button>
        </div>
    </form>
@endsection
