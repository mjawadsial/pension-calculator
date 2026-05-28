@props([
    'name',
    'style' => 'outline',
    'class' => 'h-5 w-5',
])

@php
    $prefix = $style === 'solid' ? 'heroicon-s' : 'heroicon-o';
    $component = $prefix . '-' . str($name)->replace('.', '-');
@endphp

<x-dynamic-component :component="$component" {{ $attributes->merge(['class' => $class]) }} />
