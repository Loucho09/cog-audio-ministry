@props(['variant' => 'table', 'columnSpan' => [], 'columnStart' => []])

@php
    $rows = match ($variant) { 'checklist' => 6, default => 4 };
@endphp

<style>
    @keyframes cog-skel { 0%, 100% { opacity: 1; } 50% { opacity: 0.45; } }
    .cog-skel-bar { background-color: var(--gray-200, #e5e7eb); border-radius: 0.375rem; animation: cog-skel 1.5s ease-in-out infinite; }
    :is(.dark *).cog-skel-bar, .dark .cog-skel-bar { background-color: var(--gray-700, #374151); }
    @media (prefers-reduced-motion: reduce) { .cog-skel-bar { animation: none; } }
</style>

<div
    role="status"
    aria-busy="true"
    {{ (new \Filament\Support\View\ComponentAttributeBag)->gridColumn($columnSpan, $columnStart)->class(['fi-section']) }}
    style="padding: 1.5rem;"
>
    <span class="fi-sr-only">Loading…</span>

    <div aria-hidden="true">
        @if ($variant === 'stats')
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem;">
                @foreach (range(1, 4) as $i)
                    <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                        <div class="cog-skel-bar" style="height: 0.75rem; width: 40%;"></div>
                        <div class="cog-skel-bar" style="height: 1.75rem; width: 30%;"></div>
                        <div class="cog-skel-bar" style="height: 0.75rem; width: 55%;"></div>
                    </div>
                @endforeach
            </div>
        @elseif ($variant === 'checklist')
            <div class="cog-skel-bar" style="height: 1.25rem; width: 10rem; margin-bottom: 1.25rem;"></div>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach (range(1, $rows) as $i)
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div class="cog-skel-bar" style="height: 1.5rem; width: 4.5rem; border-radius: 9999px; flex-shrink: 0;"></div>
                        <div style="flex: 1; display: flex; flex-direction: column; gap: 0.4rem;">
                            <div class="cog-skel-bar" style="height: 0.9rem; width: 45%;"></div>
                            <div class="cog-skel-bar" style="height: 0.7rem; width: 65%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="cog-skel-bar" style="height: 1.25rem; width: 10rem; margin-bottom: 1.25rem;"></div>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach (range(1, $rows) as $i)
                    <div style="display: flex; gap: 1rem;">
                        <div class="cog-skel-bar" style="height: 1rem; flex: 2;"></div>
                        <div class="cog-skel-bar" style="height: 1rem; flex: 3;"></div>
                        <div class="cog-skel-bar" style="height: 1rem; flex: 1;"></div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
