@props(['persen'])
@php $p = max(0, min(100, (float) $persen)); @endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <div class="progress flex-1"><span style="width: {{ $p }}%"></span></div>
    <span class="text-xs font-bold tabular-nums {{ $persen >= 100 ? 'text-emerald-600' : ($persen >= 60 ? 'text-sky-600' : 'text-amber-600') }} whitespace-nowrap">{{ number_format((float) $persen, 1, ',', '.') }}%</span>
</div>
