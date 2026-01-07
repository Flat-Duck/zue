@extends('layouts.app')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Maintenance & Backups
                </h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="btn-list">
                    <form action="{{ route('maintenance.quick-backup') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-bolt me-2"></i> Quick Full Backup
                        </button>
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
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                    <div>{{ session('error') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <div class="row row-cards">
            <!-- Statistics Card -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="subheader">Total Backups</div>
                        <div class="h3 m-0">{{ $stats['total_count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="subheader">Storage Used</div>
                        <div class="h3 m-0">{{ $stats['total_size'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Storage Location</div>
                        <div class="h3 m-0 text-truncate" title="{{ $stats['storage_path'] }}">{{ $stats['storage_path'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Background Activity (Recent 10)</h3>
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
                                    <th>Task ID</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>File/Error</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>#{{ $log->id }}</td>
                                        <td class="text-capitalize">{{ $log->type }}</td>
                                        <td>
                                            @if($log->status == 'pending')
                                                <span class="badge bg-yellow-lt">Pending</span>
                                            @elseif($log->status == 'running')
                                                <span class="badge bg-blue-lt">Running <span class="animated-dots"></span></span>
                                            @elseif($log->status == 'completed')
                                                <span class="badge bg-green-lt">Completed</span>
                                            @elseif($log->status == 'failed')
                                                <span class="badge bg-red-lt">Failed</span>
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
                                        <td colspan="5" class="text-center text-muted">No recent activity.</td>
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
                        <h3 class="card-title">Auto Backup Configuration</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" name="auto_backup_enabled" value="1" {{ $settings['auto_backup_enabled'] == '1' ? 'checked' : '' }}>
                                        <span class="form-check-label">Enable Auto Backups</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Interval</label>
                                    <select name="backup_interval" class="form-select">
                                        <option value="daily" {{ $settings['backup_interval'] == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ $settings['backup_interval'] == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ $settings['backup_interval'] == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Run at (Time)</label>
                                    <input type="time" name="backup_time" class="form-control" value="{{ $settings['backup_time'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Retention Policy</label>
                                    <div class="input-group">
                                        <input type="number" name="keep_backups_count" class="form-control" value="{{ $settings['keep_backups_count'] }}" min="1">
                                        <span class="input-group-text">backups</span>
                                    </div>
                                    <small class="form-hint">Oldest backups will be deleted automatically.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>

            <!-- Export Section -->
            <div class="col-md-6">
                <form action="{{ route('maintenance.export') }}" method="POST" class="card">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Export Database</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Export Type</label>
                            <select name="type" class="form-select">
                                <option value="both">Structure & Data</option>
                                <option value="structure">Structure Only</option>
                                <option value="data">Data Only</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Tables (Empty for all)</label>
                            <select name="tables[]" class="form-select" multiple size="10">
                                @foreach($tables as $table)
                                    <option value="{{ $table }}">{{ $table }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint">Hold Ctrl to select multiple tables.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="save_to_backups" value="1" checked>
                                <span class="form-check-label">Save to server backups (Background)</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-download me-2"></i> Export
                        </button>
                    </div>
                </form>
            </div>

            <!-- Import Section -->
            <div class="col-md-6">
                <form action="{{ route('maintenance.import') }}" method="POST" enctype="multipart/form-data" class="card">
                    @csrf
                    <div class="card-header">
                        <h3 class="card-title">Import SQL</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">SQL File</label>
                            <input type="file" name="sql_file" class="form-control" accept=".sql" required>
                            <small class="form-hint">Upload a .sql file to execute against the database.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>Warning:</strong> Importing will execute all SQL commands in the file. This may overwrite existing data.
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to import this SQL file?')">
                            <i class="ti ti-upload me-2"></i> Import
                        </button>
                    </div>
                </form>
            </div>

            <!-- Backups List -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Server Backups</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
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
                                                    <i class="ti ti-download"></i> Download
                                                </a>
                                                <form action="{{ route('maintenance.restore', $backup['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('RESTORE: This will overwrite current database. Are you sure?')">
                                                        <i class="ti ti-rotate-2"></i> Restore
                                                    </button>
                                                </form>
                                                <form action="{{ route('maintenance.delete', $backup['name']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="ti ti-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No backups found on server.</td>
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