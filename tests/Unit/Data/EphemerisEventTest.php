<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;

describe('EphemerisEvent DTO', function () {

    it('constructs with required fields', function () {
        $startsAt = Carbon::parse('2026-03-10 09:00');
        $endsAt   = Carbon::parse('2026-03-10 10:00');

        $event = new EphemerisEvent(
            id: 'test-1',
            title: 'Morning Yoga',
            startsAt: $startsAt,
            endsAt: $endsAt,
        );

        expect($event->id)->toBe('test-1')
            ->and($event->title)->toBe('Morning Yoga')
            ->and($event->startsAt->toDateTimeString())->toBe('2026-03-10 09:00:00')
            ->and($event->endsAt->toDateTimeString())->toBe('2026-03-10 10:00:00');
    });

    it('has correct optional field defaults', function () {
        $event = new EphemerisEvent(
            id: 'test-1',
            title: 'Test',
            startsAt: Carbon::now(),
            endsAt: Carbon::now()->addHour(),
        );

        expect($event->url)->toBeNull()
            ->and($event->description)->toBeNull()
            ->and($event->imageUrl)->toBeNull()
            ->and($event->colour)->toBeNull()
            ->and($event->category)->toBeNull()
            ->and($event->rrule)->toBeNull()
            ->and($event->exdates)->toBe([])
            ->and($event->rdates)->toBe([])
            ->and($event->extraAttributes)->toBe([]);
    });

    it('creates via static make() factory with named arguments', function () {
        $event = EphemerisEvent::make(
            id: 'class-42',
            title: 'Evening Yin',
            startsAt: Carbon::parse('2026-04-01 19:00'),
            endsAt: Carbon::parse('2026-04-01 20:30'),
            colour: 'oklch(0.65 0.12 160)',
            category: 'Yin Yoga',
            url: 'https://example.com/classes/42',
        );

        expect($event->id)->toBe('class-42')
            ->and($event->title)->toBe('Evening Yin')
            ->and($event->colour)->toBe('oklch(0.65 0.12 160)')
            ->and($event->category)->toBe('Yin Yoga')
            ->and($event->url)->toBe('https://example.com/classes/42');
    });

    it('computes durationInMinutes via property hook', function () {
        $event = EphemerisEvent::make(
            id: 'test',
            title: 'Test',
            startsAt: Carbon::parse('2026-03-10 09:00'),
            endsAt: Carbon::parse('2026-03-10 10:30'),
        );

        expect($event->durationInMinutes)->toBe(90);
    });

    it('computes formattedTimeRange via property hook', function () {
        $event = EphemerisEvent::make(
            id: 'test',
            title: 'Test',
            startsAt: Carbon::parse('2026-03-10 09:00'),
            endsAt: Carbon::parse('2026-03-10 10:30'),
        );

        expect($event->formattedTimeRange)->toBe('09:00 – 10:30');
    });

    it('detects all-day events via isAllDay property hook', function () {
        $allDay = EphemerisEvent::make(
            id: 'all-day',
            title: 'Retreat Day',
            startsAt: Carbon::parse('2026-06-01 00:00'),
            endsAt: Carbon::parse('2026-06-02 00:00'),
        );

        $timed = EphemerisEvent::make(
            id: 'timed',
            title: 'Morning Class',
            startsAt: Carbon::parse('2026-06-01 09:00'),
            endsAt: Carbon::parse('2026-06-01 10:00'),
        );

        expect($allDay->isAllDay)->toBeTrue()
            ->and($timed->isAllDay)->toBeFalse();
    });

    it('creates an occurrence clone via withDate()', function () {
        $original = EphemerisEvent::make(
            id: 'class-1',
            title: 'Yoga',
            startsAt: Carbon::parse('2026-03-10 09:00'),
            endsAt: Carbon::parse('2026-03-10 10:00'),
            colour: 'oklch(0.55 0.2 270)',
            category: 'Yoga',
            rrule: 'FREQ=WEEKLY;BYDAY=TU',
            extraAttributes: ['instructor' => 'Jane'],
        );

        $occurrence = $original->withDate(Carbon::parse('2026-03-17 09:00'));

        expect($occurrence->id)->toBe('class-1-2026-03-17')
            ->and($occurrence->title)->toBe('Yoga')
            ->and($occurrence->startsAt->toDateTimeString())->toBe('2026-03-17 09:00:00')
            ->and($occurrence->endsAt->toDateTimeString())->toBe('2026-03-17 10:00:00')
            ->and($occurrence->colour)->toBe('oklch(0.55 0.2 270)')
            ->and($occurrence->category)->toBe('Yoga')
            ->and($occurrence->rrule)->toBeNull() // occurrences are not themselves recurring
            ->and($occurrence->extraAttributes)->toBe(['instructor' => 'Jane']);
    });

    it('withDate() preserves duration across DST boundaries', function () {
        // 90-minute event
        $original = EphemerisEvent::make(
            id: 'test',
            title: 'Test',
            startsAt: Carbon::parse('2026-03-10 09:00'),
            endsAt: Carbon::parse('2026-03-10 10:30'),
        );

        $occurrence = $original->withDate(Carbon::parse('2026-04-15 09:00'));

        expect($occurrence->durationInMinutes)->toBe(90);
    });

});
