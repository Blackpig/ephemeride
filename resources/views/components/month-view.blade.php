@props([
    'grid'            => [],
    'maxEventsPerDay' => 3,
    'targetContainer' => null,
])

@php
    $days  = $grid['days'] ?? [];
    $weeks = $grid['weeks'] ?? [];

    // Day name headers from the first 7 cells of the grid
    $dayNames = [];
    $count = 0;
    foreach ($days as $dayData) {
        if ($count >= 7) {
            break;
        }
        $dayNames[] = $dayData['date']->isoFormat('ddd');
        $count++;
    }

    // Flat list for slicing 7 days per week row
    $dayList = array_values($days);
@endphp

<div
    class="ephemeride-month-grid"
    role="grid"
    aria-label="Month calendar"
>
    {{-- Day name headers — placed in the outer 7-column grid --}}
    @foreach ($dayNames as $dayName)
        <div class="ephemeride-day-header" role="columnheader">
            {{ $dayName }}
        </div>
    @endforeach

    {{-- 6 week rows — each spans all 7 columns via grid-column: 1 / -1 --}}
    @foreach ($weeks as $weekIndex => $week)
        @php $weekDays = array_slice($dayList, $weekIndex * 7, 7); @endphp

        {{--
            .ephemeride-month-week-row is its own 7-column CSS grid.
            grid-row: 1 → multi-day banners (zero-height when empty)
            grid-row: 2 → day cells (date numbers + single-day events)
        --}}
        <div
            class="ephemeride-month-week-row"
            style="grid-column: 1 / -1;"
            role="row"
        >
            {{-- Multi-day spanning banners --}}
            @foreach ($week['multiDayPlacements'] as $placement)
                @php
                    $bannerEvent = $placement['event'];
                    $colStart    = $placement['colStart'];
                    $colSpan     = $placement['colSpan'];
                @endphp
                <div style="grid-column: {{ $colStart }} / span {{ $colSpan }}; grid-row: 1; padding: 1px 4px; min-width: 0; z-index: 1;">
                    @include('ephemeride::components.event-chip', ['event' => $bannerEvent, 'targetContainer' => $targetContainer ?? null])
                </div>
            @endforeach

            {{-- Day cells --}}
            @foreach ($weekDays as $colIndex => $dayData)
                @php
                    $cellClasses = 'ephemeride-day-cell';
                    if (! $dayData['inMonth']) $cellClasses .= ' is-outside-month';
                    if ($dayData['isToday'])   $cellClasses .= ' is-today';
                    if ($colIndex === 6)        $cellClasses .= ' is-last-day';

                    $events        = $dayData['events'];
                    $visibleEvents = array_slice($events, 0, $maxEventsPerDay);
                    $overflowCount = count($events) - count($visibleEvents);
                @endphp

                <div
                    class="{{ $cellClasses }}"
                    style="grid-column: {{ $colIndex + 1 }}; grid-row: 2;"
                    role="gridcell"
                    aria-label="{{ $dayData['date']->isoFormat('D MMMM YYYY') }}"
                >
                    <div class="ephemeride-day-number" aria-hidden="true">
                        {{ $dayData['date']->day }}
                    </div>

                    @foreach ($visibleEvents as $event)
                        @include('ephemeride::components.event-chip', ['event' => $event, 'targetContainer' => $targetContainer ?? null])
                    @endforeach

                    @if ($overflowCount > 0)
                        <div class="ephemeride-overflow-label">
                            +{{ $overflowCount }} more
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>
