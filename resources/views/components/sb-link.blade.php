{{-- ===== HALAMAN: Komponen · tautan sidebar. ===== --}}
@props(['href', 'label', 'icon', 'active' => false, 'badge' => null])
<a href="{{ $href }}" title="{{ $label }}" data-label="{{ $label }}" @class([
    'nav-link group',
    'is-active' => $active,
]) {!! $active ? 'aria-current="page"' : '' !!}>
    <span class="nav-ico shrink-0 flex items-center justify-center w-10 h-10 transition-[transform,color] duration-200 group-hover:scale-110 group-active:scale-90 {{ $active ? 'text-teal-300' : 'text-slate-400 group-hover:text-teal-300' }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <div class="sb-hide flex items-center justify-between flex-1 min-w-0">
        <span class="truncate">{{ $label }}</span>
        @if ($badge)<span class="text-[10px] font-bold bg-teal-500/90 text-white rounded-full px-1.5 py-0.5 shrink-0">{{ $badge }}</span>@endif
    </div>
</a>