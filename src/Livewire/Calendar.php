<?php

namespace BlackpigCreatif\Ephemeride\Livewire;

use BlackpigCreatif\Ephemeride\Contracts\ProvidesEphemerides;
use BlackpigCreatif\Ephemeride\Support\EventExpander;
use BlackpigCreatif\Ephemeride\Support\MonthGrid;
use BlackpigCreatif\Ephemeride\Support\WeekGrid;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Calendar extends Component
{
    // -------------------------------------------------------------------------
    // Props — set at mount, remain constant for the component lifecycle
    // -------------------------------------------------------------------------

    /** Fully-qualified class name of the provider implementing ProvidesEphemerides. */
    public string $provider;

    /** Views available on this instance. Subset of ['month', 'week']. */
    public array $views = ['month', 'week'];

    /** Whether to show the category filter bar. */
    public bool $filterable = false;

    /**
     * Per-instance CSS custom property overrides.
     * Keys are bare token names (e.g. 'color-primary'), values are CSS colour strings.
     * These are merged over the global config('ephemeride.theme') defaults and
     * rendered as scoped inline style on the component root element.
     */
    public array $theme = [];

    // -------------------------------------------------------------------------
    // State — managed internally, reactive, navigated by user actions.
    // Note: asymmetric visibility (private(set)) is intentionally avoided here —
    // Livewire's hydration mechanism sets these properties directly and requires
    // full public write access. Navigation mutation is guarded by action methods.
    // -------------------------------------------------------------------------

    public string $view = 'month';

    public int $year;

    public int $month;

    public int $week;

    public ?string $activeCategory = null;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(
        string $provider,
        ?array $views = null,
        ?string $defaultView = null,
        bool $filterable = false,
        array $theme = [],
    ): void {
        $this->provider = $provider;
        $this->filterable = $filterable;
        $this->theme = $theme;

        $this->views = $views ?? config('ephemeride.views', ['month', 'week']);
        $activeView = $defaultView ?? config('ephemeride.default_view', 'month');

        if (! in_array($activeView, $this->views, true)) {
            $activeView = $this->views[0];
        }

        $this->view = $activeView;

        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
        $this->week = $now->isoWeek;

        $this->validateProvider();
    }

    // -------------------------------------------------------------------------
    // Navigation actions
    // -------------------------------------------------------------------------

    public function previousPeriod(): void
    {
        if ($this->view === 'month') {
            $date = Carbon::create($this->year, $this->month, 1)->subMonth();
            $this->year = $date->year;
            $this->month = $date->month;
        } else {
            $date = Carbon::now()->setISODate($this->year, $this->week)->subWeek();
            $this->year = $date->year;
            $this->week = $date->isoWeek;
        }
    }

    public function nextPeriod(): void
    {
        if ($this->view === 'month') {
            $date = Carbon::create($this->year, $this->month, 1)->addMonth();
            $this->year = $date->year;
            $this->month = $date->month;
        } else {
            $date = Carbon::now()->setISODate($this->year, $this->week)->addWeek();
            $this->year = $date->year;
            $this->week = $date->isoWeek;
        }
    }

    public function goToToday(): void
    {
        $now = Carbon::now();
        $this->year = $now->year;
        $this->month = $now->month;
        $this->week = $now->isoWeek;
    }

    public function switchView(string $view): void
    {
        if (! in_array($view, $this->views, true)) {
            return;
        }

        $this->view = $view;
    }

    public function filterCategory(?string $category): void
    {
        $this->activeCategory = $category;
    }

    // -------------------------------------------------------------------------
    // Computed properties (cached per request cycle by Livewire)
    // -------------------------------------------------------------------------

    /**
     * The expanded, filtered event collection for the current grid window.
     *
     * @return Collection<int, \BlackpigCreatif\Ephemeride\Data\EphemerisEvent>
     */
    #[Computed]
    public function events(): Collection
    {
        $window = $this->currentGridWindow();

        $raw = $this->resolveProvider()->getEphemerides($window['from'], $window['to']);
        $expanded = app(EventExpander::class)->expand($raw, $window['from'], $window['to']);

        if ($this->activeCategory !== null) {
            $expanded = $expanded->filter(
                fn ($event) => $event->category === $this->activeCategory
            );
        }

        return $expanded;
    }

    /**
     * The structured grid array for the current view.
     */
    #[Computed]
    public function grid(): array
    {
        $weekStartsAt = config('ephemeride.week_starts_at', 1);

        if ($this->view === 'month') {
            return app(MonthGrid::class)->build(
                year: $this->year,
                month: $this->month,
                events: $this->events,
                weekStartsAt: $weekStartsAt,
            );
        }

        return app(WeekGrid::class)->build(
            year: $this->year,
            week: $this->week,
            events: $this->events,
            weekStartsAt: $weekStartsAt,
            startHour: config('ephemeride.week_start_hour', 7),
            endHour: config('ephemeride.week_end_hour', 21),
            slotInterval: config('ephemeride.slot_interval', 30),
        );
    }

    /**
     * Unique categories derived from the current expanded event set.
     *
     * @return list<string>
     */
    #[Computed]
    public function categories(): array
    {
        return $this->events
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('ephemeride::livewire.calendar', [
            'themeStyle' => $this->buildThemeStyle(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the provider from the service container, allowing DI in providers.
     */
    protected function resolveProvider(): ProvidesEphemerides
    {
        return app($this->provider);
    }

    /**
     * Validate that the given provider class exists and implements the interface.
     *
     * @throws InvalidArgumentException
     */
    protected function validateProvider(): void
    {
        if (! class_exists($this->provider)) {
            throw new InvalidArgumentException(
                "Éphéméride: provider class [{$this->provider}] does not exist."
            );
        }

        if (! is_a($this->provider, ProvidesEphemerides::class, true)) {
            throw new InvalidArgumentException(
                "Éphéméride: [{$this->provider}] must implement ProvidesEphemerides."
            );
        }
    }

    /**
     * Return the grid window boundaries for the current view and period.
     *
     * @return array{from: Carbon, to: Carbon}
     */
    protected function currentGridWindow(): array
    {
        $weekStartsAt = config('ephemeride.week_starts_at', 1);

        if ($this->view === 'month') {
            return app(MonthGrid::class)->getGridWindow($this->year, $this->month, $weekStartsAt);
        }

        return app(WeekGrid::class)->getGridWindow($this->year, $this->week, $weekStartsAt);
    }

    /**
     * Build the scoped inline style string for CSS custom property overrides.
     *
     * Merges config-level theme defaults with per-instance :theme prop overrides.
     * Output format: "--ephemeride-key: value; --ephemeride-key2: value2;"
     */
    protected function buildThemeStyle(): string
    {
        $merged = array_merge(
            config('ephemeride.theme', []),
            $this->theme,
        );

        if (empty($merged)) {
            return '';
        }

        $declarations = [];
        foreach ($merged as $key => $value) {
            $declarations[] = "--ephemeride-{$key}: {$value}";
        }

        return implode('; ', $declarations).';';
    }
}
