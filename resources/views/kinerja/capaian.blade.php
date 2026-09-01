@extends('layouts.dashboard')
@section('title', 'Capaian Kinerja')
@section('content')

@php
    $warnaPersen = function ($num) {
        if ($num === null) return 'bg-slate-100 text-slate-500';
        if ($num >= 100)    return 'bg-emerald-100 text-emerald-800';
        if ($num >= 80)     return 'bg-amber-100 text-amber-800';
        return 'bg-rose-100 text-rose-800';
    };
    $totalIndikator = $sasarans->sum(fn ($s) => $s->indikator->count());
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Capaian Kinerja &amp; Sasaran Strategis</h2>
        <p class="text-sm text-slate-500">Matriks target vs realisasi tiap indikator kinerja dinas, {{ $years[0] }}–{{ $last }} (sumber: berkas capaian kinerja resmi sedinas).</p>
    </div>
    <div class="flex items-center gap-2 text-sm font-bold">
        <a href="{{ route('kinerja.publik') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-600 hover:border-teal-400 active:scale-95 transition">Kegiatan Fisik</a>
        <a href="{{ route('kinerja.capaian') }}" @class(['rounded-lg px-3 py-1.5 border transition active:scale-95', $tampilan === 'matriks' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>Matriks</a>
        <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}" @class(['rounded-lg px-3 py-1.5 border transition active:scale-95', $tampilan === 'statistik' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>Statistik</a>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <x-stat label="Sasaran Strategis" :value="$sasarans->count()" tone="sky" icon="statistik" />
    <x-stat label="Indikator Kinerja" :value="$totalIndikator" tone="teal" icon="kinerja" />
    <x-stat :label="'Tuntas ' . $last" :value="$tercapai . '/' . $dinilai" tone="emerald" icon="kinerja" />
    <x-stat label="Rentang Tahun" :value="$years[0] . '–' . $last" tone="rose" icon="alert" />
</div>

@if ($tampilan === 'matriks')
<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[1080px]">
            <thead>
                <tr class="text-left">
                    <th rowspan="2" class="align-bottom w-10">No.</th>
                    <th rowspan="2" class="align-bottom min-w-[220px]">Sasaran</th>
                    <th rowspan="2" class="align-bottom min-w-[220px]">Indikator Kinerja</th>
                    <th rowspan="2" class="align-bottom w-20">Satuan</th>
                    @foreach ($years as $y)
                        <th colspan="3" class="align-bottom text-center border-l border-slate-200 @if($y == $last) bg-teal-50/60 text-teal-800 @endif">{{ $y }}</th>
                    @endforeach
                </tr>
                <tr class="text-[11px] uppercase tracking-wide text-slate-400">
                    @foreach ($years as $y)
                        <th class="text-right border-l border-slate-200 @if($y == $last) bg-teal-50/60 @endif">Target</th>
                        <th class="text-right @if($y == $last) bg-teal-50/60 @endif">Realisasi</th>
                        <th class="text-center @if($y == $last) bg-teal-50/60 @endif">%</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($sasarans as $s)
                    @php $inds = $s->indikator; $n = max($inds->count(), 1); @endphp
                    @foreach ($inds as $k => $ind)
                        @php $by = $ind->tahunan->keyBy('tahun'); @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50/70">
                            @if ($k === 0)
                                <td rowspan="{{ $n }}" class="align-top font-bold text-slate-500 border-t border-slate-100">{{ $s->nomor }}</td>
                                <td rowspan="{{ $n }}" class="align-top font-semibold border-t border-slate-100">
                                    {{ $s->judul }}
                                    @if ($s->cakupan)<div class="mt-0.5 text-[11px] font-medium text-slate-400">Sasaran Tahun : {{ $s->cakupan }}</div>@endif
                                </td>
                            @endif
                            <td class="align-top">{{ $ind->nama }}</td>
                            <td class="align-top text-slate-500">{{ $ind->satuan ?: '—' }}</td>
                            @foreach ($years as $y)
                                @php $nilai = $by->get($y); $num = $nilai ? \App\Models\CapaianIndikator::persenNum($nilai->persen) : null; @endphp
                                <td class="text-right border-l border-slate-100 align-top">{{ $nilai?->target ?? '—' }}</td>
                                <td class="font-bold align-top">{{ $nilai?->realisasi ?? '—' }}</td>
                                <td class="text-center align-top">
                                    <span class="inline-block rounded-md px-1.5 py-0.5 text-xs font-bold {{ $nilai && $nilai->persen !== null ? $warnaPersen($num) : 'bg-slate-100 text-slate-400' }}">
                                        {{ $nilai?->persen ?? '—' }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($inds->isEmpty())
                        <tr class="border-t border-slate-100">
                            <td class="font-bold text-slate-500">{{ $s->nomor }}</td>
                            <td class="font-semibold">{{ $s->judul }}</td>
                            <td colspan="{{ 2 + 3 * count($years) }}" class="text-slate-400 italic">Belum ada indikator untuk sasaran ini.</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="{{ 4 + 3 * count($years) }}" class="py-10 text-center text-slate-400">
                        Belum ada data capaian. Admin dapat mengimpor berkas CAPAIAN KINERJA melalui menu <b>Kelola Capaian Kinerja</b>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-2.5 text-[11px] text-slate-400 border-t border-slate-100">
        Sel bertanda “—” berarti tidak ada data pada berkas sumber (n/a). Persen ≥100 hijau (tercapai), 80–99 kuning, &lt;80 merah.
    </div>
</div>
@else
<div class="card p-4 mb-4">
    <h3 class="font-bold">Tingkat capaian keseluruhan per tahun</h3>
    <p class="text-xs text-slate-500 mt-0.5">Persentase indikator bernilai yang mencapai target (≥100%), per tahun.</p>
    <div class="h-56 mt-2"><canvas data-type="line" data-chart='@json($ringkasChart)'></canvas></div>
</div>
<div class="grid md:grid-cols-2 gap-4">
    @foreach ($charts as $c)
        <div class="card p-4">
            <div class="text-[11px] font-semibold text-slate-400">{{ $c['sasaran'] }}</div>
            <div class="font-bold leading-snug">{{ $c['nama'] }}@if ($c['satuan']) <span class="font-medium text-slate-400">({{ $c['satuan'] }})</span>@endif</div>
            @if ($c['chart'])
                <div class="h-52 mt-2"><canvas data-type="bar" data-chart='@json($c['chart'])'></canvas></div>
            @else
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($c['chips'] as $ch)
                        <span class="rounded-lg px-2 py-1 text-xs font-bold {{ $ch['ok'] === null ? 'bg-slate-100 text-slate-500' : ($ch['ok'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-700') }}">{{ $ch['tahun'] }}: {{ $ch['teks'] }}</span>
                    @endforeach
                </div>
                <p class="mt-1.5 text-[11px] text-slate-400">Nilai berupa predikat/teks — ditampilkan apa adanya.</p>
            @endif
        </div>
    @endforeach
    @if (!$charts)
        <div class="card p-10 text-center text-slate-400 md:col-span-2">Belum ada data capaian untuk divisualkan — impor atau isi manual dulu di menu admin.</div>
    @endif
</div>
<p class="mt-3 text-[11px] text-slate-400">Baru abu-abu = target · bar hijau = realisasi · garis oranye = persen capaian (sumbu kanan). Rentang tahun mengikuti data yang ada.</p>
@endif
@endsection
