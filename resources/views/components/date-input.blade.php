@props([
    'name',
    'value' => '',
])

<input
    type="text"
    name="{{ $name }}"
    value="{{ \App\Support\DateInput::toDisplay(old($name, $value)) }}"
    placeholder="{{ __('app.date_placeholder') }}"
    inputmode="numeric"
    maxlength="10"
    autocomplete="off"
    pattern="\d{2}/\d{2}/\d{4}"
    title="{{ __('app.date_placeholder') }}"
    @input="mask($event)"
    @keydown="blockKey($event)"
    x-data="dateInputMask()"
    {{ $attributes->merge(['class' => 'input-core w-full rounded-md px-3 py-2 date-input-masked']) }}
    required
/>
