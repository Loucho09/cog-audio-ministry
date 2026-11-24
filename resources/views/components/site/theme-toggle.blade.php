{{-- Light/dark theme toggle. The saved choice is applied early by components/site/theme-script.
     Icon-button classes: the group-data variants only apply inside a navbar marked data-solid="false" (the hero). --}}
<button
    type="button"
    data-theme-toggle
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        init() {
            // Follow the device setting live until the visitor picks a theme.
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                let saved = null;
                try { saved = localStorage.getItem('theme'); } catch (err) {}
                if (!saved) { this.apply(e.matches); }
            });
        },
        apply(dark) {
            this.dark = dark;
            document.documentElement.classList.toggle('dark', dark);
        },
        toggle() {
            this.apply(!this.dark);
            try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
        },
    }"
    @click="toggle()"
    aria-label="Dark mode"
    aria-pressed="false"
    :aria-pressed="dark.toString()"
    {{ $attributes->class('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-text transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark group-data-[solid=false]:text-white group-data-[solid=false]:hover:bg-white/10 group-data-[solid=false]:focus-visible:ring-white') }}
>
    {{-- Sun (shown in dark mode) --}}
    <svg x-show="dark" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
    </svg>
    {{-- Moon (shown in light mode) --}}
    <svg x-show="!dark" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
    </svg>
</button>
