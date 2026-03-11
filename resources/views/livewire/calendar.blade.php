<div
    class="ephemeride"
    @if ($themeStyle) style="{{ $themeStyle }}" @endif
    wire:key="ephemeride-calendar-{{ $year }}-{{ $month ?? '' }}-{{ $week ?? '' }}-{{ $view }}"
>
    {{-- Header: navigation + period label + view switcher --}}
    @include('ephemeride::components.view-switcher', [
        'views'         => $views,
        'currentView'   => $view,
        'year'          => $year,
        'month'         => $month,
        'week'          => $week,
    ])

    {{-- Category filter bar --}}
    @if ($filterable && count($this->categories))
        <div class="ephemeride-filter-bar" role="toolbar" aria-label="Filter by category">
            <button
                type="button"
                wire:click="filterCategory(null)"
                class="ephemeride-filter-chip {{ $activeCategory === null ? 'is-active' : '' }}"
                aria-pressed="{{ $activeCategory === null ? 'true' : 'false' }}"
            >
                All
            </button>

            @foreach ($this->categories as $cat)
                <button
                    type="button"
                    wire:click="filterCategory('{{ $cat }}')"
                    class="ephemeride-filter-chip {{ $activeCategory === $cat ? 'is-active' : '' }}"
                    aria-pressed="{{ $activeCategory === $cat ? 'true' : 'false' }}"
                >
                    {{ $cat }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Calendar body --}}
    @if ($view === 'month')
        @include('ephemeride::components.month-view', [
            'grid'             => $this->grid,
            'maxEventsPerDay'  => config('ephemeride.month_max_events_per_day', 3),
        ])
    @elseif ($view === 'week')
        @include('ephemeride::components.week-view', [
            'grid' => $this->grid,
        ])
    @endif
</div>
