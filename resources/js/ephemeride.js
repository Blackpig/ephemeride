/**
 * Éphéméride — Alpine.js component definitions
 * blackpig-creatif/ephemeride
 *
 * Register these with Alpine before it initialises:
 *
 *   import Alpine from 'alpinejs'
 *   import { ephemeride } from 'vendor/blackpig-creatif/ephemeride/resources/js/ephemeride.js'
 *   ephemeride(Alpine)
 *   Alpine.start()
 *
 * Or with a CDN/script-tag setup, the components are registered on
 * document.addEventListener('alpine:init', ...) automatically when this
 * script is included after the Alpine CDN script.
 */

/**
 * Register all Éphéméride Alpine components with the given Alpine instance.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
function ephemeride(Alpine) {
    /**
     * ephemeride-popover
     *
     * Used on event chip wrappers in both month and week views.
     * Click-to-open is the primary interaction (mobile-compatible).
     * Hover state on desktop is additive only.
     *
     * Usage:
     *   <div x-data="ephemeride-popover">
     *     <button @click="toggle" @keydown.escape="close">chip content</button>
     *     <div x-show="open" x-transition @click.away="close">popover content</div>
     *   </div>
     */
    Alpine.data('ephemeride-popover', () => ({
        open: false,

        toggle() {
            this.open = !this.open
        },

        close() {
            this.open = false
        },
    }))
}

// Auto-register on alpine:init when loaded as a plain script tag
if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined') {
            ephemeride(Alpine)
        }
    })
}

export { ephemeride }
