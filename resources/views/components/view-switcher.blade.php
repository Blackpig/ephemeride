@props([
    'views'       => ['month', 'week'],
    'currentView' => 'month',
    'year'        => null,
    'month'       => null,
    'week'        => null,
])

@php
    // Build the human-readable period label
    if ($currentView === 'month' && $year && $month) {
        $periodLabel = \Carbon\Carbon::create($year, $month, 1)->isoFormat('MMMM YYYY');
    } elseif ($currentView === 'week' && $year && $week) {
        $weekStart = \Carbon\Carbon::now()->setISODate($year, $week)->startOfWeek();
        $weekEnd   = $weekStart->copy()->endOfWeek();
        $periodLabel = $weekStart->isoFormat('D MMM') . ' – ' . $weekEnd->isoFormat('D MMM YYYY');
    } else {
        $periodLabel = '';
    }
@endphp

<div class="ephemeride-header">
    {{-- Navigation --}}
    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <button
            type="button"
            wire:click="previousPeriod"
            class="ephemeride-nav-btn"
            aria-label="Previous period"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>

        <button
            type="button"
            wire:click="goToToday"
            class="ephemeride-nav-btn"
            style="font-size: 0.8125rem; font-weight: 500;"
        >
            Today
        </button>

        <button
            type="button"
            wire:click="nextPeriod"
            class="ephemeride-nav-btn"
            aria-label="Next period"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>
    </div>

    {{-- Period label --}}
    <span class="ephemeride-period-label" aria-live="polite" aria-atomic="true">
        {{ $periodLabel }}
    </span>

    {{-- View switcher (hidden when only one view is configured) --}}
    @if (count($views) > 1)
        <div class="ephemeride-view-switcher" role="group" aria-label="Calendar view">
            @foreach ($views as $v)
                <button
                    type="button"
                    wire:click="switchView('{{ $v }}')"
                    class="ephemeride-view-btn {{ $currentView === $v ? 'is-active' : '' }}"
                    aria-pressed="{{ $currentView === $v ? 'true' : 'false' }}"
                >
                    {{ ucfirst($v) }}
                </button>
            @endforeach
        </div>
    @endif
</div>
