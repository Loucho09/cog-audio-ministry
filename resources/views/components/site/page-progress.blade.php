{{-- Navigation progress bar. Included once in each layout, right after <body>.
     Behaviour lives in resources/js/page-progress.js (starts on link clicks and form submits). --}}
<div
    data-page-progress
    class="pointer-events-none fixed inset-x-0 top-0 z-[60] h-[3px] overflow-hidden opacity-0 transition-opacity duration-200 ease-out data-[active=true]:opacity-100"
>
    <div
        aria-hidden="true"
        data-page-progress-bar
        class="h-full w-0 rounded-r-full bg-primary shadow-[0_0_8px_1px] shadow-primary/60 transition-[width] duration-300 ease-out"
    ></div>

    {{-- The page's only polite live region for loading messages ("Loading page", "Saving…"). Written by resources/js/live-region.js.
         The visual bar is hidden from assistive tech. --}}
    <span data-page-progress-status data-live-region role="status" aria-live="polite" class="sr-only"></span>
</div>
