@props(['label', 'value', 'highlight' => false])

<div class="flex items-center justify-between border-b py-2" style="border-color: var(--theme-border);">
    <span class="text-sm opacity-75">{{ $label }}</span>
    <span class="{{ $highlight ? 'font-semibold' : '' }}">{{ $value }}</span>
</div>
