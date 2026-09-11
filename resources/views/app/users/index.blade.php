@extends('layouts.app', ['page' => 'users'])
@section('content')
    <div class="card">
        <div class="card-body border-bottom py-3">
            @error('signature_file')
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>@lang('ui.upload_failed')</strong> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="@lang('ui.close')"></button>
                </div>
            @enderror
            @error('file')
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>@lang('ui.import_failed')</strong> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="@lang('ui.close')"></button>
                </div>
            @enderror
            @error('impersonation')
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>@lang('ui.impersonation_failed')</strong> {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="@lang('ui.close')"></button>
                </div>
            @enderror
            <div class="d-flex">
                <form>
                    <div class="row g-2">
                        <div class="input-icon col">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input id="indexSearch" name="search" type="text" value="" class="form-control"
                                placeholder="@lang('ui.search_3')" aria-label="@lang('ui.search_2')" spellcheck="false" data-ms-editor="true"
                                autocomplete="off" />
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-icon btn-primary" aria-label="@lang('ui.button')">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <div class="col-auto ms-auto d-print-none">
                    @can('create', App\Models\User::class)
                        <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal"
                            data-bs-target="#importUsersModal">
                            <i class="ti ti-file-import"></i> @lang('ui.import') </button>
                        <a data-bs-original-title="@lang('ui.create')" data-bs-placement="top" data-bs-toggle="tooltip"
                            class="pull-right btn btn-primary" href="{{ route('users.create') }}">
                            <i class="ti ti-plus"></i>
                            @lang('crud.common.create')
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap datatable">
                <thead>
                    <tr>
                        <th class="text-left">@lang('crud.users.inputs.name')</th>
                        <th class="text-left">@lang('crud.users.inputs.email')</th>
                        <th class="text-left">@lang('Signature')</th>
                        <th class="text-center">@lang('crud.common.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name ?? '-' }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>
                                @if($user->signature)
                                    <span class="badge bg-success cursor-pointer" data-bs-toggle="popover"
                                        data-bs-trigger="hover focus" data-bs-html="true"
                                        data-bs-content="<img src='{{ asset('storage/' . $user->signature->image_path) }}' style='max-width:200px;' />"> @lang('ui.has_signature') </span>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                        data-bs-target="#uploadSignatureModal"
                                        onclick="setSignatureUploadAction('{{ $user->id }}', '{{ $user->name }}')">
                                        <i class="ti ti-upload"></i> @lang('ui.upload') </button>
                                @endif
                            </td>
                            <td class="text-center">
                                <div role="group" aria-label="@lang('ui.row_actions')" class="btn-group">
                                    @can('update', $user)
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-icon btn-outline-warinig ms-1">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    @endcan @can('view', $user)
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-icon btn-outline-info ms-1">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    @endcan @can('delete', $user)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline pointer ms-1"
                                            onsubmit="return confirm('{{ __('crud.common.are_you_sure') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-outline-danger">
                                                <i class="ti ti-trash-x"></i>
                                            </button>
                                        </form>
                                    @endcan
                                    @if(auth()->user()->hasRole('super-admin') && !session()->has('impersonator_id') && auth()->id() !== $user->id)
                                        <form action="{{ route('users.impersonate', $user) }}" method="POST" class="inline pointer ms-1"
                                            onsubmit="return confirm({{ Js::from(__('ui.confirm_sign_in_as', ['name' => $user->name])) }})">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-outline-primary" title="@lang('ui.sign_in_as_user')">
                                                <i class="ti ti-user-share"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">@lang('crud.common.no_items_found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-left">
            {!! $users->render() !!}
        </div>
    </div>
    </div>

    <!-- Upload Signature Modal -->
    <div class="modal fade" id="uploadSignatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="uploadSignatureForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('ui.upload_signature_for') <span id="modalUserName"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('ui.close')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">@lang('ui.select_signature_image')</label>
                            <input type="file" name="signature_file" class="form-control"
                                accept="image/png, image/jpeg, image/webp" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('ui.close')</button>
                        <button type="submit" class="btn btn-primary">@lang('ui.upload')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>

    <!-- Import Users Modal -->
    <div class="modal fade" id="importUsersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('users.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('ui.import_users')</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('ui.close')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">@lang('ui.select_excel_csv_file')</label>
                            <input type="file" name="file" class="form-control"
                                accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                required>
                        </div>
                        <div class="mt-3">
                            <a href="{{ route('users.template') }}" class="text-decoration-underline">
                                <i class="ti ti-download"></i> @lang('ui.download_template') </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('ui.close')</button>
                        <button type="submit" class="btn btn-primary">@lang('ui.import')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setSignatureUploadAction(userId, userName) {
            var form = document.getElementById('uploadSignatureForm');
            var modalTitle = document.getElementById('modalUserName');

            if (modalTitle) modalTitle.textContent = userName;

            if (form) {
                var dummyUrl = "{{ route('users.upload-signature', '000') }}";
                form.action = dummyUrl.replace('000', userId);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            })
        });
    </script>
@endsection
