@extends('layouts.app', ['page' => 'permissions'])
@section('title', 'قائمة التقارير')
@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body border-bottom py-3">
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Number</th>
                                    <th>Name</th>
                                    <th>Cost Center</th>
                                    <th>Location</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                 @forelse(App\Models\Employee::all() as $k=> $role)
                                <tr>
                                    <td>{{ $k+1 }}</td>
                                    <td>{{ $role->number ?? '-' }}</td>
                                    <td>{{ $role->english_name ?? '-' }}</td>
                                    <td>{{ $role->center->name ?? '-' }}</td>
                                    <td>{{ $role->location->name ?? '-' }}</td>
                                    <td>{{ $role->balance ?? '-' }}</td>
                                   
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2">@lang('crud.common.no_items_found')</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection