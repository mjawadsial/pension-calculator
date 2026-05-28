@props([
    'downAction',
    'upAction',
])

<div class="stepper-controls">
    <button type="button" class="stepper-btn stepper-btn-down" x-on:click="{{ $downAction }}">
        <x-app-icon name="chevron-down" class="stepper-icon" />
    </button>
    <button type="button" class="stepper-btn stepper-btn-up" x-on:click="{{ $upAction }}">
        <x-app-icon name="chevron-up" class="stepper-icon" />
    </button>
</div>
