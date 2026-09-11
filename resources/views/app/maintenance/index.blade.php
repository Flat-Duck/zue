@extends('layouts.app')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"> @lang('maintenance.maintenance_backups') </h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <form action="{{ route('maintenance.quick-backup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-bolt me-2"></i> @lang('maintenance.quick_full_backup') </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-check icon alert-icon"></i></div>
                    <div>{{ session('success') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="@lang('maintenance.close')"></a>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                    <div>{{ session('error') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="@lang('maintenance.close')"></a>
            </div>
        @endif

        <div class="row row-cards">
            <!-- Statistics Card -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="subheader">@lang('maintenance.total_backups')</div>
                        <div class="h3 m-0">{{ $stats['total_count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="subheader">@lang('maintenance.storage_used')</div>
                        <div class="h3 m-0">{{ $stats['total_size'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">@lang('maintenance.storage_location')</div>
                        <div class="h3 m-0 text-truncate" title="{{ $stats['storage_path'] }}">{{ $stats['storage_path'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">@lang('maintenance.background_activity_recent_10')</h3>
                        <div class="card-actions">
                            <a href="" class="btn-action">
                                <i class="ti ti-refresh"></i>
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>@lang('maintenance.task_id')</th>
                                    <th>@lang('maintenance.type')</th>
                                    <th>@lang('maintenance.status')</th>
                                    <th>@lang('maintenance.file_error')</th>
                                    <th>@lang('maintenance.time')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>#{{ $log->id }}</td>
                                        <td class="text-capitalize">{{ $log->type }}</td>
                                        <td>
                                            @if($log->status == 'pending')
                                                <span class="badge bg-yellow-lt">@lang('maintenance.pending')</span>
                                            @elseif($log->status == 'running')
                                                <span class="badge bg-blue-lt">@lang('maintenance.running') <span class="animated-dots"></span></span>
                                            @elseif($log->status == 'completed')
                                                <span class="badge bg-green-lt">@lang('maintenance.completed')</span>
                                            @elseif($log->status == 'failed')
                                                <span class="badge bg-red-lt">@lang('maintenance.failed')</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($log->status == 'completed')
                                                {{ $log->filename }}
                                            @elseif($log->status == 'failed')
                                                <span class="text-danger" title="{{ $log->error }}">{{ Str::limit($log->error, 50) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->completed_at)
                                                {{ $log->started_at->diffForHumans($log->completed_at, true) }}
                                            @else
                                                {{ $log->created_at->diffForHumans() }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">@lang('maintenance.no_recent_activity')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Auto Backup Settings -->
            <div class="col-md-12">
                <form action="{{ route('maintenance.settings.update') }}" method="POST" class="card">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">@lang('maintenance.auto_backup_configuration')</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="auto_backup_enabled" value="1" {{ $settings['auto_backup_enabled'] == '1' ? 'checked' : '' }}>
                                        <span class="form-check-label">@lang('maintenance.enable_auto_backups')</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">@lang('maintenance.interval')</label>
                                    <select name="backup_interval" class="form-select">
                                        <option value="daily" {{ $settings['backup_interval'] == 'daily' ? 'selected' : '' }}>@lang('maintenance.daily')</option>
                                        <option value="weekly" {{ $settings['backup_interval'] == 'weekly' ? 'selected' : '' }}>@lang('maintenance.weekly')</option>
                                        <option value="monthly" {{ $settings['backup_interval'] == 'monthly' ? 'selected' : '' }}>@lang('maintenance.monthly')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">@lang('maintenance.run_at_time')</label>
                                    <input type="time" name="backup_time" class="form-control" value="{{ $settings['backup_time'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">@lang('maintenance.retention_policy')</label>
                                    <div class="input-group">
                                        <input type="number" name="keep_backups_count" class="form-control" value="{{ $settings['keep_backups_count'] }}" min="1">
                                        <span class="input-group-text">@lang('maintenance.backups')</span>
                                    </div>
                                    <small class="form-hint">@lang('maintenance.oldest_backups_will_be_deleted_automatically')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">@lang('maintenance.save_settings')</button>
                    </div>
                </form>
            </div>

            <!-- Export Section -->
            <div class="col-md-6">
                <form action="{{ route('maintenance.export') }}" method="POST" class="card">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">@lang('maintenance.export_database')</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">@lang('maintenance.export_type')</label>
                            <select name="type" class="form-select">
                                <option value="both">@lang('maintenance.structure_data')</option>
                                <option value="structure">@lang('maintenance.structure_only')</option>
                                <option value="data">@lang('maintenance.data_only')</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">@lang('maintenance.select_tables_empty_for_all')</label>
                            <select name="tables[]" class="form-select" multiple size="10">
                                @foreach($tables as $table)
                                    <option value="{{ $table }}">{{ $table }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint">@lang('maintenance.hold_ctrl_to_select_multiple_tables')</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="save_to_backups" value="1" checked>
                                <span class="form-check-label">@lang('maintenance.save_to_server_backups_background')</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-download me-2"></i> @lang('maintenance.export') </button>
                    </div>
                </form>
            </div>

            <!-- Import Section -->
            <div class="col-md-6">
                <form action="{{ route('maintenance.import') }}" method="POST" enctype="multipart/form-data" class="card">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">@lang('maintenance.import_sql')</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">@lang('maintenance.sql_file')</label>
                            <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                            <small class="form-hint">@lang('maintenance.upload_a_sql_file_to_execute_against_the')</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>@lang('maintenance.warning')</strong> @lang('maintenance.importing_will_execute_all_sql_commands_in') </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-warning" data-confirm="{{ __('maintenance.confirm_import_sql') }}">
                            <i class="ti ti-upload me-2"></i> @lang('maintenance.import') </button>
                    </div>
                </form>
            </div>

            <!-- Backups List -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">@lang('maintenance.server_backups')</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>@lang('maintenance.filename')</th>
                                    <th>@lang('maintenance.size')</th>
                                    <th>@lang('maintenance.created_at')</th>
                                    <th class="text-end">@lang('maintenance.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                    <tr>
                                        <td>{{ $backup['name'] }}</td>
                                        <td>{{ $backup['size'] }}</td>
                                        <td>{{ $backup['created_at'] }}</td>
                                        <td class="text-end">
                                            <div class="btn-list flex-nowrap justify-content-end">
                                                <a href="{{ route('maintenance.download', $backup['name']) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-download"></i> @lang('maintenance.download') </a>
                                                <form action="{{ route('maintenance.restore', $backup['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm="{{ __('maintenance.confirm_restore') }}">
                                                        <i class="ti ti-rotate-2"></i> @lang('maintenance.restore') </button>
                                                </form>
                                                <form action="{{ route('maintenance.delete', $backup['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="{{ __('maintenance.confirm_generic') }}">
                                                        <i class="ti ti-trash"></i> @lang('maintenance.delete') </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">@lang('maintenance.no_backups_found_on_server')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection