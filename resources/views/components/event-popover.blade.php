@props(['event'])

<div class="ephemeride-popover">

    {{-- Title --}}
    <div class="ephemeride-popover-title">
        {{ $event->title }}
    </div>

    {{-- Date and time range --}}
    <div class="ephemeride-popover-meta">
        @if ($event->isAllDay)
            {{ $event->startsAt->isoFormat('D MMMM YYYY') }}
            @if (! $event->startsAt->isSameDay($event->endsAt))
                – {{ $event->endsAt->isoFormat('D MMMM YYYY') }}
            @endif
        @else
            {{ $event->startsAt->isoFormat('D MMMM YYYY') }} · {{ $event->formattedTimeRange }}
        @endif
    </div>

    {{-- Category badge --}}
    @if ($event->category)
        <div class="ephemeride-popover-category">
            {{ $event->category }}
        </div>
    @endif

    {{-- Thumbnail image --}}
    @if ($event->imageUrl)
        <img
            src="{{ $event->imageUrl }}"
            alt="{{ $event->title }}"
            class="ephemeride-popover-image"
            loading="lazy"
        >
    @endif

    {{-- Short description --}}
    @if ($event->description)
        <p class="ephemeride-popover-description">
            {{ Str::limit($event->description, 120) }}
        </p>
    @endif

    {{-- Extra attributes slot — consuming packages can override this view
         to render additional data (instructor name, spaces remaining, etc.)
         via the extraAttributes array on the DTO. --}}
    @if (! empty($event->extraAttributes))
        <div class="ephemeride-popover-extra" style="margin-bottom: 0.5rem;">
            {{-- Override the ephemeride::components.event-popover view to customise this section. --}}
        </div>
    @endif

    {{-- CTA button --}}
    @if ($event->url)
        <a
            href="{{ $event->url }}"
            class="ephemeride-popover-cta"
        >
            {{ config('ephemeride.popover_cta_label', 'View Details') }}
        </a>
    @endif

</div>
