<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;

describe('EphemerisEvent DTO', function () {

    it('constructs with required fields', function () {
        $startsAt = Carbon::parse('2026-03-10 09:00');
        $endsAt = Carbon::parse('2026-03-10 10:00');

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

    describe('toPayload()', function () {

        it('returns all expected keys', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Morning Yoga',
                startsAt: Carbon::parse('2026-04-21 09:00'),
                endsAt: Carbon::parse('2026-04-21 10:00'),
            );

            $payload = $event->toPayload();

            expect($payload)->toHaveKeys([
                'id', 'title', 'startsAt', 'endsAt',
                'isAllDay', 'formattedDate',
                'url', 'description', 'imageUrl',
                'category', 'colour', 'extraAttributes',
            ]);
        });

        it('serializes Carbon dates to ISO 8601 strings', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::parse('2026-04-21 09:00:00', 'UTC'),
                endsAt: Carbon::parse('2026-04-21 10:00:00', 'UTC'),
            );

            $payload = $event->toPayload();

            expect($payload['startsAt'])->toBeString()->toContain('2026-04-21')
                ->and($payload['endsAt'])->toBeString()->toContain('2026-04-21');
        });

        it('sets isAllDay correctly for a timed event', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::parse('2026-04-21 09:00'),
                endsAt: Carbon::parse('2026-04-21 10:00'),
            );

            expect($event->toPayload()['isAllDay'])->toBeFalse();
        });

        it('sets isAllDay correctly for an all-day event', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::parse('2026-04-21 00:00'),
                endsAt: Carbon::parse('2026-04-22 00:00'),
            );

            expect($event->toPayload()['isAllDay'])->toBeTrue();
        });

        it('formats date display for a timed event', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::parse('2026-04-21 09:00'),
                endsAt: Carbon::parse('2026-04-21 10:30'),
            );

            $payload = $event->toPayload();

            expect($payload['formattedDate'])->toContain('21')
                ->and($payload['formattedDate'])->toContain('09:00')
                ->and($payload['formattedDate'])->toContain('10:30');
        });

        it('formats date display for a single-day all-day event', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::parse('2026-04-21 00:00'),
                endsAt: Carbon::parse('2026-04-22 00:00'),
            );

            $payload = $event->toPayload();

            // A single-day all-day event's endsAt is midnight next day — both dates appear
            expect($payload['formattedDate'])->toContain('21');
        });

        it('formats date display for a multi-day all-day event with date range', function () {
            $event = EphemerisEvent::make(
                id: 'retreat',
                title: 'Spring Retreat',
                startsAt: Carbon::parse('2026-04-12 00:00'),
                endsAt: Carbon::parse('2026-04-15 00:00'),
            );

            $payload = $event->toPayload();

            expect($payload['formattedDate'])->toContain('12')
                ->and($payload['formattedDate'])->toContain('15');
        });

        it('includes optional fields in the payload', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Workshop',
                startsAt: Carbon::parse('2026-04-26 14:00'),
                endsAt: Carbon::parse('2026-04-26 17:00'),
                url: 'https://example.com/book',
                description: 'A deep afternoon workshop.',
                imageUrl: 'https://example.com/image.jpg',
                category: 'Workshop',
                colour: 'oklch(0.75 0.18 90)',
                extraAttributes: ['price' => '€45'],
            );

            $payload = $event->toPayload();

            expect($payload['url'])->toBe('https://example.com/book')
                ->and($payload['description'])->toBe('A deep afternoon workshop.')
                ->and($payload['imageUrl'])->toBe('https://example.com/image.jpg')
                ->and($payload['category'])->toBe('Workshop')
                ->and($payload['colour'])->toBe('oklch(0.75 0.18 90)')
                ->and($payload['extraAttributes'])->toBe(['price' => '€45']);
        });

        it('returns null for absent optional fields', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::now(),
                endsAt: Carbon::now()->addHour(),
            );

            $payload = $event->toPayload();

            expect($payload['url'])->toBeNull()
                ->and($payload['description'])->toBeNull()
                ->and($payload['imageUrl'])->toBeNull()
                ->and($payload['category'])->toBeNull()
                ->and($payload['colour'])->toBeNull()
                ->and($payload['extraAttributes'])->toBe([]);
        });

        it('includes links array in the payload', function () {
            $links = [
                ['label' => 'Book Now', 'url' => 'https://example.com/book', 'style' => 'primary'],
                ['label' => 'More Info', 'url' => 'https://example.com/info', 'style' => 'secondary'],
            ];

            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::now(),
                endsAt: Carbon::now()->addHour(),
                links: $links,
            );

            expect($event->toPayload()['links'])->toBe($links);
        });

        it('defaults links to an empty array', function () {
            $event = EphemerisEvent::make(
                id: 'test-1',
                title: 'Test',
                startsAt: Carbon::now(),
                endsAt: Carbon::now()->addHour(),
            );

            expect($event->toPayload()['links'])->toBe([]);
        });

    });

    it('withDate() preserves links', function () {
        $links = [
            ['label' => 'Book', 'url' => 'https://example.com/book', 'style' => 'primary'],
        ];

        $original = EphemerisEvent::make(
            id: 'test',
            title: 'Test',
            startsAt: Carbon::parse('2026-03-10 09:00'),
            endsAt: Carbon::parse('2026-03-10 10:00'),
            links: $links,
        );

        $occurrence = $original->withDate(Carbon::parse('2026-03-17 09:00'));

        expect($occurrence->links)->toBe($links);
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
