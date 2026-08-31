@extends('layouts.dashboard')
@section('title', 'Statistik Pariwisata')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-extrabold">Statistik &amp; Capaian Program</h2>
    <div class="flex items-center gap-2">
        @foreach ($tahunList as $t)
            <a href="{{ route('statistik.index', ['tahun' => $t]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t == $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
        @endforeach
    </div>
</div>

<div class="card p-5 mb-4">
    <h3 class="font-bold mb-2">Tren Bulanan <span class="text-xs text-slate-400 font-semibold">TA {{ $bulanTahun }}</span></h3>
    <div class="h-64"><canvas data-chart='@json($statChart)'></canvas></div>
</div>

<div class="space-y-4">
    @forelse ($programs as $p)
        <div class="card overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-teal-600">Program</p>
                    <h3 class="font-bold text-[14.5px] leading-snug">{{ $p->nama }}</h3>
                </div>
                <div class="text-right">
                    <p class="text-[11px] text-slate-400">Pagu</p>
                    <p class="font-extrabold text-sm">Rp {{ number_format($p->pagu_anggaran, 0, ',', '.') }}</p>
                </div>
            </div>
            <table class="data w-full">
                <thead><tr><th>Kegiatan / Sub Kegiatan</th><th class="text-right">Target</th><th class="text-right">Capaian</th><th class="min-w-[220px]">Persentase</th></tr></thead>
                <tbody>
                @foreach ($subs->filter(fn ($s) => true) as $s)
                    @php
                        $masuk = $p->urutan && $s->urutan > $p->urutan && $s->urutan < ($programs->first(fn ($x) => $x->urutan > $p->urutan)?->urutan ?? 99999);
                    @endphp
                    @if ($masuk)
                        <tr>
                            <td class="text-[13px] font-medium" style="padding-left: {{ $s->level === 'kegiatan' ? '1.25rem' : '0' }};">
                                @if ($s->level === 'kegiatan')<span class="font-bold text-slate-600">{{ $s->nama }}</span>@else
                                    {{ $s->nama }}<span class="text-[11px] text-slate-400 block">{{ $s->indikator ? 'Indikator: ' . $s->indikator : '' }}</span>
                                @endif
                            </td>
                            <td class="text-right text-xs tabular-nums whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $s->target, 2, ',', '.'), '0'), ',') }} {{ $s->satuan }}</td>
                            <td class="text-right text-xs tabular-nums font-bold">{{ rtrim(rtrim(number_format((float) $s->capaian, 2, ',', '.'), '0'), ',') }}</td>
                            <td><x-prog :persen="$s->persentase" /></td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <x-empty icon="statistik" title="Belum ada data statistik TA {{ $tahun }}" desc="Data dimuat oleh admin dari file kinerja/PARIWISATA." />
    @endforelse
</div>
@endsection
