{{--
    Family Morning Dashboard — 960x680 13-inch e-ink (1-bit)
    4 columns (one per family member) + bottom bar (weather + date)

    Polling URLs (one per line in recipe config):
      https://api.brightsky.dev/current_weather?lat={{lat}}&lon={{lon}}&tz={{tz}}
      {{p1_ics_1}}
      {{p1_ics_2}}
      {{p1_ics_3}}
      {{p2_ics_1}}
      {{p2_ics_2}}
      {{p2_ics_3}}
      {{p3_ics_1}}
      {{p3_ics_2}}
      {{p3_ics_3}}
      {{p4_ics_1}}
      {{p4_ics_2}}
      {{p4_ics_3}}

    Empty ICS lines (kids, unused slots) are stripped by Laravel's array_filter.
    IDX_0 = weather, IDX_1..N = calendars in person-slot order (up to 3 per adult).
--}}
@props(['size' => 'full'])
@php
    use Carbon\Carbon;

    $tz = $config['tz'] ?? 'Europe/Berlin';
    $now = Carbon::now($tz);
    $today = $now->copy()->startOfDay();
    $dayName = strtolower($now->format('l'));

    // German day and month names
    $germanDays = [
        'monday' => 'Montag', 'tuesday' => 'Dienstag', 'wednesday' => 'Mittwoch',
        'thursday' => 'Donnerstag', 'friday' => 'Freitag', 'saturday' => 'Samstag', 'sunday' => 'Sonntag',
    ];
    $germanMonths = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];

    // --- Weather (IDX_0) ---
    $weather = Arr::get($data, 'IDX_0.weather', []);
    $temp = isset($weather['temperature']) ? round($weather['temperature']) : null;
    $weatherIcon = $weather['icon'] ?? '';

    $iconLabels = [
        'clear-day' => 'Klar',
        'clear-night' => 'Klar',
        'partly-cloudy-day' => 'Teils bewölkt',
        'partly-cloudy-night' => 'Teils bewölkt',
        'cloudy' => 'Bewölkt',
        'fog' => 'Nebel',
        'wind' => 'Windig',
        'rain' => 'Regen',
        'sleet' => 'Schneeregen',
        'snow' => 'Schnee',
        'hail' => 'Hagel',
        'thunderstorm' => 'Gewitter',
    ];
    $weatherLabel = $iconLabels[$weatherIcon] ?? ucfirst($weather['condition'] ?? '');

    // Inline SVG fragments for weather icons (24x24 viewBox, 1-bit friendly)
    $svgPaths = [
        'clear-day' =>
            '<circle cx="12" cy="12" r="4" fill="black"/>'
            . '<g stroke="black" stroke-width="2" stroke-linecap="round">'
            . '<line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/>'
            . '<line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/>'
            . '<line x1="4.93" y1="4.93" x2="6.76" y2="6.76"/>'
            . '<line x1="17.24" y1="17.24" x2="19.07" y2="19.07"/>'
            . '<line x1="4.93" y1="19.07" x2="6.76" y2="17.24"/>'
            . '<line x1="17.24" y1="6.76" x2="19.07" y2="4.93"/>'
            . '</g>',

        'clear-night' =>
            '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" fill="black"/>',

        'partly-cloudy-day' =>
            '<circle cx="9" cy="6" r="3" fill="black"/>'
            . '<g stroke="black" stroke-width="1.5" stroke-linecap="round">'
            . '<line x1="9" y1="0.5" x2="9" y2="2"/>'
            . '<line x1="3.5" y1="6" x2="5" y2="6"/>'
            . '<line x1="5.46" y1="2.46" x2="6.52" y2="3.52"/>'
            . '<line x1="5.46" y1="9.54" x2="6.52" y2="8.48"/>'
            . '</g>'
            . '<path d="M20 20H9a5 5 0 0 1-1-9.9 6.5 6.5 0 0 1 12.13 2.15A4 4 0 0 1 20 20z" fill="black"/>',

        'partly-cloudy-night' =>
            '<path d="M10 2a5 5 0 1 0 0 8 4 4 0 0 1 0-8z" fill="black"/>'
            . '<path d="M20 20H9a5 5 0 0 1-1-9.9 6.5 6.5 0 0 1 12.13 2.15A4 4 0 0 1 20 20z" fill="black"/>',

        'cloudy' =>
            '<path d="M20 18H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 11H20a4 4 0 0 1 0 7z" fill="black"/>',

        'fog' =>
            '<path d="M18 10H8a3.5 3.5 0 0 1-.68-6.93A5 5 0 0 1 17 4a3 3 0 0 1 1 6z" fill="black"/>'
            . '<g stroke="black" stroke-width="2" stroke-linecap="round">'
            . '<line x1="3" y1="14" x2="21" y2="14"/>'
            . '<line x1="4" y1="18" x2="20" y2="18"/>'
            . '<line x1="6" y1="22" x2="18" y2="22"/>'
            . '</g>',

        'wind' =>
            '<g stroke="black" stroke-width="2" stroke-linecap="round" fill="none">'
            . '<path d="M9.59 4.59A2 2 0 1 1 11 8H2"/>'
            . '<path d="M12.59 19.41A2 2 0 1 0 14 16H2"/>'
            . '<path d="M17.73 7.73A2.5 2.5 0 1 1 19.5 12H2"/>'
            . '</g>',

        'rain' =>
            '<path d="M20 14H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 7H20a4 4 0 0 1 0 7z" fill="black"/>'
            . '<g stroke="black" stroke-width="2" stroke-linecap="round">'
            . '<line x1="8" y1="17" x2="8" y2="21"/>'
            . '<line x1="12" y1="17" x2="12" y2="21"/>'
            . '<line x1="16" y1="17" x2="16" y2="21"/>'
            . '</g>',

        'sleet' =>
            '<path d="M20 14H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 7H20a4 4 0 0 1 0 7z" fill="black"/>'
            . '<g stroke="black" stroke-width="2" stroke-linecap="round">'
            . '<line x1="8" y1="17" x2="8" y2="20"/>'
            . '<line x1="16" y1="17" x2="16" y2="20"/>'
            . '</g>'
            . '<circle cx="12" cy="19" r="1.5" fill="black"/>',

        'snow' =>
            '<path d="M20 14H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 7H20a4 4 0 0 1 0 7z" fill="black"/>'
            . '<g fill="black"><circle cx="8" cy="19" r="1.5"/><circle cx="12" cy="19" r="1.5"/><circle cx="16" cy="19" r="1.5"/></g>',

        'hail' =>
            '<path d="M20 14H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 7H20a4 4 0 0 1 0 7z" fill="black"/>'
            . '<g fill="black"><circle cx="8" cy="19" r="2"/><circle cx="13" cy="18" r="2"/><circle cx="17" cy="20" r="2"/></g>',

        'thunderstorm' =>
            '<path d="M20 14H8a5 5 0 0 1-1-9.9A6.5 6.5 0 0 1 19.5 7H20a4 4 0 0 1 0 7z" fill="black"/>'
            . '<polygon points="13,15 10,20 12,20 11,24 16,18 13,18 15,15" fill="black"/>',
    ];
    $weatherSvg = $svgPaths[$weatherIcon] ?? '';

    // Parse a single iCal IDX into events from today onward
    $parseIcal = function (int $idx) use ($data, $tz, $today) {
        return collect(Arr::get($data, "IDX_{$idx}.ical", []))
            ->map(function ($e) use ($tz) {
                try {
                    $start = isset($e['DTSTART'])
                        ? Carbon::parse($e['DTSTART'])->setTimezone($tz)
                        : null;
                } catch (\Exception $_) {
                    $start = null;
                }
                try {
                    $end = isset($e['DTEND'])
                        ? Carbon::parse($e['DTEND'])->setTimezone($tz)
                        : null;
                } catch (\Exception $_) {
                    $end = null;
                }
                return [
                    'summary' => $e['SUMMARY'] ?? 'Untitled',
                    'start'   => $start,
                    'end'     => $end,
                    'all_day' => isset($e['DTSTART']) && strlen($e['DTSTART']) <= 10,
                ];
            })
            ->filter(fn ($e) => $e['start'] && $e['start']->greaterThanOrEqualTo($today));
    };

    // --- People (p1–p4), up to 3 ICS calendars per adult ---
    $people = [];
    $icsIdx = 1; // IDX_0 = weather, IDX_1+ = calendars
    foreach (range(1, 4) as $i) {
        $name = $config["p{$i}_name"] ?? '';
        if (empty($name)) continue;

        $type = $config["p{$i}_type"] ?? 'adult';
        $person = ['name' => $name, 'type' => $type];

        if ($type === 'kid') {
            $tt = json_decode($config["p{$i}_timetable"] ?? '{}', true) ?: [];
            $todaySlots = $tt[$dayName] ?? [];

            // After 16:00 or no classes today, show the next school day
            if ($now->hour >= 16 || empty($todaySlots)) {
                $person['timetable_day'] = null;
                $check = $now->copy()->addDay();
                for ($d = 0; $d < 7; $d++) {
                    $checkDay = strtolower($check->format('l'));
                    if (!empty($tt[$checkDay] ?? [])) {
                        $person['slots'] = $tt[$checkDay];
                        $person['timetable_day'] = $germanDays[$checkDay];
                        break;
                    }
                    $check->addDay();
                }
                if ($person['timetable_day'] === null) {
                    $person['slots'] = [];
                    $person['timetable_day'] = '';
                }
            } else {
                $person['slots'] = $todaySlots;
                $person['timetable_day'] = $germanDays[$dayName] ?? '';
            }
        } else {
            // Merge events from up to 3 calendar feeds per adult
            $merged = collect();
            foreach (range(1, 3) as $c) {
                if (filled($config["p{$i}_ics_{$c}"] ?? '')) {
                    $merged = $merged->merge($parseIcal($icsIdx));
                    $icsIdx++;
                }
            }
            $allEvents = $merged->sortBy('start')->values();
            $todayEvents = $allEvents->filter(fn ($e) => $e['start']->isSameDay($today));

            if ($todayEvents->isNotEmpty()) {
                $person['events'] = $todayEvents->values();
                $person['next_day'] = null;
            } else {
                $future = $allEvents->filter(fn ($e) => $e['start']->greaterThan($today->copy()->endOfDay()));
                if ($future->isNotEmpty()) {
                    $nextDay = $future->first()['start']->copy()->startOfDay();
                    $daysUntil = (int) $today->diffInDays($nextDay);
                    $person['events'] = $future->filter(fn ($e) => $e['start']->isSameDay($nextDay))->values();
                    $person['next_day'] = $nextDay;
                    $person['days_until'] = $daysUntil;
                } else {
                    $person['events'] = collect();
                    $person['next_day'] = null;
                }
            }
        }

        $people[] = $person;
    }

    // Trim period range: skip leading/trailing slots where no kid has a class
    $kids = collect($people)->filter(fn ($p) => $p['type'] === 'kid');
    $periodStart = 0;
    $periodEnd = 7;
    // Trim from start
    while ($periodStart <= $periodEnd && !$kids->contains(fn ($p) => filled($p['slots'][$periodStart] ?? ''))) {
        $periodStart++;
    }
    // Trim from end
    while ($periodEnd >= $periodStart && !$kids->contains(fn ($p) => filled($p['slots'][$periodEnd] ?? ''))) {
        $periodEnd--;
    }
    $periodCount = max($periodEnd - $periodStart + 1, 1);
