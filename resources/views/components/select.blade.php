@props([
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => null,
    'autoSubmit' => false,
    'class' => '',
    'buttonClass' => 'rounded-xl px-3.5 py-2 text-xs sm:text-sm',
])
@php
    $value = (string) $value;
    $label = null;
    
    // Normalize options if passed as indexed or associative array / collection
    $normalizedOptions = [];
    foreach ($options as $k => $v) {
        if (is_numeric($k) && !is_object($v) && !is_array($v) && is_string($v) && !str_contains($k, '0')) {
            // Check if sequential or custom
            $normalizedOptions[$v] = ucfirst($v);
        } else {
            $normalizedOptions[(string) $k] = (string) $v;
        }
    }

    foreach ($normalizedOptions as $ov => $ol) {
        if ((string) $ov === $value) {
            $label = $ol;
            break;
        }
    }
    $label = $label ?? ($placeholder ?? ($value !== '' ? $value : 'Pilih…'));
@endphp
<div class="relative {{ $class }}" data-custom-select @if($autoSubmit) data-autosubmit="1" @endif>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <button type="button" data-cs-trigger 
            class="flex w-full items-center justify-between gap-2.5 border border-slate-200 bg-white text-slate-700 shadow-sm hover:border-purple-300 hover:bg-slate-50/50 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/15 outline-none active:scale-[.99] transition font-medium {{ $buttonClass }}">
        <span data-cs-label class="truncate text-left">{{ $label }}</span>
        <svg data-cs-chevron class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    <div data-cs-menu class="absolute left-0 top-full z-[80] mt-1.5 hidden w-full min-w-[200px] overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xl shadow-purple-950/10">
        <ul class="max-h-60 overflow-y-auto thin-scroll py-1 divide-y divide-slate-50">
            @foreach ($normalizedOptions as $optValue => $optLabel)
                @php $optValue = (string) $optValue; @endphp
                <li>
                    <button type="button" data-cs-option data-value="{{ $optValue }}" 
                            @class([
                                'flex w-full items-center justify-between gap-2.5 px-3.5 py-2.5 text-left text-xs sm:text-sm transition',
                                'bg-purple-50/90 text-purple-700 font-bold' => $optValue === $value,
                                'text-slate-600 hover:bg-purple-50/50 hover:text-purple-700 font-medium' => $optValue !== $value,
                            ])>
                        <span class="truncate">{{ $optLabel }}</span>
                        @if ($optValue === $value)
                            <svg class="h-4 w-4 shrink-0 text-purple-600 cs-check" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>
