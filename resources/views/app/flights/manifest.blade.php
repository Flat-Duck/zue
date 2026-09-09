@php
    use App\Models\Employee;
    use App\Models\FlightLeg;

    /*
     * Mirrors the sheet the company already uses at the airport: right-to-left,
     * A4 portrait, company header, then a numbered table and the two signature
     * blocks that get stamped.
     */
    $arabicDays = [
        'Saturday' => 'السبت',
        'Sunday' => 'الأحد',
        'Monday' => 'الإثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
    ];

    $flightDate = $flight->date ? \Carbon\Carbon::parse($flight->date) : null;
    $dayName = $flightDate ? ($arabicDays[$flightDate->format('l')] ?? '') : '';

    $isComing = $leg->direction === FlightLeg::DIRECTION_COMING;
    $movement = $isComing ? 'القادمون' : 'المغادرون';

    $from = $leg->fromStation?->displayNameAr() ?? '';
    $to = $leg->toStation?->displayNameAr() ?? '';

    // The official sheet keeps blank numbered rows so names can be added by hand.
    $minimumRows = 33;
    $blankRows = max(0, $minimumRows - $bookings->count());
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $movement }} — {{ $from }} / {{ $to }} — {{ $flightDate?->format('d-m-Y') }}</title>

    <style>
        @page { size: A4 portrait; margin: 10mm; }

        body {
            font-family: "Segoe UI", Tahoma, "Traditional Arabic", Arial, sans-serif;
            color: #000;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .sheet {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            padding: 10mm;
            box-sizing: border-box;
        }

        .toolbar {
            width: 210mm;
            margin: 12px auto 0;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .toolbar button, .toolbar a {
            font: inherit;
            padding: 8px 14px;
            border: 1px solid #206bc4;
            background: #206bc4;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .toolbar a.secondary { background: #fff; color: #206bc4; }

        .head { display: flex; align-items: center; justify-content: space-between; }
        .head img { height: 46px; }
        .head .titles { text-align: center; flex: 1; }
        .head .titles .company { font-weight: 700; letter-spacing: .5px; font-size: 15pt; }
        .head .titles .subject { font-size: 12pt; margin-top: 2px; }

        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11pt;
            margin: 8px 0 6px;
        }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }

        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 9.5pt;
            text-align: center;
            height: 19px;
            overflow: hidden;
            white-space: nowrap;
        }

        th { font-weight: 700; }
        td.name { text-align: right; }

        /* Column widths, right to left. */
        td.ltr { direction: ltr; unicode-bidi: bidi-override; }

        .c-seq { width: 6%; }
        .c-num { width: 12%; }
        .c-name { width: 26%; }
        .c-nat { width: 9%; }
        .c-comp { width: 14%; }
        .c-site { width: 11%; }
        .c-dept { width: 12%; }
        .c-dest { width: 10%; }

        .signatures { display: flex; justify-content: space-around; margin-top: 18mm; }
        .signatures div { text-align: center; font-size: 10pt; width: 60mm; }
        .signatures .line { border-top: 1px solid #000; margin-top: 16mm; padding-top: 4px; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="secondary" href="{{ route('flights.show', $flight) }}">رجوع</a>
        <button type="button" onclick="window.print()">طباعة / حفظ PDF</button>
    </div>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('img/zue-logo.png') }}" alt="Zueitina">
            <div class="titles">
                <div class="company">ZUEITINA OIL COMPANY</div>
                <div class="subject">
                    قائمة الركاب {{ $movement }} من {{ $from }} الى {{ $to }}
                </div>
            </div>
            <img src="{{ asset('img/zue-logo.png') }}" alt="Zueitina">
        </div>

        <div class="meta">
            <span>اليوم : {{ $dayName }}</span>
            <span>نوع الطائرة : {{ $flight->plane?->name ?? '' }}</span>
            <span>{{ $flightDate?->format('d/m/Y') }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="c-seq">ت</th>
                    <th class="c-num">الرقم</th>
                    <th class="c-name">الاســـم</th>
                    <th class="c-nat">الجنسية</th>
                    <th class="c-comp">الشركة</th>
                    <th class="c-site">الموقع</th>
                    <th class="c-dept">القسم</th>
                    <th class="c-dest">الجهة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $index => $booking)
                    @php
                        $traveller = $booking->bookable;
                        $isEmployee = $traveller instanceof Employee;

                        $number = $isEmployee ? $traveller->number : $traveller?->number;

                        // The sheet is Arabic, so prefer the Arabic name the
                        // personnel export provides and fall back to the
                        // English one only when it is missing.
                        $name = $isEmployee
                            ? ($traveller->arabic_name ?: $booking->travellerName())
                            : $booking->travellerName();
                        $nationality = $traveller?->nationality;
                        $company = $isEmployee ? 'الزويتينة' : $traveller?->company;
                        $site = $isEmployee
                            ? ($traveller->location?->arabic_name ?: $traveller->location?->name ?: $from)
                            : $from;
                        $department = $isEmployee
                            ? ($traveller->department?->arabic_name ?: $traveller->department?->name ?: '')
                            : 'مقاولات';
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="ltr">{{ $number ?: '' }}</td>
                        <td class="name">{{ $name }}</td>
                        <td>{{ $nationality ?: '' }}</td>
                        <td>{{ $company ?: '' }}</td>
                        <td>{{ $site }}</td>
                        <td>{{ $department }}</td>
                        <td>{{ $to }}</td>
                    </tr>
                @endforeach

                @for ($i = 0; $i < $blankRows; $i++)
                    <tr>
                        <td>{{ $bookings->count() + $i + 1 }}</td>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="signatures">
            <div><div class="line">مراقب حقول الانتصار 103</div></div>
            <div><div class="line">وحدة الحجز والترحيل — عمليات الطيران</div></div>
        </div>
    </div>
</body>
</html>
