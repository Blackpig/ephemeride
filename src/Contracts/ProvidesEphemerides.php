<?php

namespace BlackpigCreatif\Ephemeride\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ProvidesEphemerides
{
    /**
     * Return a collection of EphemerisEvent DTOs for the given date window.
     *
     * The $from and $to parameters represent the full grid window — including
     * leading and trailing days from adjacent months in month view, or the
     * complete seven-day span in week view. The provider is responsible for
     * querying its own models and mapping results to EphemerisEvent DTOs.
     *
     * The calendar never touches Eloquent directly.
     *
     * @return Collection<int, \BlackpigCreatif\Ephemeride\Data\EphemerisEvent>
     */
    public function getEphemerides(Carbon $from, Carbon $to): Collection;
}
