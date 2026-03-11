@props(['grid'])

@php
    $days        = $grid['days'];
    $slots       = $grid['slots'];
    $allDayEvents = $grid['allDayEvents'];
    $timedEvents  = $grid['timedEvents'];
    $today        = \Carbon\Carbon::today();

    // Total number of slot rows + 1 (the all-day row)
    $totalRows = count($slots) + 1;
@endphp

<div class="ephemeride-week-wrapper" role="grid" aria-label="Week calendar">

    {{-- Sticky day header row --}}
    <div class="ephemeride-week-day-headers" role="row">
        {{-- Corner cell (above time labels column) --}}
        <div style="border-right: 1px solid var(--ephemeride-color-border);" role="columnheader" aria-label="Time"></div>

        @foreach ($days as $day)
            @php
                $isToday = $day->isSameDay($today);
                $headerClass = 'ephemeride-week-day-header-cell' . ($isToday ? ' is-today' : '');
            @endphp
            <div class="{{ $headerClass }}" role="columnheader">
                <div>{{ $day->isoFormat('ddd') }}</div>
                <div class="{{ $isToday ? 'ephemeride-week-day-number' : '' }}">
                    {{ $day->day }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- All-day events row --}}
    @if (count($allDayEvents) > 0 || true)
        <div class="ephemeride-week-allday-row" role="row">
            <div class="ephemeride-week-allday-label" role="rowheader">All day</div>

            {{-- Day background cells — purely visual (borders + today highlight) --}}
            @foreach ($days as $day)
                <div class="ephemeride-week-allday-cell{{ $day->isToday() ? ' is-today' : '' }}"
                     role="gridcell"
                     aria-label="{{ $day->isoFormat('D MMMM') }}">
                </div>
            @endforeach

            {{-- All-day event chips as direct grid children so they can span multiple day columns --}}
            @foreach ($allDayEvents as $placement)
                @php
                    $event    = $placement['event'];
                    $colStart = $placement['colStart'];
                    $colSpan  = $placement['colSpan'];
                @endphp
                <div style="grid-column: {{ $colStart }} / span {{ $colSpan }}; grid-row: 1; padding: 2px 4px; min-width: 0; z-index: 1;">
                    @include('ephemeride::components.event-chip', ['event' => $event])
                </div>
            @endforeach
        </div>
    @endif

    {{-- Time grid (CSS Grid — NOT a table) --}}
    {{--
        Grid layout:
          Column 1 (1): time labels
          Columns 2–8 (2 to 8): day columns (Mon–Sun)
          Row 1: reserved (the all-day band is rendered above)
          Rows 2+: time slot rows
    --}}
    <div
        class="ephemeride-week-grid"
        style="grid-template-rows: repeat({{ count($slots) }}, 3rem);"
        role="presentation"
    >
        {{-- Time label cells (column 1) --}}
        @foreach ($slots as $slot)
            <div
                class="ephemeride-week-time-label"
                style="grid-column: 1; grid-row: {{ $loop->iteration }};"
                aria-hidden="true"
            >
                {{ $slot['label'] }}
            </div>
        @endforeach

        {{-- Background slot cells for each day column (columns 2–8) --}}
        @foreach ($days as $dayIndex => $day)
            @php $col = $dayIndex + 2; @endphp
            @foreach ($slots as $slot)
                <div
                    class="ephemeride-week-slot-cell{{ $day->isToday() ? ' is-today' : '' }}{{ $loop->parent->last ? ' is-last-day' : '' }}"
                    style="grid-column: {{ $col }}; grid-row: {{ $loop->iteration }};"
                    role="gridcell"
                    aria-label="{{ $day->isoFormat('D MMMM') }} {{ $slot['label'] }}"
                ></div>
            @endforeach
        @endforeach

        {{-- Positioned event blocks --}}
        @foreach ($timedEvents as $placement)
            @php
                $event    = $placement['event'];
                $col      = $placement['col']; // PHP already adds +1 for the time-label column
                $rowStart = $placement['rowStart'];
                $rowSpan  = $placement['rowSpan'];
                $chipStyle = $event->colour ? "--ephemeride-color-event: {$event->colour};" : '';
            @endphp

            <div
                x-data="{ open: false }"
                style="grid-column: {{ $col }}; grid-row: {{ $rowStart }} / span {{ $rowSpan }}; position: relative; padding: 0 2px;"
            >
                <button
                    type="button"
                    class="ephemeride-week-event"
                    @if ($chipStyle) style="width: 100%; {{ $chipStyle }}" @else style="width: 100%;" @endif
                    @click="open = !open"
                    @keydown.escape="open = false"
                    aria-haspopup="dialog"
                    :aria-expanded="open"
                    title="{{ $event->title }}"
                >
                    <div style="font-weight: 500; line-height: 1.3;">{{ $event->title }}</div>
                    @if ($rowSpan > 1)
                        <div style="font-size: 0.6875rem; opacity: 0.85;">{{ $event->formattedTimeRange }}</div>
                    @endif
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="open = false"
                    @keydown.escape.window="open = false"
                    style="display: none; position: absolute; top: 100%; left: 0; z-index: 50;"
                    role="dialog"
                    aria-modal="false"
                    aria-label="{{ $event->title }}"
                >
                    @include('ephemeride::components.event-popover', ['event' => $event])
                </div>
            </div>
        @endforeach
    </div>
</div>
