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

    @vite('resources/sass/manifest.scss')
</head>
<body>
    <div class="toolbar">
        <a class="secondary" href="{{ route('flights.show', $flight) }}">{{ __('flights.back', [], 'ar') }}</a>
        <button type="button" onclick="window.print()">{{ __('flights.print_or_save_pdf', [], 'ar') }}</button>
    </div>

    <div class="sheet">
        <div class="head">
            <img src="{{ asset('img/zue-logo.png') }}" alt="{{ __('flights.manifest_company_header', [], 'ar') }}">
            <div class="titles">
                <div class="company">{{ __('flights.manifest_company_header', [], 'ar') }}</div>
                <div class="subject">
                    {{ __('flights.manifest_title', ['movement' => $movement, 'from' => $from, 'to' => $to], 'ar') }}
                </div>
            </div>
            <img src="{{ asset('img/zue-logo.png') }}" alt="{{ __('flights.manifest_company_header', [], 'ar') }}">
        </div>

        <div class="meta">
            <span>{{ __('flights.day_label', ['day' => $dayName], 'ar') }}</span>
            <span>{{ __('flights.plane_type_label', ['plane' => $flight->plane?->name ?? ''], 'ar') }}</span>
            <span>{{ $flightDate?->format('d/m/Y') }}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="c-seq">{{ __('flights.manifest_sequence', [], 'ar') }}</th>
                    <th class="c-num">{{ __('flights.manifest_number', [], 'ar') }}</th>
                    <th class="c-name">{{ __('flights.manifest_name', [], 'ar') }}</th>
                    <th class="c-nat">{{ __('flights.manifest_nationality', [], 'ar') }}</th>
                    <th class="c-comp">{{ __('flights.manifest_company', [], 'ar') }}</th>
                    <th class="c-site">{{ __('flights.manifest_location', [], 'ar') }}</th>
                    <th class="c-dept">{{ __('flights.manifest_department', [], 'ar') }}</th>
                    <th class="c-dest">{{ __('flights.manifest_destination', [], 'ar') }}</th>
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
            <div><div class="line">{{ __('flights.field_superintendent', [], 'ar') }}</div></div>
            <div><div class="line">{{ __('flights.dispatch_unit', [], 'ar') }}</div></div>
        </div>
    </div>
</body>
</html>
