{{-- ===== HALAMAN: Statistik — tabel indikator × tahun + grafik tren. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Statistik Pariwisata')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Data & Statistik Pariwisata Kabupaten Ciamis')

@section('content')
@php
    $u = auth()->user();
    $fmt = function ($v, $sat) {
        if ($v === null) return '—';
        $v = (float) $v;
        if ($sat === 'Persen') return number_format($v, 2, ',', '.') . '%';
        return abs($v - round($v)) > 0.00001 ? number_format($v, 2, ',', '.') : number_format($v, 0, ',', '.');
    };
@endphp

<div class="space-y-6">

    {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Data &amp; Kinerja</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Statistik Pariwisata</span>
    </nav>

    {{-- ===== BAGIAN: Header Halaman & Quick Actions ===== --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Statistik Pariwisata Daerah</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $totalSemua }} Indikator
                </span>
                @if (count($tahunList))
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        {{ $tahunList[0] }}–{{ $tahunList[count($tahunList) - 1] }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">Rekap resmi Dinas per tahun. Klik baris tabel untuk memvisualkan grafik tren indikator.</p>
        </div>

        {{-- ===== BAGIAN: Quick Actions & Search ===== --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <form method="GET" class="flex items-center gap-2">
                @if ($selected && !request('q'))
                    <input type="hidden" name="ind" value="{{ $selected->id }}">
                @endif
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input name="q" value="{{ request('q') }}" placeholder="Cari indikator…"
                        class="w-56 pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs md:text-sm bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </div>
                <button class="px-3.5 py-2 rounded-xl bg-slate-900 text-white text-xs md:text-sm font-semibold hover:bg-slate-800 active:scale-95 transition shadow-sm">
                    Cari
                </button>
            </form>

            @if($u->isAdmin())
            <a href="{{ route('admin.statistik.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Kelola Statistik
            </a>
            @endif
        </div>
    </div>

    @if ($totalSemua === 0)
        <div class="bg-white border border-slate-200 rounded-xl p-10 text-center shadow-sm">
            <div class="mx-auto h-14 w-14 rounded-2xl bg-purple-50 text-purple-600 grid place-items-center mb-3.5 text-2xl border border-purple-100">📊</div>
            <h3 class="text-base font-bold text-slate-800">Belum ada data statistik pariwisata</h3>
            <p class="text-xs text-slate-400 mt-1">Admin dapat mengimpor file Excel/CSV "Data Statistik Pariwisata" melalui menu Kelola Statistik.</p>
            @if ($u->isAdmin())
                <a href="{{ route('admin.statistik.index') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-purple-700 transition">
                    Buka Halaman Kelola / Impor →
                </a>
            @endif
        </div>
    @else
        {{-- ===== BAGIAN: Grafik Tren Indikator Terpilih ===== --}}
        @if ($chart)
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-[10.5px] font-extrabold uppercase tracking-wider text-purple-600">Visualisasi Tren</span>
                        <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                            <span>📈</span> Tren — {{ $selected->indikator }}
                            @if ($selected->satuan)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                    {{ $selected->satuan }}
                                </span>
                            @endif
                        </h3>
                    </div>
                    <span class="text-xs text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        Klik baris tabel di bawah untuk mengganti indikator
                    </span>
                </div>
                <div class="h-64 mt-2">
                    <canvas data-chart='@json($chart)' data-type="line"></canvas>
                </div>
            </div>
        @endif

        {{-- ===== BAGIAN: Kontainer Tabel Data Indikator ===== --}}
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full min-w-[860px]">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                            <th class="w-12 text-center">No.</th>
                            <th>Indikator Statistik</th>
                            <th class="w-24">Satuan</th>
                            @foreach ($tahunList as $t)
                                <th class="text-right whitespace-nowrap px-3">{{ $t }}</th>
                            @endforeach
                            <th class="w-32">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $r)
                        @php 
                            $nv = $r->nilais->keyBy('tahun'); 
                            $isSelected = $selected && $selected->id === $r->id;
                        @endphp
                        <tr class="cursor-pointer transition-colors duration-150 {{ $isSelected ? 'bg-purple-50/80 font-medium' : 'hover:bg-slate-50/80' }}"
                            onclick="location.href='{{ route('statistik.index', array_filter(['ind' => $r->id, 'q' => request('q')])) }}'"
                            title="Klik untuk melihat grafik tren {{ $r->indikator }}">
                            <td class="text-xs text-slate-400 text-center font-mono {{ $isSelected ? 'text-purple-700 font-bold' : '' }}">
                                {{ $r->no_urut ?? '—' }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if ($isSelected)
                                        <span class="h-2 w-2 rounded-full bg-purple-600 shrink-0"></span>
                                    @endif
                                    <span class="text-[13px] {{ $isSelected ? 'text-purple-900 font-bold' : 'text-slate-900 font-semibold' }}">
                                        {{ $r->indikator }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-xs text-slate-500 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px]">
                                    {{ $r->satuan ?? '—' }}
                                </span>
                            </td>
                            @foreach ($tahunList as $t)
                                <td class="text-right text-xs font-mono px-3 tabular-nums {{ $nv->get($t)?->nilai === null ? 'text-slate-300' : ($isSelected ? 'text-purple-900 font-bold' : 'text-slate-700') }}">
                                    {{ $fmt($nv->get($t)?->nilai, $r->satuan) }}
                                </td>
                            @endforeach
                            <td class="text-xs text-slate-400">
                                {{ $r->keterangan ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($tahunList) }}" class="py-8">
                                <x-empty icon="kinerja" title="Indikator statistik tidak ditemukan" desc="Coba gunakan kata kunci pencarian yang lain." />
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Tanda “—” menunjukkan ketiadaan data pada tahun terkait (n/a).</span>
                <span class="font-medium text-slate-500">Sumber: Rekapitulasi Resmi Dinas Pariwisata Kab. Ciamis</span>
            </div>
        </div>
    @endif

</div>
@endsection

