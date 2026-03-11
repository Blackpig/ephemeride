@props(['event'])

@php
    // Per-event colour override cascades over --ephemeride-color-event
    $chipStyle = $event->colour
        ? "--ephemeride-color-event: {$event->colour};"
        : '';
@endphp

<div
    x-data="{ open: false }"
    style="position: relative;"
>
    {{-- The chip itself --}}
    <button
        type="button"
        class="ephemeride-event-chip"
        @if ($chipStyle) style="{{ $chipStyle }}" @endif
        @click="open = !open"
        @keydown.escape="open = false"
        aria-haspopup="dialog"
        :aria-expanded="open"
        title="{{ $event->title }}"
    >
        @unless ($event->isAllDay)
            <span style="opacity: 0.85; font-size: 0.6875rem; margin-right: 0.25rem;">
                {{ $event->startsAt->format('H:i') }}
            </span>
        @endunless
        <span>{{ $event->title }}</span>
    </button>

    {{-- Popover card --}}
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
        style="display: none;"
        role="dialog"
        aria-modal="false"
        aria-label="{{ $event->title }}"
    >
        @include('ephemeride::components.event-popover', ['event' => $event])
    </div>
</div>
