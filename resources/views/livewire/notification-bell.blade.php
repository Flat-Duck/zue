{{--
    Tabler's notification dropdown: a `dropdown-menu-card` holding a flush,
    hoverable list group, with a status dot marking what has not been read.
--}}
<div class="nav-item dropdown d-none d-md-flex me-3">
    <a href="#"
       class="nav-link px-0"
       data-bs-toggle="dropdown"
       data-bs-auto-close="outside"
       tabindex="-1"
       aria-label="@lang('crud.common.notifications')">
        <i class="ti ti-bell"></i>
        @if ($this->unreadCount > 0)
            <span class="badge bg-red">{{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}</span>
        @endif
    </a>

    <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title mb-0">@lang('crud.common.notifications')</h3>

                @if ($this->unreadCount > 0)
                    <button type="button" class="btn btn-link btn-sm ms-auto p-0" wire:click="markAllRead">
                        @lang('crud.common.mark_all_read')
                    </button>
                @endif
            </div>

            <div class="list-group list-group-flush list-group-hoverable">
                @forelse ($this->notifications as $notification)
                    @php
                        $data = $notification->data;
                    @endphp

                    <div class="list-group-item" wire:key="notification-{{ $notification->id }}">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span @class([
                                    'status-dot d-block',
                                    'status-dot-animated bg-red' => is_null($notification->read_at),
                                    'bg-secondary' => ! is_null($notification->read_at),
                                ])></span>
                            </div>

                            <div class="col text-truncate">
                                <a href="{{ $data['url'] ?? '#' }}"
                                   class="text-body d-block"
                                   wire:click="markRead('{{ $notification->id }}')">
                                    {{ $data['title'] ?? __('crud.common.notifications') }}
                                </a>
                                <div class="d-block text-secondary text-truncate mt-n1">
                                    {{ $data['message'] ?? '' }}
                                </div>
                                <div class="d-block text-secondary small">
                                    {{ $notification->created_at?->diffForHumans() }}
                                </div>
                            </div>

                            @if (is_null($notification->read_at))
                                <div class="col-auto">
                                    <button type="button"
                                            class="list-group-item-actions btn btn-link p-0"
                                            wire:click="markRead('{{ $notification->id }}')"
                                            aria-label="@lang('crud.common.mark_all_read')">
                                        <i class="ti ti-check"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">
                        @lang('crud.common.no_notifications')
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
