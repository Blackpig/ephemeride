<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use BlackpigCreatif\Ephemeride\Support\WeekGrid;
use Carbon\Carbon;

describe('WeekGrid', function () {

    beforeEach(function () {
        $this->grid = new WeekGrid;
        // ISO week 10 of 2026 = Mon 2 Mar – Sun 8 Mar 2026
        $this->year         = 2026;
        $this->week         = 10;
        $this->weekStartsAt = 1;    // Monday
        $this->startHour    = 7;
        $this->endHour      = 21;
        $this->slotInterval = 30;
    });

    it('getGridWindow returns a 7-day range for the ISO week', function () {
        $window = $this->grid->getGridWindow($this->year, $this->week, $this->weekStartsAt);

        expect($window)->toHaveKeys(['from', 'to']);

        // from = Mon 00:00:00, to = Sun 23:59:59 — spans 7 calendar days.
        // diffInDays returns ~6.999 due to the seconds component; we verify the
        // window spans exactly 7 distinct calendar days instead.
        $daySpan = $window['from']->toDateString() !== $window['to']->toDateString()
            ? (int) $window['from']->diffInDays($window['to']->copy()->startOfDay()) + 1
            : 1;
        expect($daySpan)->toBe(7);
    });

    it('produces exactly 7 day entries', function () {
        $result = $this->grid->build(
            $this->year, $this->week, collect(),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['days'])->toHaveCount(7);
    });

    it('produces the correct number of time slots', function () {
        $expectedSlots = (($this->endHour - $this->startHour) * 60) / $this->slotInterval;

        $result = $this->grid->build(
            $this->year, $this->week, collect(),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['slots'])->toHaveCount((int) $expectedSlots);
    });

    it('first slot label matches startHour', function () {
        $result = $this->grid->build(
            $this->year, $this->week, collect(),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['slots'][0]['label'])->toBe('07:00');
    });

    it('assigns timed event to correct column', function () {
        // Tuesday = dayOfWeekIso 2 → CSS col 3 (col 1=time, col 2=Mon, col 3=Tue)
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Tuesday Class',
            startsAt: Carbon::parse('2026-03-03 09:00'), // Tuesday
            endsAt: Carbon::parse('2026-03-03 10:00'),
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$event]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        $placement = $result['timedEvents']['e1'];
        // Tuesday dayOfWeekIso=2, +1 for time label col = col 3
        expect($placement['col'])->toBe(3);
    });

    it('calculates rowStart from minutes since startHour', function () {
        // 09:00 with startHour=7 and 30-min slots:
        // minutes from 07:00 = 120, floor(120/30) = 4, +1 (1-indexed rows) = 5
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Class',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:00'),
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$event]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['timedEvents']['e1']['rowStart'])->toBe(5);
    });

    it('calculates rowSpan from event duration', function () {
        // 90-minute event with 30-min slots → span 3 rows
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Long Class',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 10:30'),
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$event]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['timedEvents']['e1']['rowSpan'])->toBe(3);
    });

    it('rowSpan is at minimum 1 for very short events', function () {
        // 15-minute event with 30-min slots → ceil(15/30) = 1
        $event = EphemerisEvent::make(
            id: 'e1',
            title: 'Short Class',
            startsAt: Carbon::parse('2026-03-03 09:00'),
            endsAt: Carbon::parse('2026-03-03 09:15'),
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$event]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['timedEvents']['e1']['rowSpan'])->toBeGreaterThanOrEqual(1);
    });

    it('separates all-day events from timed events', function () {
        // Week 10: Mon 2 Mar – Sun 8 Mar 2026
        // Single-day all-day event on Tuesday 3 Mar.
        // Inclusive convention: endsAt = startsAt (same midnight) → colSpan = 1.
        $allDay = EphemerisEvent::make(
            id: 'all-day',
            title: 'Retreat Day',
            startsAt: Carbon::parse('2026-03-03 00:00'),
            endsAt: Carbon::parse('2026-03-03 00:00'), // inclusive: same day
        );

        $timed = EphemerisEvent::make(
            id: 'timed',
            title: 'Morning Class',
            startsAt: Carbon::parse('2026-03-04 09:00'),
            endsAt: Carbon::parse('2026-03-04 10:00'),
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$allDay, $timed]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['allDayEvents'])->toHaveCount(1)
            ->and($result['allDayEvents']['all-day']['event']->id)->toBe('all-day')
            ->and($result['allDayEvents']['all-day']['colStart'])->toBe(3)
            ->and($result['allDayEvents']['all-day']['colSpan'])->toBe(1)
            ->and($result['timedEvents'])->toHaveKey('timed');
    });

    it('calculates correct colStart and colSpan for a multi-day all-day event', function () {
        // Wed 4 Mar → Fri 6 Mar (inclusive) = Wed, Thu, Fri = 3 days
        // col1=label, col2=Mon, col3=Tue, col4=Wed → colStart=4, colSpan=3
        $retreat = EphemerisEvent::make(
            id: 'retreat',
            title: 'Spring Retreat',
            startsAt: Carbon::parse('2026-03-04 00:00'),
            endsAt: Carbon::parse('2026-03-06 00:00'), // inclusive last day
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$retreat]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['allDayEvents']['retreat']['colStart'])->toBe(4)
            ->and($result['allDayEvents']['retreat']['colSpan'])->toBe(3);
    });

    it('clamps all-day events that start before or end after the displayed week', function () {
        // Event starts Sat 28 Feb (week before) and ends Tue 3 Mar (inclusive, mid-week)
        // Clamped start = Mon 2 Mar → colStart=2, clamped end = Tue 3 Mar → colSpan=2
        $crossBoundary = EphemerisEvent::make(
            id: 'cross',
            title: 'Cross-boundary Retreat',
            startsAt: Carbon::parse('2026-02-28 00:00'),
            endsAt: Carbon::parse('2026-03-03 00:00'), // inclusive last day
        );

        $result = $this->grid->build(
            $this->year, $this->week, collect([$crossBoundary]),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result['allDayEvents']['cross']['colStart'])->toBe(2)
            ->and($result['allDayEvents']['cross']['colSpan'])->toBe(2);
    });

    it('returns correct structure keys', function () {
        $result = $this->grid->build(
            $this->year, $this->week, collect(),
            $this->weekStartsAt, $this->startHour, $this->endHour, $this->slotInterval
        );

        expect($result)->toHaveKeys([
            'gridStartsAt',
            'gridEndsAt',
            'days',
            'slots',
            'allDayEvents',
            'timedEvents',
        ]);
    });

});
