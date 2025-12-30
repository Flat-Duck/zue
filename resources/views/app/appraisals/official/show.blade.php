@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Official Result: {{ $employee->name ?? ('#' . $employee->id) }}</h2>
                    <div class="text-muted">{{ $period->label }}</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <form method="POST" action="{{ route('appraisals.official.finalize', [$period->id, $employee->id]) }}">
                        @csrf
                        <button class="btn btn-primary">Finalize / حساب المتوسط</button>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(!$official)
            <div class="alert alert-warning">ما فيش نتيجة رسمية بعد. اضغط Finalize.</div>
        @else
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-muted">المجموع</div>
                            <div class="h3">{{ $official->total_score }} / {{ $official->max_score }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">النسبة</div>
                            <div class="h3">{{ $official->percentage }}%</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted">التقدير</div>
                            <div class="h3">{{ $official->grade ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="text-muted mt-2">عدد التقييمات (Managers): {{ $official->reviews_count }}</div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>البند</th>
                                <th class="text-center">الحد الأعلى</th>
                                <th class="text-center">AVG</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($official->scores as $s)
                                @php $vi = $s->formVersionItem; @endphp
                                <tr>
                                    <td class="fw-bold">{{ $vi->resolved_label }}</td>
                                    <td class="text-center">{{ $vi->resolved_max_score }}</td>
                                    <td class="text-center">{{ $s->avg_score }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection