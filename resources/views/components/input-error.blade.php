@props(['messages'])

@if ($messages)
    {{-- accent-text (not accent) so the small red text passes AA in both themes. --}}
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-accent-text']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
