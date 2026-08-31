@extends('layouts.dashboard')
@section('title', 'PAD — Retribusi Daerah')
@section('content')
@php $useNum = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','); @endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Pendapatan Asli Daerah (Retribusi)</h2>
        <p class="text-sm text-slate-500">Dikelola Dinas Pariwisata Kab. Ciamis · TA {{ $tahun }} · input manual &amp; import Excel/CSV oleh admin</p>
    </div>
    <div class="flex items-center gap-2">
        @foreach ($tahunList as $t)
            <a href="{{ route('pad.index', ['tahun' => $t]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t == $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
        @endforeach
    </div>
</div>

@php
$totalTarget = $sum?->target ?? 0; $totalRealisasi = $sum?->realisasi ?? 0;
$pct = $totalTarget > 0 ? round($totalRealisasi / $totalTarget * 100, 2) : 0;
@endphp
<div class="grid sm:grid-cols-3 gap-3.5 mb-4">
    <div class="card p-4"><p class="text-[11px] font-semibold uppercase text-slate-400">Target {{ $tahun }}</p><p class="text-xl font-extrabold mt-1">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p></div>
    <div class="card p-4"><p class="text-[11px] font-semibold uppercase text-slate-400">Kumulatif Realisasi</p><p class="text-xl font-extrabold mt-1 text-emerald-600">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</p></div>
    <div class="card p-4"><p class="text-[11px] font-semibold uppercase text-slate-400">Pencapaian</p><div class="mt-2.5"><x-prog :persen="$pct" /></div></div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[860px]">
            <thead><tr><th>Retribusi / Objek</th><th class="text-right">Target</th><th class="text-right">Realisasi Nov</th><th class="text-right">Realisasi Des</th><th class="text-right">Kumulatif</th><th class="text-right">Sisa Target</th><th class="min-w-[170px]">%</th></tr></thead>
            <tbody>
            @forelse ($rows->groupBy('kategori') as $kat => $grup)
                <tr class="bg-slate-50"><td colspan="7" class="font-bold text-[12.5px] uppercase tracking-wide text-slate-600">{{ $kat ?: 'Tanpa Kategori' }}</td></tr>
                @foreach ($grup as $r)
                    <tr>
                        <td><p class="font-semibold text-[13px]">{{ $r->objek }}</p><p class="text-[11px] text-slate-400">Rek. {{ $r->no_rekening ?: '—' }}</p></td>
                        <td class="text-right text-xs tabular-nums">{{ number_format($r->target_tahunan, 0, ',', '.') }}@if ($r->target_perubahan > 0)<br><span class="text-[10px] text-sky-600 font-bold">perubahan {{ number_format($r->target_perubahan, 0, ',', '.') }}</span>@endif</td>
                        <td class="text-right text-xs tabular-nums text-slate-500">{{ number_format($r->realisasi_bulan_lalu, 0, ',', '.') }}</td>
                        <td class="text-right text-xs tabular-nums text-slate-500">{{ number_format($r->realisasi_bulan_ini, 0, ',', '.') }}</td>
                        <td class="text-right text-xs tabular-nums font-bold">{{ number_format($r->kumulatif, 0, ',', '.') }}</td>
                        <td class="text-right text-xs tabular-nums {{ $r->sisa_target < 0 ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold' }}">{{ number_format($r->sisa_target, 0, ',', '.') }}</td>
                        <td><x-prog :persen="$r->persentase" /></td>
                    </tr>
                @endforeach
                @php $g = $perKategori[$kat] ?? null; @endphp
                @if ($g)
                <tr class="border-t border-slate-200 font-bold bg-slate-50/60">
                    <td class="text-[12px] text-slate-500">Jumlah {{ $kat }}</td>
                    <td class="text-right text-xs tabular-nums">{{ number_format($g['target'], 0, ',', '.') }}</td><td></td><td></td>
                    <td class="text-right text-xs tabular-nums">{{ number_format($g['realisasi'], 0, ',', '.') }}</td>
                    <td class="text-right text-xs tabular-nums {{ $g['realisasi'] - $g['target'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($g['realisasi'] - $g['target'], 0, ',', '.') }}</td>
                    <td><x-prog :persen="$g['target'] > 0 ? round($g['realisasi'] / $g['target'] * 100, 2) : 0" /></td>
                </tr>
                @endif
            @empty
                <tr><td colspan="7"><x-empty icon="pad" title="Belum ada data PAD TA {{ $tahun }}" desc="Admin dapat mengunggah file PAD (Excel/CSV) atau menambah manual lewat menu Kelola PAD." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-2.5 text-[11px] text-slate-400 border-t border-slate-100">Sisa Target = Kumulatif Realisasi − Target (minus = belum mencapai target). Sumber: file PAD resmi, tersusun otomatis oleh sistem.</div>
</div>
@endsection
