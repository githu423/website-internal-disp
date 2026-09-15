@extends('layouts.dashboard')

@section('title', 'Capaian Kinerja & Sasaran')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Matriks Target vs Realisasi Sasaran Strategis Dinas')

@section('content')
@php
    $u = auth()->user();
    $warnaPersen = function ($num) {
        if ($num === null) return 'bg-slate-100 text-slate-500 border-slate-200';
        if ($num >= 100)    return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        if ($num >= 80)     return 'bg-amber-50 text-amber-700 border-amber-200';
        return 'bg-rose-50 text-rose-700 border-rose-200';
    };
    $totalIndikator = $sasarans->sum(fn ($s) => $s->indikator->count());
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Data &amp; Kinerja</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Capaian Kinerja</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Capaian Kinerja &amp; Sasaran Strategis</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $years[0] }}–{{ $last }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Matriks target vs realisasi tiap indikator kinerja resmi sedinas.</p>
        </div>

        <!-- Quick Actions Bar -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Mode Tampilan Switcher (Segmented Pill) -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                <a href="{{ route('kinerja.capaian') }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tampilan === 'matriks' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Matriks
                </a>
                <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}" 
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $tampilan === 'statistik' ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Grafik Tren
                </a>
            </div>

            <!-- Tombol Sekunder: Kegiatan Fisik -->
            <a href="{{ route('kinerja.publik') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                ← Kegiatan Fisik
            </a>

            <!-- Tombol Utama (Hanya Admin: Kelola Capaian Kinerja) -->
            @if($u->isAdmin())
            <a href="{{ route('admin.capaian.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Kelola Capaian
            </a>
            @endif
        </div>
    </div>

    <!-- 3. Stat Cards Ringkasan -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Sasaran Strategis</p>
                <p class="text-2xl font-black text-slate-900 mt-0.5">{{ $sasarans->count() }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Fokus utama dinas</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0">
                <x-icon name="statistik" class="h-5 w-5" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Indikator Kinerja</p>
                <p class="text-2xl font-black text-sky-600 mt-0.5">{{ $totalIndikator }}</p>
                <p class="text-[11px] text-sky-600/80 mt-0.5 font-medium">Tolok ukur keberhasilan</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shrink-0">
                <x-icon name="kinerja" class="h-5 w-5" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tuntas {{ $last }}</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">{{ $tercapai }}/{{ $dinilai }}</p>
                <p class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Target terlampaui</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Rentang Tahun</p>
                <p class="text-2xl font-black text-slate-800 mt-0.5">{{ $years[0] }}–{{ $last }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ count($years) }} periode data</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center border border-slate-200 shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
    </div>

    <!-- 4. Konten Matriks atau Grafik -->
    @if ($tampilan === 'matriks')
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[1080px]">
                <thead>
                    <tr class="text-left bg-slate-50/75 border-b border-slate-200 text-[11px] uppercase tracking-wider font-extrabold text-slate-600">
                        <th rowspan="2" class="align-bottom w-12 text-center">No.</th>
                        <th rowspan="2" class="align-bottom min-w-[240px]">Sasaran Strategis</th>
                        <th rowspan="2" class="align-bottom min-w-[240px]">Indikator Kinerja</th>
                        <th rowspan="2" class="align-bottom w-20">Satuan</th>
                        @foreach ($years as $y)
                            <th colspan="3" class="align-bottom text-center border-l border-slate-200 @if($y == $last) bg-purple-50/60 text-purple-900 font-extrabold @endif">
                                {{ $y }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="text-[10.5px] uppercase tracking-wide text-slate-400 bg-slate-50/50 border-b border-slate-200">
                        @foreach ($years as $y)
                            <th class="text-right border-l border-slate-200 @if($y == $last) bg-purple-50/40 text-purple-800 @endif">Target</th>
                            <th class="text-right @if($y == $last) bg-purple-50/40 text-purple-800 @endif">Realisasi</th>
                            <th class="text-center @if($y == $last) bg-purple-50/40 text-purple-800 @endif">%</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($sasarans as $s)
                        @php $inds = $s->indikator; $n = max($inds->count(), 1); @endphp
                        @foreach ($inds as $k => $ind)
                            @php $by = $ind->tahunan->keyBy('tahun'); @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                @if ($k === 0)
                                    <td rowspan="{{ $n }}" class="align-top font-mono font-bold text-center text-slate-500 bg-slate-50/30">
                                        {{ $s->nomor }}
                                    </td>
                                    <td rowspan="{{ $n }}" class="align-top font-semibold text-slate-900 bg-slate-50/30">
                                        {{ $s->judul }}
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold {{ $s->id_bidang ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                                {{ $s->bidang?->nama ?: 'LINTAS DINAS' }}
                                            </span>
                                            @if ($s->cakupan)
                                                <span class="rounded-md px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                                    TA: {{ $s->cakupan }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                <td class="align-top text-slate-800 font-medium">{{ $ind->nama }}</td>
                                <td class="align-top text-xs text-slate-500">{{ $ind->satuan ?: '—' }}</td>
                                @foreach ($years as $y)
                                    @php 
                                        $nilai = $by->get($y); 
                                        $num = $nilai ? \App\Models\CapaianIndikator::persenNum($nilai->persen) : null; 
                                    @endphp
                                    <td class="text-right border-l border-slate-100 align-top text-xs tabular-nums text-slate-600 @if($y == $last) bg-purple-50/10 @endif">
                                        {{ $nilai?->target ?? '—' }}
                                    </td>
                                    <td class="text-right align-top text-xs font-bold tabular-nums text-slate-900 @if($y == $last) bg-purple-50/10 @endif">
                                        {{ $nilai?->realisasi ?? '—' }}
                                    </td>
                                    <td class="text-center align-top @if($y == $last) bg-purple-50/10 @endif">
                                        @if ($nilai && $nilai->persen !== null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $warnaPersen($num) }}">
                                                {{ $nilai->persen }}
                                            </span>
                                        @else
                                            <span class="text-slate-300 text-xs">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        @if ($inds->isEmpty())
                            <tr class="hover:bg-slate-50/70">
                                <td class="font-mono font-bold text-center text-slate-500 bg-slate-50/30">{{ $s->nomor }}</td>
                                <td class="font-semibold text-slate-900 bg-slate-50/30">
                                    {{ $s->judul }}
                                    <span class="mt-1 block w-fit rounded-md px-2 py-0.5 text-[10px] font-bold {{ $s->id_bidang ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        {{ $s->bidang?->nama ?: 'LINTAS DINAS' }}
                                    </span>
                                </td>
                                <td colspan="{{ 2 + 3 * count($years) }}" class="text-slate-400 italic text-xs py-3">
                                    Belum ada indikator terdaftar untuk sasaran ini.
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ 4 + 3 * count($years) }}" class="py-10 text-center">
                                <x-empty icon="kinerja" title="Belum ada data capaian kinerja" desc="Admin dapat mengimpor data melalui menu Kelola Capaian Kinerja." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 bg-slate-50/60 text-xs text-slate-500 border-t border-slate-100 flex flex-wrap items-center gap-3">
            <span class="font-bold text-slate-700">Keterangan:</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> ≥100% Tercapai</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500"></span> 80–99% Perlu Perhatian</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500"></span> &lt;80% Belum Tercapai</span>
            <span class="inline-flex items-center gap-1.5 text-slate-400"><span class="h-2 w-2 rounded-full bg-slate-300"></span> — Data Kosong (n/a)</span>
        </div>
    </div>
    @else
    <!-- Tampilan Mode Grafik & Visualisasi Tren -->
    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3 mb-2">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Tingkat Capaian Keseluruhan per Tahun</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Persentase indikator yang memenuhi atau melampaui target (≥100%) tiap tahun.</p>
                </div>
                <span class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm border border-purple-100">
                    📈
                </span>
            </div>
            <div class="h-60 mt-3">
                <canvas data-type="line" data-chart='@json($ringkasChart)'></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($charts as $c)
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[11px] font-semibold text-slate-400 truncate">{{ $c['sasaran'] }}</span>
                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-[9.5px] font-extrabold {{ !empty($c['bidang']) ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                {{ $c['bidang'] ?: 'LINTAS DINAS' }}
                            </span>
                        </div>
                        <h4 class="font-bold text-slate-900 text-[13.5px] leading-snug">
                            {{ $c['nama'] }}
                            @if ($c['satuan']) <span class="font-normal text-slate-400 text-xs">({{ $c['satuan'] }})</span>@endif
                        </h4>
                    </div>

                    <div class="mt-3">
                        @if ($c['chart'])
                            <div class="h-48"><canvas data-type="bar" data-chart='@json($c['chart'])'></canvas></div>
                        @else
                            <div class="flex flex-wrap gap-1.5 py-2">
                                @foreach ($c['chips'] as $ch)
                                    <span class="rounded-lg px-2.5 py-1 text-xs font-bold border {{ $ch['ok'] === null ? 'bg-slate-50 text-slate-600 border-slate-200' : ($ch['ok'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200') }}">
                                        {{ $ch['tahun'] }}: {{ $ch['teks'] }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Nilai bersifat kualitatif/predikat dinas.</p>
                        @endif
                    </div>
                </div>
            @endforeach

            @if (!$charts)
                <div class="col-span-2 bg-white border border-slate-200 rounded-xl p-10 text-center text-slate-400 shadow-sm">
                    Belum ada visualisasi grafik untuk indikator kinerja yang dipilih.
                </div>
            @endif
        </div>
        <p class="text-xs text-slate-400">Keterangan: Bar abu-abu = target · Bar ungu/hijau = realisasi · Garis oranye = persentase capaian.</p>
    </div>
    @endif

</div>
@endsection

