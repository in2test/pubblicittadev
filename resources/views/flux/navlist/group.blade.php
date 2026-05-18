@props([
    'expandable' => false,
    'expanded' => true,
    'heading' => null,
])

<?php if ($expandable && $heading): ?>

<ui-disclosure {{ $attributes->class('group/disclosure') }} @if ($expanded === true) open @endif
    data-flux-navlist-group>
    <button type="button"
        class="group/disclosure-button mb-0.5 flex h-10 w-full items-center rounded-lg text-gray-500 hover:bg-gray-800/5 hover:text-gray-800 lg:h-8">
        <div class="ps-3 pe-4">
            <flux:icon.chevron-down class="hidden size-3! group-data-open/disclosure-button:block" />
            <flux:icon.chevron-right class="block size-3! group-data-open/disclosure-button:hidden" />
        </div>

        <span class="text-sm font-medium leading-none">{{ $heading }}</span>
    </button>

    <div class="relative hidden space-y-0.5 ps-7 data-open:block" @if ($expanded === true) data-open @endif>
        <div class="absolute inset-y-0.75 inset-s-0 ms-4 w-px bg-gray-200"></div>

        {{ $slot }}
    </div>
</ui-disclosure>

<?php elseif ($heading): ?>

<div {{ $attributes->class('block space-y-[2px]') }}>
    <div class="px-1 py-2">
        <div class="text-xs leading-none text-gray-400">{{ $heading }}</div>
    </div>

    <div>
        {{ $slot }}
    </div>
</div>

<?php else: ?>

<div {{ $attributes->class('block space-y-[2px]') }}>
    {{ $slot }}
</div>

<?php endif; ?>
