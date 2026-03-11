<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Available Views
    |--------------------------------------------------------------------------
    |
    | The calendar views that are enabled. Supported: "month", "week".
    | Override per-component via the :views prop.
    |
    */
    'views' => ['month', 'week'],

    /*
    |--------------------------------------------------------------------------
    | Default View
    |--------------------------------------------------------------------------
    |
    | The view shown when the calendar is first rendered.
    | Override per-component via the default-view prop.
    |
    */
    'default_view' => 'month',

    /*
    |--------------------------------------------------------------------------
    | Week Grid Time Range
    |--------------------------------------------------------------------------
    |
    | The hour range displayed in the week view time grid.
    | Times outside this range are not shown.
    |
    */
    'week_start_hour' => 7,
    'week_end_hour' => 21,

    /*
    |--------------------------------------------------------------------------
    | Slot Interval
    |--------------------------------------------------------------------------
    |
    | Time slot interval in minutes for the week view grid.
    |
    */
    'slot_interval' => 30,

    /*
    |--------------------------------------------------------------------------
    | Week Starts At
    |--------------------------------------------------------------------------
    |
    | The first day of the week. 1 = Monday (ISO, European default), 0 = Sunday.
    |
    */
    'week_starts_at' => 1,

    /*
    |--------------------------------------------------------------------------
    | Month View Event Overflow
    |--------------------------------------------------------------------------
    |
    | Maximum number of event chips shown per day cell in month view
    | before the "+X more" overflow label is shown.
    |
    */
    'month_max_events_per_day' => 3,

    /*
    |--------------------------------------------------------------------------
    | Popover CTA Label
    |--------------------------------------------------------------------------
    |
    | Default label for the call-to-action button in the event popover.
    | Shown when the event has a url.
    |
    */
    'popover_cta_label' => 'View Details',

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | Global CSS custom property overrides applied to all calendar instances.
    | Use valid CSS colour values — oklch() recommended for Tailwind v4 consistency.
    | These are merged with the package CSS defaults and can be overridden
    | per-instance via the :theme prop.
    |
    | Example:
    |   'color-primary'  => 'oklch(0.65 0.12 160)',
    |   'color-event'    => 'oklch(0.65 0.12 160)',
    |   'radius'         => '0.75rem',
    |
    */
    'theme' => [
        // 'color-primary'    => 'oklch(0.55 0.2 270)',
        // 'color-event'      => 'oklch(0.55 0.2 270)',
        // 'color-today-bg'   => 'oklch(0.55 0.2 270)',
        // 'radius'           => '0.5rem',
    ],

];
