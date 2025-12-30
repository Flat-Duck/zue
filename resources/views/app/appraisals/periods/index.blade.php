@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">فترات التقييم</h2>
                    <div class="text-muted">تفتح وتقفل تلقائياً حسب التواريخ</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>الفترة</th>
                            <th>نافذة التقييم</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $p)
                            <tr>
                                <td class="fw-bold">{{ $p->label }}</td>
                                <td>{{ $p->window_open_from->format('Y-m-d') }} → {{ $p->window_open_to->format('Y-m-d') }}</td>
                                <td>
                                    @php
                                        $badge = match ($p->status) {
                                            'open' => 'bg-green',
                                            'closed' => 'bg-orange',
                                            'locked' => 'bg-red',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ strtoupper($p->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection