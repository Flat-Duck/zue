@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Appraisal Items</h2>
                    <div class="text-muted">مكتبة البنود المشتركة بين النماذج</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.items.create') }}" class="btn btn-primary">+ Item</a>
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
                            placeholder="بحث بالـ key أو الاسم...">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Section</th>
                            <th>Key</th>
                            <th>Label</th>
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
                                        href="{{ route('appraisals.items.edit', $i->id) }}">Edit</a>
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