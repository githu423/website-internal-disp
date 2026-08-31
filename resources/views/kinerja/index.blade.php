@extends('layouts.dashboard')
@section('title', 'Kegiatan & Kinerja')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Daftar Kegiatan &amp; Capaian Kinerja <span class="text-slate-400 font-bold">TA {{ $tahun }}</span></h2>
        <p class="text-sm text-slate-500">Sumber: Kinerja Fisik Dinas — dikelola admin, tampil otomatis sebagai info &amp; pengingat kegiatan.</p>
    </div>
    <div class="flex items-center gap-2">
        @foreach ($tahunList as $t)
            <a href="{{ route('kinerja.publik', array_merge(request()->query(), ['tahun' => $t])) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t == $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <x-stat label="Total Sub Kegiatan" :value="(int) $ringkas->total" tone="sky" icon="kinerja" />
    <x-stat label="Tuntas (≥100%)" :value="(int) $ringkas->tuntas" tone="emerald" icon="kinerja" />
    <x-stat label="Berjalan (60–99%)" :value="(int) $ringkas->berjalan" tone="teal" icon="statistik" />
    <x-stat label="Perlu Perhatian (<60%)" :value="(int) $ringkas->lambat" tone="rose" icon="alert" />
</div>

<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap gap-2.5 items-center">
        <form class="flex-1 min-w-[200px] flex gap-2" method="GET">
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama kegiatan…" class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
            <select name="status" class="rounded-lg border border-slate-300 px-2 py-2 text-sm bg-white">
                <option value="">Semua status</option>
                @foreach (['tuntas' => 'Tuntas', 'berjalan' => 'Berjalan', 'lambat' => 'Perlu perhatian'] as $kv => $lab)
                    <option value="{{ $kv }}" @selected(request('status') == $kv)>{{ $lab }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 active:scale-95 transition">Cari</button>
        </form>
    </div>
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[820px]">
            <thead><tr><th class="w-8">#</th><th>Kegiatan</th><th>Indikator / Pagu</th><th>Pelaksana</th><th>Target</th><th class="min-w-[200px]">Capaian</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($items as $k)
                <tr>
                    <td class="text-slate-400 text-xs">{{ $items->firstItem() + $loop->index }}</td>
                    <td><p class="font-semibold text-[13px] leading-snug">{{ $k->nama }}</p>
                        @if ($k->catatan)<p class="text-[11px] text-amber-600 mt-0.5">⚠ {{ $k->catatan }}</p>@endif</td>
                    <td class="text-xs text-slate-500 max-w-[260px]">{{ $k->indikator ?? '—' }}<br><span class="text-slate-400">Pagu Rp {{ number_format($k->pagu_anggaran, 0, ',', '.') }}</span></td>
                    <td class="text-xs text-slate-500">{{ $k->bidang?->nama ?? 'Dinas' }}</td>
                    <td class="text-xs font-semibold whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} <span class="font-normal text-slate-400">{{ $k->satuan }}</span></td>
                    <td><x-prog :persen="$k->persentase" /></td>
                    <td>@php $st = $k->label_status; @endphp
                        <span class="text-[11px] font-bold rounded-full px-2 py-1 whitespace-nowrap {{ $st === 'Tuntas' ? 'bg-emerald-50 text-emerald-700' : ($st === 'Berjalan' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700') }}">{{ $st }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="kinerja" title="Tidak ada kegiatan yang cocok" desc="Coba kata kunci lain atau ganti tahun anggaran." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($items->hasPages())<div class="p-3">{{ $items->links() }}</div>@endif
</div>
@endsection