@endphp

<x-trmnl::view size="{{ $size }}">
    <style>
        .fd {
            display: flex;
            flex-direction: column;
            height: 100%;
            font-family: 'Inter', sans-serif;
            letter-spacing: -0.01em;
        }
        .fd-columns {
            display: flex;
            flex: 1;
            min-height: 0;
            gap: 0;
        }
        .fd-col {
            flex: 1;
            border-right: 1px solid black;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .fd-col:last-child {
            border-right: none;
        }
        .fd-name {
            background: black;
            color: white;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: center;
        }
        .fd-body {
            padding: 8px 12px;
            flex: 1;
            overflow: hidden;
        }
        .fd-body:has(.fd-timetable) {
            padding: 0;
        }

        /* Kid timetable — day header + period rows filling the column */
        .fd-timetable {
            display: grid;
            grid-template-rows: auto repeat({{ $periodCount }}, 1fr);
            height: 100%;
        }
        .fd-tt-day {
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 6px 0;
            border-bottom: 1px solid black;
        }
        .fd-slot {
            font-size: 15px;
            border-bottom: 1px solid black;
            display: flex;
            align-items: center;
            padding: 0 12px;
        }
        .fd-slot:last-child {
            border-bottom: none;
        }
        .fd-slot-num {
            width: 28px;
            font-size: 13px;
            font-weight: 400;
            flex-shrink: 0;
        }
        .fd-slot-subject {
            font-weight: 400;
        }

        /* Adult events */
        .fd-event {
            padding: 7px 0;
            border-bottom: 1px solid black;
        }
        .fd-event:last-child {
            border-bottom: none;
        }
        .fd-event-time {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }
        .fd-event-summary {
            font-size: 15px;
            font-weight: 400;
            margin-top: 2px;
            line-height: 1.3;
        }

        .fd-empty {
            font-size: 14px;
            font-weight: 400;
            padding-top: 12px;
        }
        .fd-next-day {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 8px 0 6px;
            border-bottom: 1px solid black;
            margin-bottom: 2px;
        }

        /* Bottom bar */
        .fd-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            border-top: 2px solid black;
        }
        .fd-weather {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fd-temp {
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        .fd-condition {
            font-size: 14px;
            font-weight: 400;
        }
        .fd-date {
            font-size: 14px;
            font-weight: 500;
        }
    </style>

    <div class="fd">
        <div class="fd-columns">
            @foreach ($people as $person)
                <div class="fd-col">
                    <div class="fd-name">{{ $person['name'] }}</div>
                    <div class="fd-body">
                        @if ($person['type'] === 'kid')
                            <div class="fd-timetable">
                                <div class="fd-tt-day">{{ $person['timetable_day'] }}</div>
                                @for ($idx = $periodStart; $idx <= $periodEnd; $idx++)
                                    <div class="fd-slot">
                                        <span class="fd-slot-num">{{ $idx }}.</span>
                                        <span class="fd-slot-subject">{{ $person['slots'][$idx] ?? '' }}</span>
                                    </div>
                                @endfor
                            </div>
                        @else
                            @if (!empty($person['next_day']))
                                <div class="fd-next-day">
                                    @if ($person['days_until'] === 1)
                                        Morgen
                                    @elseif ($person['days_until'] === 2)
                                        Übermorgen
                                    @else
                                        In {{ $person['days_until'] }} Tagen ({{ $person['next_day']->format('d.m.') }})
                                    @endif
                                </div>
                            @endif
                            @forelse ($person['events'] as $event)
                                <div class="fd-event">
                                    <div class="fd-event-time">
                                        @if ($event['all_day'])
                                            Ganztägig
                                        @else
                                            {{ $event['start']->format('H:i') }}@if ($event['end'])–{{ $event['end']->format('H:i') }}@endif
                                        @endif
                                    </div>
                                    <div class="fd-event-summary">{{ Str::limit($event['summary'], 28) }}</div>
                                </div>
                            @empty
                                <div class="fd-empty">Keine Termine</div>
                            @endforelse
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fd-bar">
            <div class="fd-weather">
                @if ($weatherSvg)
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">{!! $weatherSvg !!}</svg>
                @endif
                @if ($temp !== null)
                    <span class="fd-temp">{{ $temp }}°</span>
                @endif
                @if ($weatherLabel)
                    <span class="fd-condition">{{ $weatherLabel }}</span>
                @endif
            </div>
            <span class="fd-date">{{ $germanDays[$dayName] }}, {{ $now->day }}. {{ $germanMonths[$now->month - 1] }}</span>
        </div>
    </div>
</x-trmnl::view>
