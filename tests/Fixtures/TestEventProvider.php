<?php

namespace BlackpigCreatif\Ephemeride\Tests\Fixtures;

use BlackpigCreatif\Ephemeride\Contracts\ProvidesEphemerides;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Simple fixture provider for tests.
 *
 * Accepts a pre-built collection at construction and returns it from
 * getEphemerides(), allowing tests to inject arbitrary events.
 */
class TestEventProvider implements ProvidesEphemerides
{
    public function __construct(
        private readonly Collection $events,
    ) {}

    public function getEphemerides(Carbon $from, Carbon $to): Collection
    {
        return $this->events;
    }
}
