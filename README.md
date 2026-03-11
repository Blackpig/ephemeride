# Éphéméride

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blackpig-creatif/ephemeride.svg?style=flat-square)](https://packagist.org/packages/blackpig-creatif/ephemeride)
[![Total Downloads](https://img.shields.io/packagist/dt/blackpig-creatif/ephemeride.svg?style=flat-square)](https://packagist.org/packages/blackpig-creatif/ephemeride)

A Livewire calendar component for displaying events in month and week views, with RRULE recurrence, multi-day spanning banners, and CSS custom property theming.

Éphéméride is deliberately data-agnostic. You implement a single interface to supply events as `EphemerisEvent` DTOs — the component handles all layout geometry, all-day spanning, overflow, and navigation. No Eloquent models, no migrations, no Filament dependency.

## Requirements

- PHP 8.3+
- Laravel 12+
- Livewire 4+ (Alpine.js is bundled)

## Installation

```bash
composer require blackpig-creatif/ephemeride
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag="ephemeride-config"
```

Publish the CSS (optional — include in your build pipeline if you prefer the source):

```bash
php artisan vendor:publish --tag="ephemeride-assets"
```

---

## Quick Start

### 1. Generate an event provider

```bash
php artisan ephemeride:make-provider Festivals
```

This creates `app/BlackpigCreatif/Ephemeride/FestivalsProvider.php` with the interface already implemented and a commented example. The `Provider` suffix is added automatically — passing `FestivalsProvider` and `Festivals` both produce the same class name.

Open the generated file and implement `getEphemerides()`. The `$from`/`$to` window covers the full visible grid range, including leading and trailing days from adjacent months or weeks, so your query should use it as-is.

```php
namespace App\BlackpigCreatif\Ephemeride;

use BlackpigCreatif\Ephemeride\Contracts\ProvidesEphemerides;
use BlackpigCreatif\Ephemeride\Data\EphemerisEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FestivalsProvider implements ProvidesEphemerides
{
    public function getEphemerides(Carbon $from, Carbon $to): Collection
    {
        return Event::query()
            ->whereBetween('starts_at', [$from, $to])
            ->get()
            ->map(fn (Event $event) => EphemerisEvent::make(
                id:       (string) $event->id,
                title:    $event->title,
                startsAt: $event->starts_at,
                endsAt:   $event->ends_at,
                colour:   $event->colour,
                url:      route('events.show', $event),
            ));
    }
}
```

### 2. Mount the component

```blade
<livewire:ephemeride-calendar :provider="App\BlackpigCreatif\Ephemeride\FestivalsProvider::class" />
```

### 3. Include the styles

Pull `ephemeride.css` into your build pipeline:

```js
// vite.config.js or app.css
import '/vendor/blackpig-creatif/ephemeride/resources/css/ephemeride.css'
```

Or reference the published file at `public/vendor/ephemeride/ephemeride.css`.

---

## EphemerisEvent

`EphemerisEvent` is a `final` readonly-property DTO. Construct instances via the static factory:

```php
EphemerisEvent::make(
    id:          'evt-123',
    title:       'Team Stand-up',
    startsAt:    Carbon::parse('2026-03-10 09:00'),
    endsAt:      Carbon::parse('2026-03-10 09:30'),
    url:         'https://example.com/events/123',  // optional — activates popover CTA
    description: 'Daily sync.',                      // optional — shown in popover body
    colour:      'oklch(0.55 0.2 160)',             // optional — overrides theme event colour
    category:    'Internal',                         // optional — used for filtering
    rrule:       'FREQ=WEEKLY;BYDAY=MO,WE,FR',      // optional — RFC 5545 RRULE string
    imageUrl:    'https://example.com/thumb.jpg',    // optional — shown in popover
);
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | `string` | Unique identifier. Recurring occurrences append `-{YYYY-MM-DD}` automatically. |
| `title` | `string` | Displayed in chips and the popover header. |
| `startsAt` | `Carbon` | Inclusive start. For all-day events, set to midnight (`00:00`). |
| `endsAt` | `Carbon` | Inclusive end. For all-day events, set to midnight of the last day. |
| `url` | `?string` | Activates the CTA button in the event popover. |
| `description` | `?string` | Shown in the popover body. |
| `imageUrl` | `?string` | Thumbnail shown in the popover. |
| `colour` | `?string` | Any valid CSS colour value. Overrides `--ephemeride-color-event` for this event. |
| `category` | `?string` | Used by the optional category filter. |
| `rrule` | `?string` | RFC 5545 RRULE string (omit the `RRULE:` prefix). |
| `exdates` | `array` | Dates to exclude from a recurring series. |
| `rdates` | `array` | Additional one-off dates to include in a series. |
| `extraAttributes` | `array` | Pass-through data for consuming code or custom Blade slots. |

### Computed property hooks

Read-only virtual properties — no storage, computed on access:

| Property | Type | Value |
|----------|------|-------|
| `durationInMinutes` | `int` | `endsAt->diffInMinutes(startsAt)` |
| `formattedTimeRange` | `string` | e.g. `'09:00 – 10:30'` |
| `isAllDay` | `bool` | `true` when both `startsAt` and `endsAt` are at midnight |

### All-day events

Set both `startsAt` and `endsAt` to midnight (`00:00`). Multi-day all-day events — where `endsAt > startsAt` — render as horizontal spanning banners in both month and week views. The `endsAt` date is **inclusive**: an event from Mar 10 to Mar 12 covers all three days.

```php
EphemerisEvent::make(
    id:       'retreat',
    title:    'Spring Retreat',
    startsAt: Carbon::parse('2026-03-10 00:00'),
    endsAt:   Carbon::parse('2026-03-12 00:00'),
);
```

---

## Recurring Events

Pass an RFC 5545 RRULE string to the `rrule` property. Éphéméride uses `rlanvin/php-rrule` internally — omit the `RRULE:` prefix.

```php
EphemerisEvent::make(
    id:       'standup',
    title:    'Daily Stand-up',
    startsAt: Carbon::parse('2026-01-01 09:00'),
    endsAt:   Carbon::parse('2026-01-01 09:30'),
    rrule:    'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
);
```

Exclusions and additional dates are supported via `exdates` and `rdates`:

```php
EphemerisEvent::make(
    id:       'standup',
    title:    'Daily Stand-up',
    startsAt: Carbon::parse('2026-01-01 09:00'),
    endsAt:   Carbon::parse('2026-01-01 09:30'),
    rrule:    'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
    exdates:  [new \DateTime('2026-03-17')],
);
```

Expansion is bounded to the current grid window (not just the calendar month), so recurring events that fall on leading or trailing days from adjacent months are included correctly.

---

## Views

### Month view

A 42-cell (6×7) grid padded to six rows for layout stability. Multi-day all-day events render as horizontal spanning banners above the day cells for each week row. Per-day timed events stack up to `month_max_events_per_day` chips; any overflow shows a `+X more` label.

### Week view

A CSS Grid layout (not a `<table>`). Day columns are aligned to the calendar week; rows represent time slots at the configured interval. Timed events are positioned and sized relative to `week_start_hour`. All-day events occupy a pinned row above the time grid and span multiple day columns where appropriate. Times outside `week_start_hour`–`week_end_hour` are not rendered.

### Controlling available views

```blade
{{-- Both views, starting with month (default) --}}
<livewire:ephemeride-calendar :provider="App\BlackpigCreatif\Ephemeride\FestivalsProvider::class" />

{{-- Month only — view switcher is hidden automatically --}}
<livewire:ephemeride-calendar :provider="..." :views="['month']" />

{{-- Start in week view --}}
<livewire:ephemeride-calendar :provider="..." default-view="week" />
```

---

## Theming

All colours, radii, and font references in the Blade templates use CSS custom properties. There are no hardcoded colour values.

### CSS overrides

Override the root tokens in your own stylesheet after importing the package CSS:

```css
:root {
    --ephemeride-color-primary:  oklch(0.55 0.2 160);
    --ephemeride-color-event:    oklch(0.55 0.2 160);
    --ephemeride-color-today-bg: oklch(0.55 0.2 160);
    --ephemeride-radius:         0.75rem;
}
```

### Config-level defaults

Values in `config/ephemeride.php` under `theme` are applied as inline custom properties to every component instance:

```php
'theme' => [
    'color-primary'  => 'oklch(0.55 0.2 160)',
    'color-event'    => 'oklch(0.55 0.2 160)',
    'color-today-bg' => 'oklch(0.55 0.2 160)',
    'radius'         => '0.5rem',
],
```

### Per-instance overrides

The `:theme` prop applies scoped custom properties to the component's root element, overriding config defaults for that instance only:

```blade
<livewire:ephemeride-calendar
    :provider="App\BlackpigCreatif\Ephemeride\FestivalsProvider::class"
    :theme="['color-primary' => 'oklch(0.6 0.15 30)', 'color-event' => 'oklch(0.6 0.15 30)']"
/>
```

### Available tokens

| Token | Default | Description |
|-------|---------|-------------|
| `--ephemeride-color-primary` | `oklch(0.55 0.2 270)` | Navigation buttons, active states |
| `--ephemeride-color-surface` | `oklch(1 0 0)` | Day cell and grid background |
| `--ephemeride-color-surface-alt` | `oklch(0.97 0 0)` | Out-of-month day cells |
| `--ephemeride-color-border` | `oklch(0.9 0 0)` | Grid lines and dividers |
| `--ephemeride-color-text` | `oklch(0.15 0 0)` | Primary text |
| `--ephemeride-color-text-muted` | `oklch(0.55 0 0)` | Day numbers, time labels |
| `--ephemeride-color-today-bg` | `oklch(0.55 0.2 270)` | Today date number circle background |
| `--ephemeride-color-today-text` | `oklch(1 0 0)` | Today date number text |
| `--ephemeride-color-event` | `oklch(0.55 0.2 270)` | Default event chip background |
| `--ephemeride-color-popover-bg` | `oklch(1 0 0)` | Event popover background |
| `--ephemeride-radius` | `0.5rem` | Border radius for chips, popovers, buttons |

---

## Category Filtering

Enable the filter bar by passing `:filterable="true"`. Categories are derived automatically from the `category` property on the events in the current grid window — no configuration needed.

```blade
<livewire:ephemeride-calendar :provider="..." :filterable="true" />
```

Clicking a category chip filters the rendered events to that category; clicking again clears the filter. The active category resets when the view period changes.

---

## Configuration Reference

Published to `config/ephemeride.php`:

```php
return [
    // Views available in the switcher. Supported: 'month', 'week'.
    'views'                    => ['month', 'week'],

    // The view shown on first render.
    'default_view'             => 'month',

    // Hour range for the week view time grid. Events outside this window are not shown.
    'week_start_hour'          => 7,
    'week_end_hour'            => 21,

    // Slot interval in minutes.
    'slot_interval'            => 30,

    // First day of the week. 1 = Monday (ISO), 0 = Sunday.
    'week_starts_at'           => 1,

    // Maximum event chips per day cell before '+X more' is shown.
    'month_max_events_per_day' => 3,

    // Label for the CTA button in the event popover (shown when the event has a url).
    'popover_cta_label'        => 'View Details',

    // Global CSS custom property overrides. Merged with package defaults.
    // Per-instance :theme prop values take precedence over these.
    'theme'                    => [],
];
```

---

## Architecture

The component is thin at the boundary. It resolves the provider class via `app($provider)`, calls `getEphemerides($from, $to)` with the full grid window, expands recurring events through `EventExpander` (using `RSet` to handle RRULE, EXDATE, and RDATE in a single pass), then passes the flat collection to `MonthGrid` or `WeekGrid` for placement geometry. The Livewire component class holds no event data; everything is derived on each render.

Three namespacing systems are registered independently:

| System | Tag |
|--------|-----|
| Livewire component | `<livewire:ephemeride-calendar />` |
| Internal view namespace | `ephemeride::livewire.calendar` |
| Anonymous Blade components | `<x-ephemeride::event-chip />` etc. |

Provider classes are resolved through the container, so constructor injection works as expected:

```php
namespace App\BlackpigCreatif\Ephemeride;

class FestivalsProvider implements ProvidesEphemerides
{
    public function __construct(private readonly EventRepository $repository) {}

    public function getEphemerides(Carbon $from, Carbon $to): Collection
    {
        return $this->repository->forWindow($from, $to)->map(/* ... */);
    }
}
```

If the provider requires explicit binding:

```php
$this->app->bind(FestivalsProvider::class, fn () => new FestivalsProvider(
    app(EventRepository::class),
));
```

---

## Artisan Commands

### Make Provider

```bash
php artisan ephemeride:make-provider Festivals
```

Generates `app/BlackpigCreatif/Ephemeride/FestivalsProvider.php` — a scaffolded class implementing `ProvidesEphemerides` with a commented example body.

The `Provider` suffix is appended automatically and deduplicated, so all of the following produce `FestivalsProvider`:

```bash
php artisan ephemeride:make-provider Festivals
php artisan ephemeride:make-provider FestivalsProvider
```

If no name is given the command will prompt for one:

```bash
php artisan ephemeride:make-provider
# What should the provider be called? (e.g., Festivals, Events, Retreats)
```

---

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Credits

- [Stuart Hallewell](https://github.com/blackpig-creatif)
- [All Contributors](../../contributors)

## License

MIT. See [LICENSE](LICENSE.md).
