@extends('layouts.app', ['page' => 'employees'])
@section('content')
    <div class="row row-cards">
        @forelse($employees as $employee)
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body p-4 text-center">
                    <span class="avatar avatar-xl mb-3 rounded">SA</span>
                    <h3 class="m-0 mb-1"><a href="#">{{ $employee->english_name ?? '-' }}</a></h3>
                    <div class="text-secondary">Nuclear Power Engineer</div>
                    <div class="text-secondary">{{ $employee->number ?? '-' }}</div>
                    <div class="mt-3">
                    </div>
                </div>
                <div class="d-flex">
                    <a href="#" class="card-btn"><!-- Download SVG icon from http://tabler-icons.io/i/mail -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon me-2 text-muted">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z">
                            </path>
                            <path d="M3 7l9 6l9 -6"></path>
                        </svg>
                        Email</a>
                    <a href="#" class="card-btn"><!-- Download SVG icon from http://tabler-icons.io/i/phone -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon me-2 text-muted">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path
                                d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2">
                            </path>
                        </svg>
                        Call</a>
                </div>
            </div>
        </div>
        @empty
        @lang('crud.common.no_items_found')
        
    @endforelse

{{-- 

    </div>
    <tbody>
        
            <tr>
                <td></td>
                <td>{{ $employee->job ?? '-' }}</td>
                <td></td>
                <td>{{ $employee->id_card ?? '-' }}</td>
                <td>{{ $employee->id_card_issue_date ?? '-' }}</td>
                <td>{{ $employee->passport ?? '-' }}</td>
                <td>{{ $employee->passport_issue_date ?? '-' }}</td>
                <td>{{ $employee->address ?? '-' }}</td>
                <td>{{ $employee->phone ?? '-' }}</td>
                <td>{{ $employee->email ?? '-' }}</td>
                <td>{{ optional($employee->user)->name ?? '-' }}</td>
                <td>{{ optional($employee->location)->name ?? '-' }}</td>
                <td>{{ optional($employee->department)->name ?? '-' }}</td>
                <td>{{ optional($employee->center)->name ?? '-' }}</td>
                <td>{{ $employee->transfered_balance ?? '-' }}</td>
                <td>{{ $employee->schedule ?? '-' }}</td>
                <td>{{ $employee->start_date ?? '-' }}</td>
                <td>{{ $employee->last_date ?? '-' }}</td>
                <td>{{ $employee->total_balance ?? '-' }}</td>
                <td>{{ $employee->archived_at ?? '-' }}</td>
                <td class="text-center" style="width: 134px;">
                    <div role="group" aria-label="Row Actions" class="btn-group">
                        @can('update', $employee)
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-icon btn-outline-warinig ms-1">
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan @can('view', $employee)
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-icon btn-outline-info ms-1">
                                <i class="ti ti-eye"></i>
                            </a>
                            @endcan @can('delete', $employee)
                            <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                                class="inline pointer ms-1" onsubmit="return confirm('{{ __('crud.common.are_you_sure') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-outline-danger">
                                    <i class="ti ti-trash-x"></i>
                                </button>
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        
    </tbody>
    </table>
    </div> --}}
    <div class="card-footer d-flex align-items-left">
        {!! $employees->render() !!}
    </div>
    </div>
@endsection
