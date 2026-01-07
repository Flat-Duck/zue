@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">تقييماتي (كمُقيّم)</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.reviews.create') }}" class="btn btn-primary">
                        إنشاء تقييم جديد
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>الفترة</th>
                            <th>الحالة</th>
                            <th>المجموع</th>
                            <th>النسبة</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $r)
                            <tr>
                                <td class="fw-bold">{{ $r->employee->name ?? ('#' . $r->employee_id) }}</td>
                                <td>{{ $r->period->label }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($r->status) }}</span></td>
                                <td>{{ $r->total_score ?? '-' }} / {{ $r->max_score ?? '-' }}</td>
                                <td>{{ $r->percentage ?? '-' }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('appraisals.reviews.edit', $r->id) }}">
                                        فتح
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">مافيش تقييمات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection