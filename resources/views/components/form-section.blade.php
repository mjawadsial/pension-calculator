<div class="panel mb-4 overflow-hidden rounded-lg" x-data="{ open: true }">
    <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-4 py-3 text-left font-medium">
        <span>{{ $title }}</span>
        <span x-text="open ? '▲' : '▼'"></span>
    </button>
    <div x-show="open" class="grid grid-cols-1 gap-4 px-4 pb-4 md:grid-cols-2">
        {{ $slot }}
    </div>
</div>
