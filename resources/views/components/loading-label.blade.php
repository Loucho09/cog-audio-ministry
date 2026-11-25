{{--
    Swaps a button's label for a spinner and loading text while the button has aria-busy="true"
    (set by resources/js/submit-guard.js). The parent <button> needs the "group" class.
    Both labels stay in the DOM, so screen readers and no-JS views get the normal label.
--}}
@props(['loadingText'])

<span class="group-aria-busy:hidden">{{ $slot }}</span>
<span class="hidden items-center gap-2 group-aria-busy:inline-flex">
    <x-spinner />
    {{ $loadingText }}
</span>
