{{--
    Éphéméride event panel — target container component.

    Place this anywhere on your page alongside a calendar configured with
    `target-container="..."`. When an event chip is clicked the calendar
    dispatches an `ephemeride-event-selected` browser event; this component
    listens and renders the event details.

    The outer wrapper always occupies layout space (visibility: hidden when
    empty) to prevent layout shift. The inner content fades in on selection.

    Usage:
        <x-ephemeride::event-panel />
        <x-ephemeride::event-panel class="my-custom-class" />

    To build a fully custom panel, listen for the same event yourself:
        <div x-data="{ event: null }" @ephemeride-event-selected.window="event = $event.detail">
            <div x-show="event" x-text="event?.title"></div>
        </div>
--}}
<div
    {{ $attributes->merge(['class' => 'ephemeride-panel']) }}
    x-data="{ event: null }"
    @ephemeride-event-selected.window="event = $event.detail"
>
    <div
        class="ephemeride-panel-inner"
        :class="{ 'is-visible': event !== null }"
        aria-live="polite"
    >
        {{-- Left: main content --}}
        <div class="ephemeride-panel-body">

            {{-- Category badge --}}
            <div
                class="ephemeride-panel-category"
                x-show="event?.category"
                x-text="event?.category"
            ></div>

            {{-- Title --}}
            <div class="ephemeride-panel-title" x-text="event?.title"></div>

            {{-- Date / time meta --}}
            <div class="ephemeride-panel-meta" x-text="event?.formattedDate"></div>

            {{-- Image --}}
            <img
                x-show="event?.imageUrl"
                :src="event?.imageUrl ?? ''"
                :alt="event?.title ?? ''"
                class="ephemeride-panel-image"
                loading="lazy"
            >

            {{-- Description --}}
            <p
                class="ephemeride-panel-description"
                x-show="event?.description"
                x-text="event?.description"
            ></p>

        </div>

        {{-- Right: actions + close --}}
        <div class="ephemeride-panel-actions">

            {{-- Close / clear button --}}
            <button
                type="button"
                class="ephemeride-panel-close"
                @click="event = null"
                aria-label="Close event details"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>

            {{--
                CTA links — `links` array takes priority over the single `url` field.
                Each link: { label, url, style: 'primary'|'secondary'|'ghost' }
            --}}
            <div class="ephemeride-panel-ctas">

                {{-- Multiple links (links array) --}}
                <template x-if="event?.links?.length">
                    <div class="ephemeride-panel-ctas-inner">
                        <template x-for="link in event.links" :key="link.url">
                            <a
                                :href="link.url"
                                class="ephemeride-panel-cta"
                                :class="'ephemeride-panel-cta--' + (link.style ?? 'primary')"
                                x-text="link.label"
                            ></a>
                        </template>
                    </div>
                </template>

                {{-- Single URL fallback (backwards compat) --}}
                <template x-if="!(event?.links?.length) && event?.url">
                    <a
                        :href="event.url"
                        class="ephemeride-panel-cta ephemeride-panel-cta--primary"
                    >
                        {{ config('ephemeride.popover_cta_label', 'View Details') }}
                    </a>
                </template>

            </div>

        </div>
    </div>
</div>
