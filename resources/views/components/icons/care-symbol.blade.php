@props(['symbol' => '', 'class' => 'w-6 h-6'])

@php
    $s = (string) $symbol;
@endphp

@switch($s)
    @case('30°')
    @case('30°_')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <path d="M3 10c2-1 4-1 6 0s4 1 6 0 4-1 6 0"/>
            <text x="12" y="16" text-anchor="middle" font-size="6.5" font-weight="bold" fill="currentColor" stroke="none">30°</text>
        </svg>
        @break

    @case('40°')
    @case('40°_')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <path d="M3 10c2-1 4-1 6 0s4 1 6 0 4-1 6 0"/>
            <text x="12" y="16" text-anchor="middle" font-size="6.5" font-weight="bold" fill="currentColor" stroke="none">40°</text>
        </svg>
        @break

    @case('60°')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <path d="M3 10c2-1 4-1 6 0s4 1 6 0 4-1 6 0"/>
            <text x="12" y="16" text-anchor="middle" font-size="6.5" font-weight="bold" fill="currentColor" stroke="none">60°</text>
        </svg>
        @break

    @case('95°')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <path d="M3 10c2-1 4-1 6 0s4 1 6 0 4-1 6 0"/>
            <text x="12" y="16" text-anchor="middle" font-size="6.5" font-weight="bold" fill="currentColor" stroke="none">95°</text>
        </svg>
        @break

    @case('hand')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 7h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 7z"/>
            <path d="M10 4v4m2-3v3m2-2v2"/>
        </svg>
        @break

    @case('no_wash')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <line x1="2" y1="2" x2="22" y2="22" stroke-width="2.2" class="text-red-500" stroke="currentColor"/>
        </svg>
        @break

    @case('no_bleach')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 3 22 20 2 20"/>
            <line x1="2" y1="2" x2="22" y2="22" stroke-width="2.2" class="text-red-500" stroke="currentColor"/>
        </svg>
        @break

    @case('bleach')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="12 3 22 20 2 20"/>
        </svg>
        @break

    @case('iron_1')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18h18c-1-6-4-10-10-10H5a2 2 0 0 0-2 2v8z"/>
            <path d="M6 8V6a2 2 0 0 1 2-2h8"/>
            <circle cx="11" cy="14" r="1" fill="currentColor"/>
        </svg>
        @break

    @case('iron_2')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18h18c-1-6-4-10-10-10H5a2 2 0 0 0-2 2v8z"/>
            <path d="M6 8V6a2 2 0 0 1 2-2h8"/>
            <circle cx="9" cy="14" r="1" fill="currentColor"/>
            <circle cx="13" cy="14" r="1" fill="currentColor"/>
        </svg>
        @break

    @case('iron_3')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18h18c-1-6-4-10-10-10H5a2 2 0 0 0-2 2v8z"/>
            <path d="M6 8V6a2 2 0 0 1 2-2h8"/>
            <circle cx="8" cy="14" r="1" fill="currentColor"/>
            <circle cx="11" cy="14" r="1" fill="currentColor"/>
            <circle cx="14" cy="14" r="1" fill="currentColor"/>
        </svg>
        @break

    @case('no_iron')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18h18c-1-6-4-10-10-10H5a2 2 0 0 0-2 2v8z"/>
            <path d="M6 8V6a2 2 0 0 1 2-2h8"/>
            <line x1="2" y1="2" x2="22" y2="22" stroke-width="2.2" class="text-red-500" stroke="currentColor"/>
        </svg>
        @break

    @case('no_tumble')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="12" cy="12" r="6"/>
            <line x1="2" y1="2" x2="22" y2="22" stroke-width="2.2" class="text-red-500" stroke="currentColor"/>
        </svg>
        @break

    @case('tumble')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="12" cy="12" r="6"/>
        </svg>
        @break

    @case('tumble_low')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <circle cx="12" cy="12" r="6"/>
            <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
        </svg>
        @break

    @case('no_dry_clean')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <line x1="2" y1="2" x2="22" y2="22" stroke-width="2.2" class="text-red-500" stroke="currentColor"/>
        </svg>
        @break

    @case('dry_clean_p')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <text x="12" y="16" text-anchor="middle" font-size="11" font-weight="bold" fill="currentColor" stroke="none">P</text>
        </svg>
        @break

    @case('dry_clean_f')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <text x="12" y="16" text-anchor="middle" font-size="11" font-weight="bold" fill="currentColor" stroke="none">F</text>
        </svg>
        @break

    @default
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18l-2 10a4 4 0 0 1-4 3H9a4 4 0 0 1-4-3L3 6z"/>
            <path d="M3 10c2-1 4-1 6 0s4 1 6 0 4-1 6 0"/>
        </svg>
@endswitch
