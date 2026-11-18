{{-- ===== HALAMAN: Komponen · kartu statistik. ===== --}}
@props(['label', 'value', 'sub' => null, 'tone' => 'teal', 'href' => null, 'icon' => 'dashboard', 'dataKey' => null])
@php $tones = ['teal' => 'bg-green-100 text-green-600', 'sky' => 'bg-blue-100 text-blue-600', 'amber' => 'bg-slate-100 text-slate-600', 'rose' => 'bg-rose-50 text-rose-600', 'violet' => 'bg-indigo-100 text-indigo-600', 'emerald' => 'bg-orange-100 text-orange-600', 'slate' => 'bg-slate-100 text-slate-600']; @endphp
<a {{ $href ? "href=$href" : '' }} {{ $attributes->merge(['class' => 'clean-card p-4 flex items-center gap-3.5 cursor-pointer ' . ($href ? 'hover:border-purple-200 hover:-translate-y-1 hover:shadow-md transition-all duration-200 group' : '')]) }}>
    <span
        class="h-11 w-11 shrink-0 rounded-xl grid place-items-center {{ $tones[$tone] ?? $tones['teal'] }} group-hover:scale-105 transition"><x-icon
            :name="$icon" class="h-5 w-5" /></span>
    <span class="min-w-0">
        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $label }}</span>
        <span class="block text-2xl font-black text-gray-900 leading-tight mt-0.5" {{ $dataKey ? 'data-kartu="' . $dataKey . '"' : '' }}>{{ $value }}</span>
        @if ($sub)<span class="block text-[11px] text-gray-500 truncate">{{ $sub }}</span>@endif
    </span>
</a>