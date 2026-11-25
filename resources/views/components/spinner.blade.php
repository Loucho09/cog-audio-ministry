{{-- Inline spinner for loading buttons. Uses currentColor, so it follows the button text in light and dark mode. --}}
<svg {{ $attributes->class(['h-4 w-4 shrink-0 animate-spin motion-reduce:animate-none']) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
</svg>
