<x-filament-widgets::widget>
    <x-filament::section heading="Site setup" :description="$remaining === 0 ? 'Everything is filled in.' : $remaining.' item(s) still missing.'">
        <ul class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($items as $item)
                <li class="flex items-start gap-3 py-3">
                    <x-filament::icon
                        :icon="$item['done'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle'"
                        @class([
                            'mt-0.5 h-5 w-5 shrink-0',
                            'text-success-600 dark:text-success-400' => $item['done'],
                            'text-warning-600 dark:text-warning-400' => ! $item['done'],
                        ])
                    />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ $item['label'] }}
                            <span class="sr-only">{{ $item['done'] ? '(done)' : '(missing)' }}</span>
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if (! $item['done'] && ! empty($item['url']))
                                <a href="{{ $item['url'] }}" class="underline">{{ $item['hint'] }}</a>
                            @else
                                {{ $item['hint'] }}
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
