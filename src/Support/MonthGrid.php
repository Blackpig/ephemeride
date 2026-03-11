<?php

namespace BlackpigCreatif\Ephemeride\Support;

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthGrid
{
    /**
     * Return the full grid window (from/to) for a given year/month.
     *
     * This window is passed to the provider so it queries events across the
     * complete visible range — including leading and trailing days from
     * adjacent months. Always covers exactly 42 cells (6 × 7).
     *
     * @return array{from: Carbon, to: Carbon}
     */
    public function getGridWindow(int $year, int $month, int $weekStartsAt): array
    {
        $startsAt = Carbon::create($year, $month, 1)->startOfDay();
        $endsAt = $startsAt->copy()->endOfMonth();

        // Carbon's startOfWeek($weekStartsAt) takes the START day of the week.
        // Carbon's endOfWeek($weekEndsAt) takes the END day of the week.
        // If week starts on Monday (1), it ends on Sunday (0).
        // Formula: weekEndsAt = (weekStartsAt + 6) % 7
        $weekEndsAt = ($weekStartsAt + 6) % 7;

        $gridStartsAt = $startsAt->copy()->startOfWeek($weekStartsAt);
        $gridEndsAt = $endsAt->copy()->endOfWeek($weekEndsAt)->endOfDay();

        // Ensure we always have at least 6 rows (42 cells) for layout stability.
        // Some months fit in 5 rows — pad to 6 to prevent layout shift.
        // diffInDays(42 cells) = 41 because cells are 0-indexed.
        if ($gridStartsAt->diffInDays($gridEndsAt) < 41) {
            $gridEndsAt = $gridStartsAt->copy()->addDays(41)->endOfDay();
        }

        return ['from' => $gridStartsAt, 'to' => $gridEndsAt];
    }

    /**
     * Build the complete month grid structure.
     *
     * Returns a structured array with the grid window, an ordered day map
     * keyed by date string ('YYYY-MM-DD'), and a weeks array with multi-day
     * event placements for horizontal spanning banners.
     *
     * All-day events use an INCLUSIVE endsAt convention: startsAt=Mon, endsAt=Wed
     * means the event covers Mon, Tue, and Wed. Multi-day all-day events
     * (endsAt > startsAt) are placed in the `weeks` structure; single-day
     * events are assigned to their day cell as normal.
     *
     * @param  Collection<int, EphemerisEvent>  $events  Already expanded by EventExpander.
     * @return array{
     *   gridStartsAt: Carbon,
     *   gridEndsAt: Carbon,
     *   days: array<string, array{date: Carbon, isToday: bool, inMonth: bool, events: list<EphemerisEvent>}>,
     *   weeks: list<array{startDate: Carbon, multiDayPlacements: list<array{event: EphemerisEvent, colStart: int, colSpan: int, continued: bool, continues: bool}>}>
     * }
     */
    public function build(
        int $year,
        int $month,
        Collection $events,
        int $weekStartsAt,
    ): array {
        ['from' => $gridStartsAt, 'to' => $gridEndsAt] = $this->getGridWindow($year, $month, $weekStartsAt);

        // Build the skeleton day map
        $days = [];
        $current = $gridStartsAt->copy();

        while ($current->lte($gridEndsAt)) {
            $days[$current->toDateString()] = [
                'date' => $current->copy(),
                'isToday' => $current->isToday(),
                'inMonth' => $current->month === $month,
                'events' => [],
            ];
            $current->addDay();
        }

        // Build the 6 week-row skeletons for multi-day event banner placement
        $weeks = [];
        $weekCursor = $gridStartsAt->copy();
        while ($weekCursor->lte($gridEndsAt)) {
            $weeks[] = ['startDate' => $weekCursor->copy(), 'multiDayPlacements' => []];
            $weekCursor->addDays(7);
        }

        // Assign events: multi-day all-day events → weeks structure; everything else → day cells
        foreach ($events as $event) {
            $isMultiDayAllDay = $event->isAllDay && $event->endsAt->gt($event->startsAt);

            if ($isMultiDayAllDay) {
                $eventStart = $event->startsAt->copy()->startOfDay();
                $eventEnd   = $event->endsAt->copy()->startOfDay(); // inclusive last day

                foreach ($weeks as &$week) {
                    $weekStart = $week['startDate']->copy()->startOfDay();
                    $weekEnd   = $weekStart->copy()->addDays(6); // inclusive last day of this row

                    if ($eventStart->gt($weekEnd) || $eventEnd->lt($weekStart)) {
                        continue; // event doesn't appear in this week row
                    }

                    $clampedStart = $eventStart->lt($weekStart) ? $weekStart->copy() : $eventStart->copy();
                    $clampedEnd   = $eventEnd->gt($weekEnd) ? $weekEnd->copy() : $eventEnd->copy();

                    $week['multiDayPlacements'][] = [
                        'event'     => $event,
                        'colStart'  => (int) $weekStart->diffInDays($clampedStart) + 1, // 1-indexed
                        'colSpan'   => max(1, (int) $clampedStart->diffInDays($clampedEnd) + 1),
                        'continued' => $eventStart->lt($weekStart), // started in a previous row
                        'continues' => $eventEnd->gt($weekEnd),     // continues into next row
                    ];
                }
                unset($week);
            } else {
                $key = $event->startsAt->toDateString();

                if (array_key_exists($key, $days)) {
                    $days[$key]['events'][] = $event;
                }
            }
        }

        return [
            'gridStartsAt' => $gridStartsAt,
            'gridEndsAt'   => $gridEndsAt,
            'days'         => $days,
            'weeks'        => $weeks,
        ];
    }
}
