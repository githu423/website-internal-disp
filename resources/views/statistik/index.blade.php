@extends('layouts.dashboard')
@section('title', 'Statistik Pariwisata')
@section('content')
@php
    $fmt = function ($v, $sat) {
        if ($v === null) return '—';
        $v = (float) $v;
        if ($sat === 'Persen') return number_format($v, 2, ',', '.') . '%';
        return abs($v - round($v)) > 0.00001 ? number_format($v, 2, ',', '.') : number_format($v, 0, ',', '.');
    };
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Statistik Pariwisata Daerah</h2>
        <p class="text-[12px] text-slate-400 mt-0.5">{{ $totalSemua }} indikator
            @if (count($tahunList)) · {{ $tahunList[0] }}–{{ $tahunList[count($tahunList) - 1] }} @endif
            — rekap resmi Dinas per tahun. Klik baris untuk melihat grafik trennya.</p>
    </div>
    <form method="GET" class="flex gap-2">
        @if ($selected && !request('q'))<input type="hidden" name="ind" value="{{ $selected->id }}">@endif
        <input name="q" value="{{ request('q') }}" placeholder="Cari indikator…"
            class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
        <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 active:scale-95 transition">Cari</button>
    </form>
</div>

@if ($totalSemua === 0)
    <div class="card p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 grid place-items-center mb-3 text-xl">📊</div>
        <h3 class="font-bold">Belum ada data statistik</h3>
        <p class="text-[13px] text-slate-400 mt-1">Admin dapat mengimpor file Excel/CSV "Data Statistik Pariwisata"<br>melalui menu Admin ▸ Statistik.</p>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('admin.statistik.index') }}" class="btn-main inline-flex mt-4">Buka halaman import</a>
        @endif
    </div>
@else
    @if ($chart)
        <div class="card p-5 mb-4">
            <div class="flex items-start justify-between gap-3 mb-2">
                <h3 class="font-bold leading-snug">Tren — <span class="text-teal-700">{{ $selected->indikator }}</span>
                    @if ($selected->satuan)<span class="text-[11px] text-slate-400 font-bold">({{ $selected->satuan }})</span>@endif</h3>
                <span class="text-[11px] text-slate-400 shrink-0 mt-1">klik baris tabel untuk ganti indikator</span>
            </div>
            <div class="h-64"><canvas data-chart='@json($chart)' data-type="line"></canvas></div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[860px]">
                <thead><tr>
                    <th class="w-12">No.</th><th>Indikator</th><th class="w-20">Satuan</th>
                    @foreach ($tahunList as $t)<th class="text-right whitespace-nowrap">{{ $t }}</th>@endforeach
                    <th class="w-28">Keterangan</th>
                </tr></thead>
                <tbody>
                @forelse ($rows as $r)
                    @php $nv = $r->nilais->keyBy('tahun'); @endphp
                    <tr @class(['cursor-pointer transition', 'bg-teal-50/70' => $selected && $selected->id === $r->id])
                        onclick="location.href='{{ route('statistik.index', array_filter(['ind' => $r->id, 'q' => request('q')])) }}'"
                        title="Tampilkan grafik tren indikator ini">
                        <td class="text-xs text-slate-400">{{ $r->no_urut ?? '—' }}</td>
                        <td class="font-semibold text-[13px]">{{ $r->indikator }}</td>
                        <td class="text-[11px] text-slate-500 whitespace-nowrap">{{ $r->satuan ?? '—' }}</td>
                        @foreach ($tahunList as $t)
                            <td class="text-right text-[12.5px] font-mono {{ $nv->get($t)?->nilai === null ? 'text-slate-300' : 'text-slate-700' }}">{{ $fmt($nv->get($t)?->nilai, $r->satuan) }}</td>
                        @endforeach
                        <td class="text-[11px] text-slate-400">{{ $r->keterangan ?: '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 4 + count($tahunList) }}"><x-empty icon="kinerja" title="Indikator tidak ditemukan" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="px-5 py-2.5 border-t border-slate-100 text-[11px] text-slate-400">Sumber: rekap resmi Bidang/Secretariat — diperbarui lewat import Excel atau isian manual admin. Angka "—" = belum ada data (n/a), bukan nol.</p>
    </div>
@endif
@endsection
