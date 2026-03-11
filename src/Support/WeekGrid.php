<?php

namespace BlackpigCreatif\Ephemeride\Support;

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeekGrid
{
    /**
     * Return the full grid window (from/to) for the given ISO week.
     *
     * @return array{from: Carbon, to: Carbon}
     */
    public function getGridWindow(int $year, int $week, int $weekStartsAt): array
    {
        // Carbon's endOfWeek($weekEndsAt) takes the END day of the week, not the start.
        // If week starts on Monday (1), it ends on Sunday (0).
        $weekEndsAt = ($weekStartsAt + 6) % 7;

        $gridStartsAt = Carbon::now()
            ->setISODate($year, $week)
            ->startOfWeek($weekStartsAt)
            ->startOfDay();

        $gridEndsAt = $gridStartsAt->copy()
            ->endOfWeek($weekEndsAt)
            ->endOfDay();

        return ['from' => $gridStartsAt, 'to' => $gridEndsAt];
    }

    /**
     * Build the complete week grid structure for the Blade view.
     *
     * Returns days, time slots, all-day events, and timed events with
     * pre-calculated CSS Grid placement (column, row start, row span).
     *
     * CSS Grid layout:
     *   - Column 1: time labels
     *   - Columns 2–8: day columns (Mon–Sun)
     *   - Row 1: all-day events band
     *   - Rows 2+: time slot rows
     *
     * @param  Collection<int, EphemerisEvent>  $events  Already expanded by EventExpander.
     * @return array{
     *   gridStartsAt: Carbon,
     *   gridEndsAt: Carbon,
     *   days: list<Carbon>,
     *   slots: list<array{row: int, time: Carbon, label: string}>,
     *   allDayEvents: list<EphemerisEvent>,
     *   timedEvents: array<string, array{event: EphemerisEvent, col: int, rowStart: int, rowSpan: int}>
     * }
     */
    public function build(
        int $year,
        int $week,
        Collection $events,
        int $weekStartsAt,
        int $startHour,
        int $endHour,
        int $slotInterval,
    ): array {
        ['from' => $gridStartsAt, 'to' => $gridEndsAt] = $this->getGridWindow($year, $week, $weekStartsAt);

        // Build the ordered list of 7 day Carbon instances by walking from grid start to grid end
        $days = [];
        $day = $gridStartsAt->copy();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $day->copy();
            $day->addDay();
        }

        // Build time slot definitions — one per interval between startHour and endHour
        $slots = [];
        $slotStart = $gridStartsAt->copy()->setTime($startHour, 0);
        $slotEnd = $gridStartsAt->copy()->setTime($endHour, 0);
        $rowIndex = 1; // 1-based; all-day band is rendered outside the CSS grid, so row 1 = first time slot

        while ($slotStart->lt($slotEnd)) {
            $slots[] = [
                'row' => $rowIndex, // matches $loop->iteration in the blade template
                'time' => $slotStart->copy(),
                'label' => $slotStart->format('H:i'),
            ];
            $slotStart->addMinutes($slotInterval);
            $rowIndex++;
        }

        // Separate all-day events from timed events and calculate CSS Grid placement
        $allDayEvents = [];
        $timedEvents = [];

        // All-day events use an INCLUSIVE endsAt convention: an event with
        // startsAt=Mon and endsAt=Wed covers Mon, Tue, and Wed (3 days).
        $weekStart = $gridStartsAt->copy()->startOfDay();
        $weekEnd   = $gridEndsAt->copy()->startOfDay(); // inclusive last day of the week

        foreach ($events as $event) {
            if ($event->isAllDay) {
                // Clamp event bounds to the displayed week so partially-overlapping
                // events (e.g. retreats starting before Monday) render correctly.
                $eventStart   = $event->startsAt->copy()->startOfDay();
                $eventEnd     = $event->endsAt->copy()->startOfDay();
                $clampedStart = $eventStart->lt($weekStart) ? $weekStart->copy() : $eventStart->copy();
                $clampedEnd   = $eventEnd->gt($weekEnd) ? $weekEnd->copy() : $eventEnd->copy();

                // CSS column: col 1 = time labels, col 2 = first day, …, col 8 = last day.
                $colStart = (int) $weekStart->diffInDays($clampedStart) + 2;
                // +1 because endsAt is inclusive (endsAt=Wed on a Wed means that day IS shown).
                $colSpan  = max(1, (int) $clampedStart->diffInDays($clampedEnd) + 1);

                $allDayEvents[$event->id] = [
                    'event'    => $event,
                    'colStart' => $colStart,
                    'colSpan'  => $colSpan,
                ];

                continue;
            }

            // CSS column: dayOfWeekIso returns 1=Mon … 7=Sun.
            // Day columns in the grid are 2–8 (col 1 = time labels).
            $col = $event->startsAt->dayOfWeekIso + 1;

            // CSS row: calculated relative to the event's OWN DAY start hour.
            // The all-day band is rendered outside the CSS grid, so row 1 = first time slot.
            // e.g. 09:00 with startHour=7, slotInterval=30:
            //   minutes = (9*60 + 0) - (7*60) = 120
            //   rowStart = floor(120/30) + 1 = 4 + 1 = 5
            $eventDayStart = $event->startsAt->copy()->setTime($startHour, 0);
            $minutesFromDayStart = (int) $eventDayStart->diffInMinutes($event->startsAt);
            $rowStart = (int) floor($minutesFromDayStart / $slotInterval) + 1; // +1: rows are 1-indexed

            // Row span: how many slots the event occupies (minimum 1)
            $rowSpan = (int) max(1, ceil($event->durationInMinutes / $slotInterval));

            $timedEvents[$event->id] = [
                'event' => $event,
                'col' => $col,
                'rowStart' => $rowStart,
                'rowSpan' => $rowSpan,
            ];
        }

        return [
            'gridStartsAt' => $gridStartsAt,
            'gridEndsAt' => $gridEndsAt,
            'days' => $days,
            'slots' => $slots,
            'allDayEvents' => $allDayEvents,
            'timedEvents' => $timedEvents,
        ];
    }
}
