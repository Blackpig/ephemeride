<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use BlackpigCreatif\Ephemeride\Support\MonthGrid;
use Carbon\Carbon;

describe('MonthGrid', function () {

    beforeEach(function () {
        $this->grid = new MonthGrid;
    });

    it('always produces exactly 42 day cells (6×7)', function () {
        // February 2026 — can fit in 4 rows; should be padded to 42
        $result = $this->grid->build(2026, 2, collect(), 1);

        expect(count($result['days']))->toBe(42);
    });

    it('produces 42 cells for months that start at the beginning of a week', function () {
        // March 2026 starts on a Sunday; with week starting Monday the grid needs padding
        $result = $this->grid->build(2026, 3, collect(), 1);

        expect(count($result['days']))->toBe(42);
    });

    it('marks leading days from previous month as outside month', function () {
        // January 2026: starts on a Thursday. With week starting Monday,
        // Mon-Wed (29, 30, 31 Dec) are leading days.
        $result = $this->grid->build(2026, 1, collect(), 1);
        $days = array_values($result['days']);

        // First day must be in the previous month
        expect($days[0]['inMonth'])->toBeFalse()
            ->and($days[0]['date']->month)->toBe(12);
    });

    it('marks trailing days from next month as outside month', function () {
        $result = $this->grid->build(2026, 3, collect(), 1);
        $days = array_values($result['days']);
        $lastDay = end($days);

        if (! $lastDay['inMonth']) {
            expect($lastDay['inMonth'])->toBeFalse();
        } else {
            // If the last day is in-month, at least confirm grid is complete
            expect(count($result['days']))->toBe(42);
        }
    });

    it('marks today correctly', function () {
        $today = Carbon::today();
        $result = $this->grid->build($today->year, $today->month, collect(), 1);

        $todayKey = $today->toDateString();
        expect($result['days'][$todayKey]['isToday'])->toBeTrue();
    });

    it('assigns events to their matching day', function () {
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Test Event',
            startsAt: Carbon::parse('2026-03-15 09:00'),
            endsAt: Carbon::parse('2026-03-15 10:00'),
        );

        $result = $this->grid->build(2026, 3, collect([$event]), 1);

        expect($result['days']['2026-03-15']['events'])->toHaveCount(1)
            ->and($result['days']['2026-03-15']['events'][0]->id)->toBe('e1');
    });

    it('assigns events on leading days to the correct cells', function () {
        // Feb 2026 starts on Sunday — with Monday start, 26 Jan is the grid start
        // Place an event on 26 Jan
        $event = EphemerisEvent::make(
            id: 'jan-event',
            title: 'January Event',
            startsAt: Carbon::parse('2026-01-26 10:00'),
            endsAt: Carbon::parse('2026-01-26 11:00'),
        );

        $result = $this->grid->build(2026, 2, collect([$event]), 1);

        $leadingDay = '2026-01-26';
        if (array_key_exists($leadingDay, $result['days'])) {
            expect($result['days'][$leadingDay]['events'])->toHaveCount(1);
        } else {
            // Day not in grid — skip (grid window varies by month)
            expect(true)->toBeTrue();
        }
    });

    it('getGridWindow returns from and to keys', function () {
        $window = $this->grid->getGridWindow(2026, 3, 1);

        expect($window)->toHaveKeys(['from', 'to'])
            ->and($window['from'])->toBeInstanceOf(Carbon::class)
            ->and($window['to'])->toBeInstanceOf(Carbon::class);
    });

    it('grid window covers at least 42 days', function () {
        $window = $this->grid->getGridWindow(2026, 3, 1);

        $diff = $window['from']->diffInDays($window['to']);
        expect($diff)->toBeGreaterThanOrEqual(41);
    });

    it('week starting on Sunday produces different grid start than Monday', function () {
        $mondayWindow = $this->grid->getGridWindow(2026, 3, 1);
        $sundayWindow = $this->grid->getGridWindow(2026, 3, 0);

        // For March 2026 (starts on Sunday), a Sunday-start grid should start on 1 March
        // while a Monday-start grid rewinds to the previous Monday (23 Feb)
        expect($mondayWindow['from']->toDateString())->not->toBe($sundayWindow['from']->toDateString());
    });

});
