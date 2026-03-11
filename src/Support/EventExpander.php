<?php

namespace BlackpigCreatif\Ephemeride\Support;

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RRule\RRule;
use RRule\RSet;

class EventExpander
{
    /**
     * Expand a collection of EphemerisEvent DTOs into individual occurrences
     * within the given date window.
     *
     * One-off events are passed through directly if they fall within the window.
     * Recurring events are expanded using rlanvin/php-rrule's RSet, which handles
     * the RRULE, EXDATE (exclusions), and RDATE (additional dates) in a single pass.
     *
     * @param  Collection<int, EphemerisEvent>  $events
     * @return Collection<int, EphemerisEvent>
     */
    public function expand(Collection $events, Carbon $from, Carbon $to): Collection
    {
        $expanded = collect();

        foreach ($events as $event) {
            if ($event->rrule === null) {
                // One-off event: include only if it overlaps the window.
                if ($event->startsAt->lte($to) && $event->endsAt->gte($from)) {
                    $expanded->push($event);
                }

                continue;
            }

            // Recurring event: build an RSet combining the rule, exclusions, and additions.
            // We pass DTSTART from the event's startsAt so occurrences are anchored to the
            // correct time of day — without DTSTART, rlanvin/php-rrule uses "now" as the start.
            $rrule = new RRule($event->rrule, $event->startsAt->toDateTime());
            $rset = new RSet;
            $rset->addRRule($rrule);

            foreach ($event->exdates as $exdate) {
                $rset->addExDate($this->toDateTime($exdate));
            }

            foreach ($event->rdates as $rdate) {
                $rset->addDate($this->toDateTime($rdate));
            }

            // getOccurrencesBetween is inclusive of both boundaries.
            $occurrences = $rset->getOccurrencesBetween(
                $from->toDateTime(),
                $to->toDateTime(),
            );

            foreach ($occurrences as $occurrence) {
                $expanded->push(
                    $event->withDate(Carbon::instance($occurrence))
                );
            }
        }

        return $expanded;
    }

    /**
     * Normalise an exdate or rdate value to a \DateTime instance
     * suitable for RSet. Accepts Carbon instances, \DateTime, or date strings.
     */
    private function toDateTime(mixed $date): \DateTime
    {
        if ($date instanceof Carbon) {
            return $date->toDateTime();
        }

        if ($date instanceof \DateTime) {
            return $date;
        }

        return Carbon::parse((string) $date)->toDateTime();
    }
}
