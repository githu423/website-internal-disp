{{--
    Ikon identitas bidang — memakai set SVG & warna YANG SAMA dengan halaman
    Bidang (resources/views/bidang/index.blade.php & show.blade.php), supaya
    logo tiap bidang konsisten di seluruh aplikasi.

    Pemakaian:
      <x-bidang-icon :bidang="$a->bidang" />                 (kotak 8x8, default)
      <x-bidang-icon :bidang="$b" size="lg" />               (kotak 12x12)
      <x-bidang-icon :bidang="$b" :box="false" size="mini" /> (svg polos, tanpa kotak)
      <x-bidang-icon dinas />                                (ikon Agenda Dinas / umum)
--}}
@props([
    'bidang' => null,
    'size' => 'md',
    'box' => true,
    'boxClass' => null,
    'iconClass' => null,
    'dinas' => false,
])

@php
    $ukuran = [
        'mini' => ['box' => 'h-6 w-6 rounded-lg', 'icon' => 'h-3.5 w-3.5'],
        'sm' => ['box' => 'h-7 w-7 rounded-lg', 'icon' => 'h-4 w-4'],
        'md' => ['box' => 'h-8 w-8 rounded-xl', 'icon' => 'h-4 w-4'],
        'lg' => ['box' => 'h-12 w-12 rounded-2xl', 'icon' => 'h-6 w-6'],
    ][$size] ?? ['box' => 'h-8 w-8 rounded-xl', 'icon' => 'h-4 w-4'];

    // Peta identitas bidang: warna kotak + jalur SVG. Sama persis dengan
    // $getBidangMeta pada halaman Bidang agar tidak ada ikon yang berbeda.
    $petaBidang = function ($item) {
        $nama = strtolower($item->nama ?? '');
        $kode = strtolower($item->kode ?? '');

        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'box' => 'bg-emerald-100/80 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'box' => 'bg-sky-100/80 text-sky-700 border border-sky-200 shadow-sm shadow-sky-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'box' => 'bg-pink-100/80 text-pink-700 border border-pink-200 shadow-sm shadow-pink-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4 5 5 0 015-5c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4c-1.4 0-2 .6-3 1.5-.8.8-1.6 1.5-3 1.5a5 5 0 01-5-5 4 4 0 014-4c1.4 0 2 .6 3 1.5.8.8 1.6 1.5 3 1.5a5 5 0 005-5 4 4 0 00-4-4" />',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'box' => 'bg-amber-100/80 text-amber-700 border border-amber-200 shadow-sm shadow-amber-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'box' => 'bg-teal-100/80 text-teal-700 border border-teal-200 shadow-sm shadow-teal-500/10',
                'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            ];
        }

        return [
            'box' => 'bg-purple-100/80 text-purple-700 border border-purple-200 shadow-sm shadow-purple-500/10',
            'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
        ];
    };

    if ($dinas || !$bidang) {
        // Agenda dinas (lintas bidang) — gedung pemerintahan, nada gelap netral.
        $meta = [
            'box' => 'bg-slate-900 text-white border border-slate-900 shadow-sm',
            'path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V9.5L12 4l7 5.5V21M9 21v-5h6v5M9.5 12h.01M14.5 12h.01" />',
        ];
    } else {
        $meta = $petaBidang($bidang);
    }

    $svg =
        '<svg class="' .
        e($iconClass ?: $ukuran['icon']) .
        ' shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">' .
        $meta['path'] .
        '</svg>';
@endphp

@if ($box)
    <span
        {{ $attributes->merge(['class' => $ukuran['box'] . ' grid place-items-center shrink-0 ' . ($boxClass ?: $meta['box'])]) }}>
        {!! $svg !!}
    </span>
@else
    {!! $svg !!}
@endif
