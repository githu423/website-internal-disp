@props(['ext', 'mime' => ''])
@php
    $ext = strtolower((string) $ext);
    $grad = match (true) {
        $ext === 'pdf' => 'from-rose-500 to-red-600',
        in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true) => 'from-fuchsia-500 to-purple-600',
        in_array($ext, ['xlsx', 'xls', 'ods', 'csv'], true) => 'from-emerald-500 to-green-600',
        in_array($ext, ['doc', 'docx', 'rtf'], true) => 'from-sky-500 to-blue-600',
        in_array($ext, ['ppt', 'pptx'], true) => 'from-amber-500 to-orange-600',
        in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'], true) => 'from-slate-400 to-slate-600',
        $ext === 'txt' => 'from-slate-400 to-slate-600',
        default => 'from-indigo-400 to-purple-600',
    };
    $label = $ext ?: 'file';
@endphp
<span {{ $attributes->merge(['class' => 'file-ico relative inline-flex shrink-0 items-center justify-center bg-gradient-to-br text-white shadow-sm ' . $grad]) }}
    style="clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%);"
    title="{{ strtoupper($label) }}">
    {{-- lipatan pojok kanan atas (kesan kertas) --}}
    <span class="absolute right-0 top-0 h-3 w-3 bg-white/40" style="clip-path: polygon(100% 0, 0 0, 100% 100%);"></span>
    {{-- label ekstensi --}}
    <span class="mt-1 text-[10px] font-black uppercase tracking-wide leading-none">{{ $label }}</span>
</span>