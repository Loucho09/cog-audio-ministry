{{-- Flat card: 1px hairline border, no shadow. Hover only changes the border colour. --}}
<div {{ $attributes->class(['flex h-full flex-col rounded-console border border-text/10 bg-surface p-6 transition-colors hover:border-primary/40 lg:p-8']) }}>
    {{ $slot }}
</div>
