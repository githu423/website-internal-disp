@props([
    'name' => 'id_kategori',
    'teksName' => 'kategori',
    'options' => [],
    'value' => '',
    'placeholder' => 'Pilih kategori…',
    'autoSubmit' => false,
    'withNew' => true,
    'suggestions' => [],
    'listId' => null,
    'treeLabel' => 'Kategori / Subkategori',
    'newLabel' => 'Atau buat kategori baru',
    'help' => null,
    'teksPlaceholder' => 'Contoh: Laporan / Tahun 2026 / Triwulan I',
    'class' => '',
    'triggerClass' => 'rounded-xl px-3.5 py-2 text-xs sm:text-sm',
    'teksClass' => 'rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2 text-sm',
])

@php
    static $kpUid = 0;
    $kpUid++;
    $uid = 'kategori-picker-' . $kpUid;
    $listId = $listId ?? ($uid . '-list');
    $valueStr = (string) $value;
    $label = $placeholder;
    $found = false;
    foreach ($options as $ov => $ol) {
        if ((string) $ov === $valueStr) {
            $label = (string) $ol;
            $found = true;
            break;
        }
    }
    if (!$found && $valueStr !== '') {
        $label = $valueStr;
    }
    $showNew = $withNew && $newLabel !== null && $newLabel !== '';
@endphp

<div id="{{ $uid }}">
    @if ($treeLabel !== null && $treeLabel !== '')
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ $treeLabel }}</label>
    @endif

    <div class="relative {{ $class }}" data-custom-select @if($autoSubmit) data-autosubmit="1" @endif>
        <input type="hidden" name="{{ $name }}" value="{{ $valueStr }}">
        <button type="button" data-cs-trigger
            class="flex w-full items-center justify-between gap-2.5 border border-slate-200 bg-white text-slate-700 shadow-sm hover:border-purple-300 hover:bg-slate-50/50 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/15 outline-none active:scale-[.99] transition font-medium {{ $triggerClass }}">
            <span data-cs-label class="truncate text-left">{{ $label }}</span>
            <svg data-cs-chevron class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div data-cs-menu
            class="absolute left-0 top-full z-[80] mt-1.5 hidden w-full min-w-[200px] overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xl shadow-purple-950/10">
            <ul class="max-h-60 overflow-y-auto thin-scroll py-1 divide-y divide-slate-50">
                @foreach ($options as $optValue => $optLabel)
                    @php $optValueStr = (string) $optValue; @endphp
                    <li>
                        <button type="button" data-cs-option data-value="{{ $optValueStr }}" @class([
                            'flex w-full items-center justify-between gap-2.5 px-3.5 py-2.5 text-left text-xs sm:text-sm transition',
                            'bg-purple-50/90 text-purple-700 font-bold' => $optValueStr === $valueStr,
                            'text-slate-600 hover:bg-purple-50/50 hover:text-purple-700 font-medium' => $optValueStr !== $valueStr,
                        ])>
                            <span class="truncate">{{ $optLabel }}</span>
                            @if ($optValueStr === $valueStr)
                                <svg class="h-4 w-4 shrink-0 text-purple-600 cs-check" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($showNew)
        <label class="mt-3 block text-xs font-medium text-slate-500">
            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $newLabel }}</span>
            <input type="text" name="{{ $teksName }}" value="{{ old($teksName) }}" list="{{ $listId }}"
                placeholder="{{ $teksPlaceholder }}"
                class="w-full {{ $teksClass }} focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            @if ($help)
                <span class="mt-1 block text-[10.5px] text-slate-400">{{ $help }}</span>
            @endif
        </label>
        @if (!empty($suggestions))
            <datalist id="{{ $listId }}">
                @foreach ($suggestions as $sug)
                    <option value="{{ $sug }}">
                @endforeach
            </datalist>
        @endif
    @endif
</div>

@if ($showNew)
    <script>
        (function () {
            var root = document.getElementById('{{ $uid }}');
            if (!root) return;
            var hidden = root.querySelector('input[name="{{ $name }}"]');
            var text = root.querySelector('input[name="{{ $teksName }}"]');
            var label = root.querySelector('[data-cs-label]');
            if (!hidden || !text || !label) return;

            var optMap = {};
            root.querySelectorAll('[data-cs-option]').forEach(function (opt) {
                var span = opt.querySelector('span');
                optMap[opt.getAttribute('data-value')] = span ? span.textContent.trim() : '';
                opt.addEventListener('click', function () { text.value = ''; });
            });

            var placeholder = @json($placeholder);
            text.addEventListener('input', function () {
                if (text.value.trim() !== '') {
                    hidden.value = '';
                    label.textContent = 'Pakai isian kategori baru';
                } else {
                    label.textContent = optMap[hidden.value] || placeholder;
                }
            });
        })();
    </script>
@endif