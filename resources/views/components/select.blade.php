@props([
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => null,
    'autoSubmit' => false,
    'class' => '',
    'buttonClass' => 'rounded-xl pl-3.5 pr-3 py-2 text-sm',
])
@php
    $value = (string) $value;
    $label = null;
    foreach ($options as $ov => $ol) {
        if ((string) $ov === $value) { $label = $ol; break; }
    }
    $label = $label ?? ($placeholder ?? ($value !== '' ? $value : 'Pilih…'));
@endphp
<div class="relative {{ $class }}" data-custom-select @if($autoSubmit) data-autosubmit="1" @endif>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <button type="button" data-cs-trigger class="flex w-full items-center justify-between gap-2 border border-slate-200 bg-white text-slate-600 hover:border-purple-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition {{ $buttonClass }}">
        <span data-cs-label class="truncate">{{ $label }}</span>
        <svg data-cs-chevron class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div data-cs-menu class="absolute left-0 z-50 mt-1.5 hidden w-full min-w-[220px] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
        <ul class="max-h-64 overflow-y-auto thin-scroll py-1">
            @foreach ($options as $optValue => $optLabel)
                @php $optValue = (string) $optValue; @endphp
                <li>
                    <button type="button" data-cs-option data-value="{{ $optValue }}" @class([
                        'flex w-full items-center justify-between gap-2 px-3.5 py-2 text-left text-sm transition',
                        'bg-purple-50 text-purple-700 font-semibold' => $optValue === $value,
                        'text-slate-600 hover:bg-slate-50 hover:text-purple-700' => $optValue !== $value,
                    ])>
                        <span class="truncate">{{ $optLabel }}</span>
                        @if ($optValue === $value)
                            <svg class="h-4 w-4 shrink-0 text-purple-600 cs-check" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>