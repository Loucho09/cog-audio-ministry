@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm font-medium text-primary-dark', 'role' => 'status']) }}>
        {{ $status }}
    </div>
@endif
