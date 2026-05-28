@extends('layouts.app')

@section('content')
<div class="result-panel panel rounded-lg p-5">
    <div class="mb-4 border-b pb-3" style="border-color: var(--theme-border);">
        <h2 class="text-xl font-semibold">{{ __('app.summary') }}</h2>
        <p class="text-sm opacity-75">
            {{ $validated['name'] }} - BPS {{ $validated['bps'] }} - {{ __('app.retired_on') }} {{ \App\Support\DateInput::toDisplay($validated['date_of_retirement']) }}
        </p>
    </div>

    <x-result-row :label="__('app.qualifying_service')" :value="$result['service']['rounded_years'] . ' years'" />
    <x-result-row :label="__('app.pensionable_pay')" :value="'Rs. ' . number_format($result['avg_pay'], 2)" />
    @if($result['early_retirement_penalty']['applies'])
        <x-result-row :label="__('app.gross_pension_before_penalty')" :value="'Rs. ' . number_format($result['early_retirement_penalty']['gross_pension_before'], 2)" />
        <x-result-row :label="__('app.retirement_age')" :value="$result['retirement_age'] . ' ' . __('app.years')" />
        <x-result-row
            :label="__('app.early_retirement_penalty')"
            :value="number_format($result['early_retirement_penalty']['penalty_percentage'], 2) . '%'"
        />
    @endif
    <x-result-row :label="__('app.gross_pension')" :value="'Rs. ' . number_format($result['gross_pension'], 2)" />

    @if($result['commutation']['pct'] > 0)
        <x-result-row :label="__('app.age_next_birthday')" :value="$result['commutation']['age_next_birthday']" />
        <x-result-row :label="__('app.commutation_factor')" :value="number_format($result['commutation']['commutation_factor'], 4)" />
        <x-result-row :label="__('app.commuted_monthly')" :value="'Rs. ' . number_format($result['commutation']['commuted_monthly'], 2)" />
        <x-result-row :label="__('app.commutation_lumpsum')" :value="'Rs. ' . number_format($result['commutation']['lump_sum'], 2)" :highlight="true" />
    @endif

    <x-result-row :label="__('app.net_pension')" :value="'Rs. ' . number_format($result['commutation']['net_pension'], 2)" />

    <div class="mt-5 border-t pt-4" style="border-color: var(--theme-border);">
        <h3 class="mb-3 text-base font-semibold">{{ __('app.increases_in_net_pension') }}</h3>
        <div class="space-y-2 text-sm">
            @foreach($result['increases']['breakdown'] as $item)
                <div class="flex flex-wrap items-baseline justify-between gap-2 border-b py-2" style="border-color: var(--theme-border);">
                    <span>{{ $item['label'] }}</span>
                    <span class="font-medium">Rs. {{ number_format($item['increase'], 2) }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-4 space-y-2 border-t pt-3 text-sm" style="border-color: var(--theme-border);">
            <x-result-row :label="__('app.net_pension_after_increases')" :value="'Rs. ' . number_format($result['final_pension'], 2)" />
            <x-result-row :label="__('app.medical_allowance_after_increases')" :value="'Rs. ' . number_format($result['final_medical_allowance'], 2)" />
        </div>

        <div class="mt-4 border-t pt-4" style="border-color: var(--theme-border);">
            <x-result-row
                :label="__('app.net_pension_payable')"
                :value="'Rs. ' . number_format($result['net_pension_payable'], 2)"
                :highlight="true"
            />
        </div>
    </div>

    @if($result['service']['capped'])
        <p class="mt-3 text-sm">{{ __('app.service_capped') }}</p>
    @endif
    @if($result['service_warning'])
        <p class="mt-1 text-sm">{{ __('app.service_warning') }}</p>
    @endif
    @if($result['min_applied'])
        <p class="mt-1 text-sm">{{ __('app.minimum_applied') }}</p>
    @endif

    <p class="mt-5 text-xs opacity-75">
        {{ __('app.disclaimer') }}
    </p>
</div>

<div class="no-print mt-4 flex gap-3">
    <button class="btn-primary rounded-md px-3 py-2" onclick="window.print()">{{ __('app.print_pdf') }}</button>
    <a href="{{ route('pension.index') }}" class="btn-secondary rounded-md px-3 py-2">{{ __('app.recalculate') }}</a>
</div>
@endsection
