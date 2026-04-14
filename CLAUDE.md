# Éphéméride

A Livewire calendar component for displaying events — part of the BlackpigCreatif package ecosystem. Renders month and week views with support for recurring events (iCalendar RRULE), category filtering, and themeable CSS custom properties.

## Package-Specific Notes

- Event data is provided via the `ProvidesEphemerides` interface — the package never touches Eloquent directly.
- `EphemerisEvent` uses PHP 8.4 property hooks for computed properties (`isAllDay`, `formattedTimeRange`, `durationInMinutes`) — no backing storage for these.
- Recurring event expansion is handled by `EventExpander` using `rlanvin/php-rrule`.
- CSS is scoped via custom properties (`--ephemeride-*`) — no Tailwind dependency.
- Blade components are registered under the `ephemeride::` namespace.
- The Livewire component is registered as `<livewire:ephemeride-calendar />`.
