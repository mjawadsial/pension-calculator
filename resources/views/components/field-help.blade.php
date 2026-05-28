@props([
    'enTitle' => '',
    'enText' => '',
    'urTitle' => '',
    'urText' => '',
])
@php($isUrdu = app()->getLocale() === 'ur')

<span
    class="field-help-wrap inline-flex"
    x-data="{
        showTooltip: false,
        showModal: false,
        tooltipPlacement: 'right',
        isDesktop: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
        openHelp() {
            if (this.isDesktop) {
                this.showTooltip = true;
                this.$nextTick(() => this.updatePlacement());
            } else {
                this.showModal = true;
            }
        },
        closeHelp() { this.showTooltip = false; this.showModal = false; },
        updatePlacement() {
            const tip = this.$refs.tooltip;
            const wrap = this.$el.getBoundingClientRect();
            if (!tip) {
                return;
            }
            const tipWidth = tip.offsetWidth || 288;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            this.tooltipPlacement = wrap.left + tipWidth + 16 > viewportWidth ? 'left' : 'right';
        }
    }"
    @mouseenter="if (isDesktop) openHelp()"
    @mouseleave="if (isDesktop) closeHelp()"
>
    <button
        type="button"
        class="help-dot"
        aria-label="Field help"
        @click.prevent="openHelp()"
    >?</button>

    <div
        x-ref="tooltip"
        x-show="showTooltip && isDesktop"
        x-transition
        class="help-tooltip"
        :class="tooltipPlacement === 'left' ? 'help-tooltip-left' : 'help-tooltip-right'"
        style="display: none;"
    >
        @if($isUrdu)
            <p class="help-title" dir="rtl">{{ $urTitle }}</p>
            <p class="help-body" dir="rtl">{{ $urText }}</p>
        @else
            <p class="help-title">{{ $enTitle }}</p>
            <p class="help-body">{{ $enText }}</p>
        @endif
    </div>

    <div x-show="showModal && !isDesktop" x-transition class="help-modal-wrap" style="display: none;" @click.self="closeHelp()">
        <div class="panel app-modal help-modal-content rounded-xl p-4">
            <div class="mb-2 flex items-center justify-between">
                <h4 class="text-sm font-semibold" @if($isUrdu) dir="rtl" @endif>{{ $isUrdu ? $urTitle : $enTitle }}</h4>
                <button type="button" class="btn-secondary inline-flex h-8 w-8 items-center justify-center rounded-md p-0" @click="closeHelp()" aria-label="Close help">
                    <x-app-icon name="x-mark" class="icon-fixed-white h-4 w-4" />
                </button>
            </div>
            <p class="help-body mb-3" @if($isUrdu) dir="rtl" @endif>{{ $isUrdu ? $urText : $enText }}</p>
        </div>
    </div>
</span>
