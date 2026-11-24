{{-- Destructive action: outlined in accent-text (AA in both themes), submit by default. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-full border border-accent-text bg-surface px-4 py-2 text-sm font-semibold tracking-wide text-accent-text transition duration-200 hover:bg-accent/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2']) }}>
    {{ $slot }}
</button>
