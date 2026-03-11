<?php

namespace BlackpigCreatif\Ephemeride\Data;

use Carbon\Carbon;

/**
 * Immutable event DTO. All stored properties are individually readonly.
 *
 * Note: The class is declared `final` but NOT `readonly` because PHP 8.4
 * property hooks are incompatible with readonly properties — readonly classes
 * implicitly make every property readonly, which conflicts with the hooked
 * virtual properties below. Individual `public readonly` on each stored
 * property provides the same immutability guarantee.
 */
final class EphemerisEvent
{
    /**
     * Duration of the event in minutes.
     * Computed via PHP 8.4 property hook — no backing storage.
     */
    public int $durationInMinutes {
        get => (int) $this->startsAt->diffInMinutes($this->endsAt);
    }

    /**
     * Human-readable time range, e.g. "09:00 – 10:30".
     * Computed via PHP 8.4 property hook — no backing storage.
     */
    public string $formattedTimeRange {
        get => $this->startsAt->format('H:i').' – '.$this->endsAt->format('H:i');
    }

    /**
     * True when the event occupies a full day (both times at midnight).
     * Computed via PHP 8.4 property hook — no backing storage.
     */
    public bool $isAllDay {
        get => $this->startsAt->hour === 0
            && $this->startsAt->minute === 0
            && $this->endsAt->hour === 0
            && $this->endsAt->minute === 0;
    }

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly Carbon $startsAt,
        public readonly Carbon $endsAt,
        public readonly ?string $url = null,
        public readonly ?string $description = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $colour = null,
        public readonly ?string $category = null,
        public readonly ?string $rrule = null,
        public readonly array $exdates = [],
        public readonly array $rdates = [],
        public readonly array $extraAttributes = [],
    ) {}

    /**
     * Named constructor for fluent creation with named arguments.
     *
     * Usage:
     *   EphemerisEvent::make(
     *       id: 'class-42',
     *       title: 'Morning Yoga',
     *       startsAt: Carbon::parse('2026-03-10 09:00'),
     *       endsAt: Carbon::parse('2026-03-10 10:00'),
     *       colour: 'oklch(0.65 0.12 160)',
     *       category: 'Yoga',
     *       rrule: 'FREQ=WEEKLY;BYDAY=MO,WE,FR',
     *   )
     */
    public static function make(
        string $id,
        string $title,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $url = null,
        ?string $description = null,
        ?string $imageUrl = null,
        ?string $colour = null,
        ?string $category = null,
        ?string $rrule = null,
        array $exdates = [],
        array $rdates = [],
        array $extraAttributes = [],
    ): self {
        return new self(
            id: $id,
            title: $title,
            startsAt: $startsAt,
            endsAt: $endsAt,
            url: $url,
            description: $description,
            imageUrl: $imageUrl,
            colour: $colour,
            category: $category,
            rrule: $rrule,
            exdates: $exdates,
            rdates: $rdates,
            extraAttributes: $extraAttributes,
        );
    }

    /**
     * Create a new occurrence DTO at the given date, preserving all other metadata.
     *
     * Used by EventExpander when expanding recurring events — each occurrence
     * gets its own DTO with the correct startsAt/endsAt, and an id suffixed
     * with the occurrence date to ensure uniqueness across occurrences.
     */
    public function withDate(Carbon $date): self
    {
        $duration = $this->durationInMinutes;

        return new self(
            id: $this->id.'-'.$date->toDateString(),
            title: $this->title,
            startsAt: $date->copy(),
            endsAt: $date->copy()->addMinutes($duration),
            url: $this->url,
            description: $this->description,
            imageUrl: $this->imageUrl,
            colour: $this->colour,
            category: $this->category,
            rrule: null, // occurrences are not themselves recurring
            exdates: [],
            rdates: [],
            extraAttributes: $this->extraAttributes,
        );
    }
}
