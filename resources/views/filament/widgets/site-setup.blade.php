<x-filament-widgets::widget>
    <x-filament::section heading="Site setup" :description="$remaining === 0 ? 'Everything is filled in.' : $remaining.' item(s) still missing.'">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach ($items as $item)
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="flex-shrink: 0;">
                        <x-filament::badge
                            :color="$item['done'] ? 'success' : 'warning'"
                            :icon="$item['done'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle'"
                        >
                            {{ $item['done'] ? 'Done' : 'Missing' }}
                        </x-filament::badge>
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-weight: 500;">{{ $item['label'] }}</div>
                        <div>
                            @if (! $item['done'] && ! empty($item['url']))
                                <x-filament::link :href="$item['url']" size="sm">{{ $item['hint'] }}</x-filament::link>
                            @else
                                <span class="fi-section-header-description">{{ $item['hint'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
