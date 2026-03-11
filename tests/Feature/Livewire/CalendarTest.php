<?php

use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use BlackpigCreatif\Ephemeride\Livewire\Calendar;
use BlackpigCreatif\Ephemeride\Tests\Fixtures\TestEventProvider;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    // Bind TestEventProvider into the container so Calendar can resolve it
    app()->bind(TestEventProvider::class, fn () => new TestEventProvider(collect()));
});

describe('Calendar Livewire component', function () {

    it('mounts with a valid provider FQCN', function () {
        Livewire::test(Calendar::class, [
            'provider' => TestEventProvider::class,
        ])->assertOk();
    });

    it('throws when provider class does not exist', function () {
        // Livewire may wrap exceptions from mount() in a ViewException.
        // We test for the exception message rather than the exact class.
        expect(fn () =>
            Livewire::test(Calendar::class, [
                'provider' => 'App\\NonExistent\\Provider',
            ])
        )->toThrow(\Exception::class, 'does not exist');
    });

    it('throws when provider does not implement ProvidesEphemerides', function () {
        expect(fn () =>
            Livewire::test(Calendar::class, [
                'provider' => \stdClass::class,
            ])
        )->toThrow(\Exception::class, 'ProvidesEphemerides');
    });

    it('defaults to month view', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->assertSet('view', 'month');
    });

    it('mounts with a specified default view', function () {
        Livewire::test(Calendar::class, [
            'provider'    => TestEventProvider::class,
            'defaultView' => 'week',
        ])->assertSet('view', 'week');
    });

    it('initialises year and month from today', function () {
        $now = Carbon::now();

        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->assertSet('year', $now->year)
            ->assertSet('month', $now->month);
    });

    it('previousPeriod() decrements the month', function () {
        $now = Carbon::now();
        $expectedMonth = $now->copy()->subMonth()->month;
        $expectedYear  = $now->copy()->subMonth()->year;

        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->call('previousPeriod')
            ->assertSet('month', $expectedMonth)
            ->assertSet('year', $expectedYear);
    });

    it('previousPeriod() wraps year correctly (Jan → Dec)', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->set('year', 2026)
            ->set('month', 1)
            ->call('previousPeriod')
            ->assertSet('month', 12)
            ->assertSet('year', 2025);
    });

    it('nextPeriod() increments the month', function () {
        $now = Carbon::now();
        $expectedMonth = $now->copy()->addMonth()->month;
        $expectedYear  = $now->copy()->addMonth()->year;

        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->call('nextPeriod')
            ->assertSet('month', $expectedMonth)
            ->assertSet('year', $expectedYear);
    });

    it('nextPeriod() wraps year correctly (Dec → Jan)', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->set('year', 2025)
            ->set('month', 12)
            ->call('nextPeriod')
            ->assertSet('month', 1)
            ->assertSet('year', 2026);
    });

    it('goToToday() resets to current year and month', function () {
        $now = Carbon::now();

        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->set('year', 2020)
            ->set('month', 6)
            ->call('goToToday')
            ->assertSet('year', $now->year)
            ->assertSet('month', $now->month);
    });

    it('switchView() changes the active view', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->assertSet('view', 'month')
            ->call('switchView', 'week')
            ->assertSet('view', 'week');
    });

    it('switchView() ignores a view not in $views', function () {
        Livewire::test(Calendar::class, [
            'provider' => TestEventProvider::class,
            'views'    => ['month'],
        ])
            ->call('switchView', 'week')
            ->assertSet('view', 'month'); // unchanged
    });

    it('filterCategory() sets the active category', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->call('filterCategory', 'Yoga')
            ->assertSet('activeCategory', 'Yoga');
    });

    it('filterCategory(null) resets the filter', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->set('activeCategory', 'Yoga')
            ->call('filterCategory', null)
            ->assertSet('activeCategory', null);
    });

    it('renders the month view markup', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->assertSee('ephemeride-month-grid', false);
    });

    it('renders the week view markup after switching', function () {
        Livewire::test(Calendar::class, ['provider' => TestEventProvider::class])
            ->call('switchView', 'week')
            ->assertSee('ephemeride-week-grid', false);
    });

    it('does not render the view switcher when only one view is configured', function () {
        Livewire::test(Calendar::class, [
            'provider' => TestEventProvider::class,
            'views'    => ['month'],
        ])->assertDontSee('ephemeride-view-switcher', false);
    });

    it('applies inline theme CSS custom properties on the root element', function () {
        Livewire::test(Calendar::class, [
            'provider' => TestEventProvider::class,
            'theme'    => ['color-primary' => 'oklch(0.65 0.12 160)'],
        ])->assertSee('--ephemeride-color-primary: oklch(0.65 0.12 160)', false);
    });

    it('category filter is not shown when filterable is false', function () {
        app()->bind(TestEventProvider::class, fn () => new TestEventProvider(
            collect([
                EphemerisEvent::make(
                    id: 'e1',
                    title: 'Yoga',
                    startsAt: Carbon::now()->startOfMonth()->setTime(9, 0),
                    endsAt: Carbon::now()->startOfMonth()->setTime(10, 0),
                    category: 'Yoga',
                ),
            ])
        ));

        Livewire::test(Calendar::class, [
            'provider'   => TestEventProvider::class,
            'filterable' => false,
        ])->assertDontSee('ephemeride-filter-bar', false);
    });

    it('week navigation: previousPeriod() decrements the ISO week', function () {
        Livewire::test(Calendar::class, [
            'provider'    => TestEventProvider::class,
            'defaultView' => 'week',
        ])
            ->set('year', 2026)
            ->set('week', 10)
            ->call('previousPeriod')
            ->assertSet('week', 9);
    });

    it('week navigation: nextPeriod() increments the ISO week', function () {
        Livewire::test(Calendar::class, [
            'provider'    => TestEventProvider::class,
            'defaultView' => 'week',
        ])
            ->set('year', 2026)
            ->set('week', 10)
            ->call('nextPeriod')
            ->assertSet('week', 11);
    });

});
