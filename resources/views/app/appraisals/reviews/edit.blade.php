@section('styles')
    @vite('resources/sass/print/appraisal-review.scss')
@endsection
@extends('layouts.app', ['page' => 'appraisals'])

@section('content')

    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.appraisal_of', ['name' => $review->employee->name ?? '#'.$review->employee_id])</h2>
                    <div class="text-muted">
                        @lang('appraisals.period_line', ['period' => $review->period->label]) —
                        @lang('appraisals.form_line', [
                            'form' => $review->formVersion->form->name_ar,
                            'version' => $review->formVersion->version,
                        ])
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <span class="badge bg-secondary">{{ strtoupper($review->status) }}</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Progress Bar --}}
        <div class="progress-container mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span>@lang('appraisals.completion_rate')</span>
                <span id="progress-text">0%</span>
            </div>
            <div class="progress">
                <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0"
                    aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        {{-- Attendance Stats (Read-Only from TimeSheets) --}}
        <div class="card mb-3">
            <div class="card-status-top bg-lime"></div>
            <div class="card-header">
                <h3 class="card-title">@lang('appraisals.attendance_data_calculated')</h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="h1 mb-0">{{ $attendanceStats['sick_leaves'] }}</div>
                        <div class="text-muted">@lang('appraisals.sick_leave_days_s')</div>
                    </div>
                    <div class="col">
                        <div class="h1 mb-0">{{ $attendanceStats['absence_days'] }}</div>
                        <div class="text-muted">@lang('appraisals.absence_days_x')</div>
                    </div>
                    <div class="col">
                        <div class="h1 mb-0">{{ $attendanceStats['unpaid_leaves'] }}</div>
                        <div class="text-muted">@lang('appraisals.unpaid_z')</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.department')</div>
                        <div class="fw-bold">{{ $review->employee->department->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.administration')</div>
                        <div class="fw-bold">{{ $review->employee->administration->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.location')</div>
                        <div class="fw-bold">{{ $review->employee->location->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-label">@lang('appraisals.cost_center')</div>
                        <div class="fw-bold">{{ $review->employee->costCenter->name ?? '-' }}</div>
                    </div>
                </div>

                <div class="hr-text">@lang('appraisals.result')</div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="text-muted">@lang('appraisals.total')</div>
                        <div class="h3">{{ $review->total_score ?? 0 }} / {{ $review->max_score ?? 0 }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted">@lang('appraisals.percentage')</div>
                        <div class="h3">{{ $review->percentage ?? 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('appraisals.reviews.update', $review->id) }}" id="review-form">
            @csrf
            @method('PUT')

            @foreach($itemsBySection as $section => $items)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ match ($section) {
                    'job_performance' => 'الأداء الوظيفي',
                    'personal_traits' => 'الصفات الشخصية',
                    'initiative' => 'المبادرة والتميز',
                    default => $section
                } }}
                            </h3>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>@lang('appraisals.item')</th>
                                        <th class="text-center">@lang('appraisals.max_limit')</th>
                                        <th class="text-center" style="width: 250px;">@lang('appraisals.appraisal')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $vi)
                                        @php
                                            $scoreRow = $scoresMap[$vi->id] ?? null;
                                            $isText = $vi->item && $vi->item->type === 'text';
                                            $val = $isText ? ($scoreRow?->text_value ?? '') : ($scoreRow?->score ?? '');
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $vi->resolved_label }}</td>
                                            <td class="text-center">
                                                @if(!$isText)
                                                    <span class="badge bg-azure-lt">{{ $vi->resolved_max_score }}</span>
                                                @else
                                                    <span class="badge bg-secondary-lt">@lang('appraisals.textual')</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($isText)
                                                    <textarea class="form-control score-input"
                                                              name="scores[{{ $vi->id }}]"
                                                              rows="2"
                                                              placeholder="@lang('appraisals.enter_text_here')"
                                                              {{ $review->status !== 'draft' ? 'disabled' : '' }}>{{ old('scores.' . $vi->id, $val) }}</textarea>
                                                @else
                                                    <input type="number"
                                                           class="form-control text-center score-input"
                                                           name="scores[{{ $vi->id }}]"
                                                           data-max="{{ $vi->resolved_max_score }}"
                                                           min="0"
                                                           max="{{ $vi->resolved_max_score }}"
                                                           value="{{ old('scores.' . $vi->id, $val) }}"
                                                           {{ $review->status !== 'draft' ? 'disabled' : '' }} />
                                                    <div class="invalid-feedback" style="display:none;">@lang('appraisals.over_limit')</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
            @endforeach

            <div class="sticky-footer d-flex gap-2 justify-content-end">
                <a href="{{ route('appraisals.reviews.index') }}" class="btn btn-outline-secondary me-auto">@lang('appraisals.back')</a>

                <button class="btn btn-primary" id="save-btn" {{ $review->status !== 'draft' ? 'disabled' : '' }}>@lang('appraisals.save_changes')</button>

                @if($review->status === 'draft')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#submitModal"> @lang('appraisals.submit') </button>
                @else
                    <button class="btn btn-success" disabled>@lang('appraisals.submitted')</button>
                @endif
            </div>

            {{-- Submit Modal --}}
            <div class="modal fade" id="submitModal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="modal-title">@lang('appraisals.confirm_generic')</div>
                            <div>@lang('appraisals.cannot_edit_after_submit')</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary me-auto"
                                data-bs-dismiss="modal">@lang('appraisals.cancel')</button>
                            <button type="button" class="btn btn-success"
                                onclick="document.getElementById('submit-form').submit()">@lang('appraisals.yes_submit')</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <form method="POST" action="{{ route('appraisals.reviews.submit', $review->id) }}" id="submit-form" class="d-none">
            @csrf
        </form>

    </div>

    <div class="toast-container position-fixed bottom-0 start-0 p-3" style="z-index: 1055">
        <div id="autosave-toast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"> @lang('appraisals.saved_automatically') </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="@lang('appraisals.close')"></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.score-input');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const saveBtn = document.getElementById('save-btn');
            const reviewForm = document.getElementById('review-form');
            const totalDisplay = document.querySelector('.h3:nth-of-type(1)'); // Selects the total score box roughly
            const percentDisplay = document.querySelectorAll('.h3')[1]; // Selects percentage box
            const autosaveToastEl = document.getElementById('autosave-toast');
            let autosaveToast;
            let autosaveTimer;

            document.addEventListener('DOMContentLoaded', function() {
                const autosaveToastEl = document.getElementById('autosave-toast');
                if(autosaveToastEl) {
                    autosaveToast = new bootstrap.Toast(autosaveToastEl, { delay: 2000 });
                }
            });

            function updateProgress() {
                let filled = 0;
                let total = inputs.length;

                inputs.forEach(input => {
                    // For number inputs, check if value is not empty
                    // For textareas, check if value is not empty after trimming
                    if (input.tagName === 'INPUT' && input.type === 'number') {
                        if (input.value !== '' && input.value !== null) {
                            filled++;
                        }
                    } else if (input.tagName === 'TEXTAREA') {
                        if (input.value.trim() !== '') {
                            filled++;
                        }
                    }
                });

                const percent = total > 0 ? Math.round((filled / total) * 100) : 0;
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
                progressText.innerText = getGrade(percent) + ' (' + percent + '%)';

                if (percent === 100) {
                    progressBar.classList.add('bg-success');
                } else {
                    progressBar.classList.remove('bg-success');
                }
            }

            function getGrade(percent) {
                // This is just a visual estimation, real grade comes from server after calc
                // Matches the backend logic roughly for user feedback
                if (percent < 10) return 'جاري التقييم...'; // Don't show grades too early
                // We'll trust the server response for the actual grade,
                // but we can update the label to show "Completeness" here.
                return 'نسبة الإكمال';
            }

            function performAutosave() {
               const formData = new FormData(reviewForm);

               // We use fetch to send data
               fetch(reviewForm.action, {
                   method: 'POST',
                   headers: {
                       'X-Requested-With': 'XMLHttpRequest',
                       'Accept': 'application/json'
                   },
                   body: formData
               })
               .then(response => response.json())
               .then(data => {
                   if(data.success) {
                       // Update Totals from Server
                       if (totalDisplay && percentDisplay) {
                        // Assuming the structure didn't change, updates the text
                        // We might need to target IDs more specifically in future refactor
                        // For now we trust the H3 order: 1st is Total, 2nd is Percent
                        const h3s = document.querySelectorAll('.card-body .h3');
                        if(h3s.length >= 2) {
                             h3s[0].innerText = data.total_score + ' / ' + data.max_score;
                             h3s[1].innerText = data.percentage + '%';
                        }
                       }
                       autosaveToast.show();
                   }
               })
               .catch(error => console.error('Autosave failed:', error));
            }

            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Only check max score logic if it is a number input (has data-max)
                    if (this.hasAttribute('data-max')) {
                        const max = parseFloat(this.getAttribute('data-max'));
                        const val = parseFloat(this.value);

                        if (val > max) {
                            this.classList.add('is-invalid');
                            this.nextElementSibling.style.display = 'block';
                            saveBtn.disabled = true;
                        } else if (val < 0) {
                            this.classList.add('is-invalid');
                            this.nextElementSibling.style.display = 'block';
                            saveBtn.disabled = true;
                        } else {
                            this.classList.remove('is-invalid');
                            this.nextElementSibling.style.display = 'none';
                            const anyInvalid = document.querySelectorAll('.is-invalid').length > 0;
                            saveBtn.disabled = anyInvalid;

                            // Trigger Autosave
                            clearTimeout(autosaveTimer);
                            autosaveTimer = setTimeout(performAutosave, 1000);
                        }
                    } else {
                        // Text input - just trigger autosave
                        clearTimeout(autosaveTimer);
                        autosaveTimer = setTimeout(performAutosave, 1000);
                    }

                    updateProgress();
                });
            });

            updateProgress();
        });
    </script>
@endsection