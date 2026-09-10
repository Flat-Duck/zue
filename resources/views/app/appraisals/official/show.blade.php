@extends('layouts.app', ['page' => 'appraisals'])
@section('content')
    @section('styles')
    @vite('resources/sass/print/appraisal-official.scss')
    @endsection
    <div class="card" dir="rtl">
        <div class="card-body border-bottom" id="section-to-print">
            <div id="pageHead" style="position: fixed;">
            </div>
            <div class="page">
                <div class="pageNo">3</div>

                <!-- Header -->
                <table class="t hdr">
                    <tr>
                        <td class="center bold mid" style="width: 30%;">
                            المؤسسة الوطنية للنفط<br>
                            إدارة شئون العاملين
                        </td>
                        <td class="center mid" style="width: 46%;">
                            نموذج تقييم الأداء للوظائف الادارية والمالية
                        </td>
                        <td class="center" style="width: 24%;">
                            <img src="{{ asset('/img/noc-logo.png') }}" style="height: 76px" class="mx-auto d-block">
                        </td>
                    </tr>
                </table>

                <div class="center bold mt-1" style="font-size:10pt;">
                    خلال الفترة من <span class="bold">{{ $period->window_open_from->format('d/m/Y') }} </span> م إلى <span
                        class="bold">{{ $period->window_open_to->format('d/m/Y') }} </span>م
                </div>
                <div class="tableTitle mt-1">
                    1 بيانات أساسية

                </div>
                <!-- (1) بيانات أساسية -->
                <table class="t mt-1">
                    <tr>
                        <td>اسم المستخدم</td>
                        <td colspan="2">{{ $employee->full_name ?? $employee->first_name }}</td>

                        <td>شارة تعريف رقم</td>
                        <td style="width: 7%;">{{ $employee->id }}</td>

                        <td colspan="2">الادارة</td>
                        <td colspan="2">{{ $employee->administrationName ?? '-' }}</td>



                        {{-- <td class="col-10pc">العمليات</td>
                        <td class="col-12pc">{{ $employee->department->name ?? '-' }}</td> --}}


                    </tr>

                    <tr>
                        <td>تاريخ التعيين</td>
                        <td>{{ $employee->start_date ?? '-' }}</td>

                        <td>الوظيفة</td>
                        <td colspan="2">{{ $employee->job_title ?? '-' }}</td>

                        <td>الفئة</td>
                        <td>{{ $employee->job_level ?? '-' }}</td>

                        <td>المستوى</td>
                        <td><input type="text" value=""></td>
                    </tr>

                    <tr>
                        <td>مؤهل العلمي</td>
                        <td colspan="8"><input type="text" value=""></td>
                    </tr>
                </table>
                <div class="tableTitle mt-1">
                    (2) بيانات إضافية

                </div>
                <!-- (2) بيانات إضافية -->
                <table class="t mt-1">
                    <tr>
                        <td class="col-16pc">الإجازات المرضية</td>
                        <td class="col-14pc">{{ $employee->sick_leaves() ?? 0 }}</td>

                        <td class="col-12pc">أيام الغياب</td>
                        <td class="col-14pc">{{ $employee->sick_leaves() ?? 0 }}</td>

                        <td class="col-16pc">إجازة بدون مرتب</td>
                        <td class="col-14pc">{{ $employee->sick_leaves() ?? 0 }}</td>

                        <td class="col-16pc">أيام المرتب الأساسي</td>
                        <td class="col-14pc">{{ $employee->sick_leaves() ?? 0 }}</td>
                    </tr>
                    <tr>
                        <td colspan="2">الجزاءات الموقعة خلال الفترة</td>
                        <td><input type="text" value=""></td>

                        <td colspan="2">أيام الندب أو الإعارة</td>
                        <td><input type="text" value=""></td>

                        <td>أيام التدريب</td>
                        <td><input type="text" value=""></td>
                    </tr>
                </table>

                <!-- تصديق البيانات -->
                <table class="t mt-1">
                    <tr>
                        <td class="grey center bold col-12pc">تصديق البيانات</td>

                        <td class="col-10pc">الاسم</td>
                        <td class="col-20pc"><input type="text" value=""></td>

                        <td class="col-10pc">الوظيفة</td>
                        <td style="width: 18%;"><input type="text" value=""></td>

                        <td class="col-20pc">رئيس قسم شؤون العاملين</td>
                        <td class="col-20pc"><input type="text" value=""></td>

                        <td class="col-10pc">التوقيع</td>
                        <td class="col-20pc"><input type="text" value=""></td>
                    </tr>
                </table>

                <!-- Main scoring -->


                <div class="gridx mt-1">

                    <!-- RIGHT: scoring tables -->
                    <div>
                        @php
                            $grandMax = 0;
                            $grandTotal = 0;
                            $counter = 3;
                        @endphp

                        @foreach($groupedScores as $sectionKey => $scores)
                            @php

                                $sectionTitle = $sectionLabels[$sectionKey] ?? $sectionKey;
                                $sectionMax = 0;
                                $sectionTotal = 0;
                            @endphp

                            <div class="tableTitle mt-1">({{ $counter++ }}) {{ $sectionTitle }}</div>
                            <table class="t">
                                <tr class="center bold">
                                    <td style="width: 64%;">العنصر</td>
                                    <td class="col-12pc">الحد الأعلى</td>
                                    <td class="col-12pc">الرئيس المباشر</td>
                                    <td class="col-12pc">الرئيس الأعلى</td>
                                </tr>
                                @foreach($scores as $score)
                                    @php
                                        $vi = $score->formVersionItem;
                                        $max = $vi->resolved_max_score;
                                        $val = $score->avg_score;
                                        $override = $score->score_override;
                                        $effective = $override ?? $val;

                                        $sectionMax += $max;
                                        $sectionTotal += $effective;
                                    @endphp
                                    <tr>
                                        <td>{{ $vi->resolved_label }}</td>
                                        <td class="center">{{ $max }}</td>
                                        <td class="center">{{ $val }}</td> <!-- Using val directly as it's typically the calc -->
                                        <td class="center">
                                            <input class="upper" type="number" value="{{ $override }}" data-sec="{{ $sectionKey }}"
                                                disabled>
                                            <!-- Disabled for now until edit mode is added, or should it be editable? User said 'if overrided also count it', implying view mode of existing overrides. -->
                                            <!-- But the prototype had inputs. Let's leave disabled to signify it's official view, unless we add a form wrapper. -->
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bold center">
                                    <td>المجموع</td>
                                    <td>{{ $sectionMax }}</td>
                                    <td>{{ $sectionTotal }}</td> <!-- This sums the effective scores -->
                                    <td>{{ $scores->sum('score_override') > 0 ? $scores->sum('score_override') : '' }}</td>
                                </tr>
                            </table>
                            @php
                                $grandMax += $sectionMax;
                                $grandTotal += $sectionTotal;
                            @endphp
                        @endforeach
                    </div>
                    <!-- LEFT: summaries + comments + rec -->
                    <div>
                        <div class="tableTitle mt-1">
                            (7) مجموع الدرجات والتقدير
                        </div>
                        <table class="t">
                            <tr>
                                @foreach($groupedScores as $sectionKey => $scores)
                                    <td class="center bold">{{ $sectionLabels[$sectionKey] ?? $sectionKey }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($groupedScores as $sectionKey => $scores)
                                    @php
                                        $sTotal = $scores->sum(fn($s) => $s->score_override ?? $s->avg_score);
                                    @endphp
                                    <td class="center bold">{{ $sTotal }}</td>
                                @endforeach
                            </tr>
                        </table>

                        <table class="t mt-1">
                            <tr>
                                <td class="center bold col-55pc">المجموع الكلي</td>
                                <td class="center bold col-45pc">{{ $grandTotal }}</td>
                            </tr>
                        </table>

                        <table class="t mt-1">
                            <tr>
                                <td class="center bold col-55pc">التقدير</td>
                                <td class="center bold col-45pc">
                                    {{ $grandMax > 0 ? round(($grandTotal / $grandMax) * 100) : 0 }}%
                                </td>
                            </tr>
                        </table>

                        <table class="t mt-1">
                            <tr>
                                <td class="center bold">ممتاز<br><span class="xs">(90-100)</span></td>
                                <td class="center bold">جيد جداً<br><span class="xs">(75-89)</span></td>
                                <td class="center bold">جيد<br><span class="xs">(60-74)</span></td>
                                <td class="center bold">متوسط<br><span class="xs">(45-59)</span></td>
                                <td class="center bold">ضعيف<br><span class="xs">أقل من 45</span></td>
                            </tr>
                        </table>


                        <div class="box mt-1">
                            <div class="tableTitle ">
                                (8) نقاط القوة للصفات الإيجابية الأخرى التي يتميز بها ولم تشملها العناصر
                                السابقة
                            </div>
                            <div class="body" style="height: 30mm;">
                                <textarea></textarea>
                            </div>
                        </div>

                        <div class="box mt-1">
                            <div class="tableTitle">(9) نقاط الضعف للصفات السلبية الأخرى التي يتصف بها ولم تشملها
                                العناصر
                                السابقة
                            </div>
                            <div class="body" style="height: 30mm;">
                                <textarea></textarea>
                            </div>
                        </div>

                        <div class="box mt-1">
                            <div class="tableTitle ">(10) توصيات</div>
                            <div class="body" style="padding: 2.2mm;">
                                <div class="optRow">
                                    <label><input type="radio" name="rec"></label>
                                    <span>يحتاج إلى تدريب في مجال:</span>
                                    <span class="dots"></span>
                                </div>
                                <div class="optRow">
                                    <label><input type="radio" name="rec"></label>
                                    <span>النقل الى وظيفة أخرى</span>
                                    <span class="dots"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- (6) رأي الرئيس الأعلى -->
                <div class="box mt-1">
                    <div class="tableTitle mt-1">(6) رأي الرئيس الأعلى</div>
                    <div class="body" style="padding: 2mm;">
                        <div class=" bold small">مبررات تقدير درجة الكفاءة (ضعيف – متوسط – ممتاز)</div>
                        <div style="height: 10mm; border-bottom: 2px dotted #000; margin-top: 1mm;"></div>

                        <div class=" bold small" style="margin-top: 2mm;">
                            أسباب اختلاف تقرير الكفاءة بين الرئيس المباشر والرئيس الأعلى
                        </div>
                        <div style="height: 10mm; border-bottom: 2px dotted #000; margin-top: 1mm;"></div>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="mt-1" style="display:grid; grid-template-columns: 1fr 1fr; gap: 5mm;">
                    <table class="t">
                        <tr>
                            <td class="center bold" colspan="2">توقيع الرئيس المباشر</td>
                        </tr>
                        <tr>
                            <td class="col-25pc">الاسم:</td>
                            <td><input type="text" value="عمر جمعة دحيم"></td>
                        </tr>
                        <tr>
                            <td>الصفة:</td>
                            <td><input type="text" value="رئيس قسم (الشئون الإدارية) 103"></td>
                        </tr>
                        <tr>
                            <td>التوقيع:</td>
                            <td><input type="text" value=""></td>
                        </tr>
                    </table>

                    <table class="t">
                        <tr>
                            <td class="center bold" colspan="2">توقيع الرئيس الأعلى</td>
                        </tr>
                        <tr>
                            <td class="col-25pc">الاسم:</td>
                            <td><input type="text" value="ناجي محمد المبروك"></td>
                        </tr>
                        <tr>
                            <td>الصفة:</td>
                            <td><input type="text" value="مراقب حقول 103"></td>
                        </tr>
                        <tr>
                            <td>التوقيع:</td>
                            <td><input type="text" value=""></td>
                        </tr>
                    </table>
                </div>

            </div>

            <script>
                function sumSection(sec, cls) {
                    let total = 0;
                    document.querySelectorAll(`.${cls}[data-sec="${sec}"]`).forEach(inp => {
                        const v = parseFloat(inp.value);
                        if (!isNaN(v)) total += v;
                    });
                    return total;
                }

                function recalc() {
                    const includeInit = document.getElementById("includeInit").checked;

                    const maxPerf = sumSection("perf", "max");
                    const totPerf = sumSection("perf", "score");
                    const upPerf = sumSection("perf", "upper");

                    const maxPersonal = sumSection("personal", "max");
                    const totPersonal = sumSection("personal", "score");
                    const upPersonal = sumSection("personal", "upper");

                    const maxInit = sumSection("init", "max");
                    const totInit = sumSection("init", "score");
                    const upInit = sumSection("init", "upper");

                    document.getElementById("maxPerf").textContent = Math.round(maxPerf);
                    document.getElementById("totPerf").textContent = Math.round(totPerf);
                    document.getElementById("upPerf").textContent = Math.round(upPerf);

                    document.getElementById("maxPersonal").textContent = Math.round(maxPersonal);
                    document.getElementById("totPersonal").textContent = Math.round(totPersonal);
                    document.getElementById("upPersonal").textContent = Math.round(upPersonal);

                    document.getElementById("maxInit").textContent = Math.round(maxInit);
                    document.getElementById("totInit").textContent = Math.round(totInit);
                    document.getElementById("upInit").textContent = Math.round(upInit);

                    document.getElementById("sumPerf").textContent = Math.round(totPerf);
                    document.getElementById("sumPersonal").textContent = Math.round(totPersonal);
                    document.getElementById("sumInit").textContent = Math.round(totInit);

                    const maxApplicable = maxPerf + maxPersonal + (includeInit ? maxInit : 0);
                    const totalApplicable = totPerf + totPersonal + (includeInit ? totInit : 0);

                    document.getElementById("maxAll").textContent = Math.round(maxPerf + maxPersonal + maxInit);
                    document.getElementById("totAll").textContent = Math.round(totPerf + totPersonal + totInit);
                    document.getElementById("upAll").textContent = Math.round(upPerf + upPersonal + upInit);

                    document.getElementById("sumTotal").textContent = Math.round(totalApplicable);
                    const pct = maxApplicable > 0 ? (totalApplicable / maxApplicable) * 100 : 0;
                    document.getElementById("percent").textContent = Math.round(pct);
                }

                document.addEventListener("input", (e) => {
                    if (e.target.matches(".max,.score,.upper,#includeInit")) recalc();
                });

                document.getElementById("includeInit").checked = false;
                recalc();
            </script>

        </div>


    </div>
    </div>
@endsection