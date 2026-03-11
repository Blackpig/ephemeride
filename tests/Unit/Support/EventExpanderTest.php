<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use BlackpigCreatif\Ephemeride\Support\EventExpander;
use Carbon\Carbon;

describe('EventExpander', function () {

    beforeEach(function () {
        $this->expander = new EventExpander;
        $this->from     = Carbon::parse('2026-03-01 00:00:00');
        $this->to       = Carbon::parse('2026-03-31 23:59:59');
    });

    it('includes a one-off event within the window', function () {
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'One-off',
            startsAt: Carbon::parse('2026-03-15 09:00'),
            endsAt: Carbon::parse('2026-03-15 10:00'),
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe('e1');
    });

    it('excludes a one-off event outside the window', function () {
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Outside',
            startsAt: Carbon::parse('2026-04-15 09:00'),
            endsAt: Carbon::parse('2026-04-15 10:00'),
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        expect($result)->toHaveCount(0);
    });

    it('expands a weekly recurring event to correct occurrences', function () {
        // Weekly on Tuesday — should produce 4 Tuesdays in March 2026
        // Tuesdays in March 2026: 3, 10, 17, 24, 31 = 5
        $event = EphemerisEvent::make(
            id: 'weekly-tue',
            title: 'Tuesday Yoga',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        expect($result->count())->toBe(5);

        $dates = $result->pluck('startsAt')->map(fn ($d) => $d->toDateString())->all();
        expect($dates)->toContain('2026-03-03')
            ->toContain('2026-03-10')
            ->toContain('2026-03-17')
            ->toContain('2026-03-24')
            ->toContain('2026-03-31');
    });

    it('each expanded occurrence has a unique id', function () {
        $event = EphemerisEvent::make(
            id: 'class',
            title: 'Yoga',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        $ids = $result->pluck('id')->all();
        expect(array_unique($ids))->toHaveCount(count($ids));
    });

    it('EXDATE removes a specific occurrence', function () {
        // Weekly Tuesday, but exclude 10th March
        $event = EphemerisEvent::make(
            id: 'weekly-tue',
            title: 'Tuesday Yoga',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
            exdates: [Carbon::parse('2026-03-10 09:00')],
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        $dates = $result->pluck('startsAt')->map(fn ($d) => $d->toDateString())->all();
        expect($dates)->not->toContain('2026-03-10')
            ->toContain('2026-03-03')
            ->toContain('2026-03-17');
    });

    it('RDATE adds a one-off occurrence to a recurring event', function () {
        // Weekly Tuesday + one additional Monday
        $event = EphemerisEvent::make(
            id: 'weekly-tue',
            title: 'Tuesday Yoga',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
            rdates: [Carbon::parse('2026-03-16 09:00')], // a Monday
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        $dates = $result->pluck('startsAt')->map(fn ($d) => $d->toDateString())->all();
        expect($dates)->toContain('2026-03-16'); // the added Monday
    });

    it('handles a mixed collection of one-off and recurring events', function () {
        $oneOff = EphemerisEvent::make(
            id: 'one-off',
            title: 'One Off',
            startsAt: Carbon::parse('2026-03-20 11:00'),
            endsAt: Carbon::parse('2026-03-20 12:00'),
        );

        $recurring = EphemerisEvent::make(
            id: 'recurring',
            title: 'Weekly',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
        );

        $result = $this->expander->expand(collect([$oneOff, $recurring]), $this->from, $this->to);

        // 5 Tuesdays + 1 one-off = 6
        expect($result->count())->toBe(6);
    });

    it('preserves event metadata on each occurrence', function () {
        $event = EphemerisEvent::make(
            id: 'class',
            title: 'Yoga',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
            colour: 'oklch(0.65 0.12 160)',
            category: 'Yoga',
            url: 'https://example.com',
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
        );

        $result = $this->expander->expand(collect([$event]), $this->from, $this->to);

        $occurrence = $result->first();
        expect($occurrence->colour)->toBe('oklch(0.65 0.12 160)')
            ->and($occurrence->category)->toBe('Yoga')
            ->and($occurrence->url)->toBe('https://example.com');
    });

});
