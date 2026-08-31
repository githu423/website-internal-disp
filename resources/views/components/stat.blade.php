@props(['label', 'value', 'sub' => null, 'tone' => 'teal', 'href' => null, 'icon' => 'dashboard'])
@php $tones = ['teal'=>'bg-teal-50 text-teal-600','sky'=>'bg-sky-50 text-sky-600','amber'=>'bg-amber-50 text-amber-600','rose'=>'bg-rose-50 text-rose-600','violet'=>'bg-violet-50 text-violet-600','emerald'=>'bg-emerald-50 text-emerald-600']; @endphp
<a {{ $href ? "href=$href" : '' }} {{ $attributes->merge(['class' => 'card p-4 flex items-center gap-3.5 ' . ($href ? 'hover:border-teal-300 hover:shadow transition group' : '')]) }}>
    <span class="h-11 w-11 shrink-0 rounded-xl grid place-items-center {{ $tones[$tone] ?? $tones['teal'] }} group-hover:scale-105 transition"><x-icon :name="$icon" class="h-5 w-5" /></span>
    <span class="min-w-0">
        <span class="block text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</span>
        <span class="block text-xl font-extrabold text-slate-800 leading-tight">{{ $value }}</span>
        @if ($sub)<span class="block text-[11px] text-slate-500 truncate">{{ $sub }}</span>@endif
    </span>
</a>
