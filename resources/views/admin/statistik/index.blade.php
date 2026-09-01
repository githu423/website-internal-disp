@extends('layouts.dashboard')
@section('title', 'Kelola Statistik')
@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Kelola Statistik Pariwisata</h2>
        <p class="text-sm text-slate-500">Tren bulanan (target vs capaian) yang menggerakkan grafik di halaman Statistik. Isi manual atau impor berkas per tahun.</p>
    </div>
    <a href="{{ route('statistik.index', ['tahun' => $tahun]) }}" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-bold text-white hover:bg-slate-700 active:scale-95 transition">👁 Lihat halaman statistik</a>
</div>

<div class="card p-4 mb-4 flex flex-wrap items-center gap-2">
    <span class="text-sm font-bold text-slate-500">Tahun:</span>
    @foreach ($tahunList as $t)
        <a href="{{ route('admin.statistik.index', ['tahun' => $t]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t === $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
    @endforeach
    <form method="GET" class="flex items-center gap-1.5 ml-auto">
        <select name="tahun" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
            @foreach ($tahunOptions as $y)
                @if (!in_array($y, $tahunList)) <option value="{{ $y }}">{{ $y }} (baru)</option> @endif
            @endforeach
        </select>
        <button class="rounded-lg border border-teal-500 bg-teal-50 px-2.5 py-1.5 text-sm font-bold text-teal-700 hover:bg-teal-100">Buka</button>
    </form>
</div>

<div class="grid lg:grid-cols-3 gap-4 items-start">
    <div class="card overflow-hidden lg:col-span-2">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold">Angka per Bulan — TA {{ $tahun }}</h3>
            <span class="text-[11px] text-slate-400">satua bebas (juta Rp / orang / kunjungan) — yang penting konsisten</span>
        </div>
        <form method="POST" action="{{ route('admin.statistik.store') }}">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full">
                    <thead><tr class="text-left text-[11px] uppercase tracking-wide text-slate-400"><th class="w-24">Bulan</th><th>Target</th><th>Capaian</th><th class="w-16 text-right">%</th></tr></thead>
                    <tbody>
                        @for ($b = 1; $b <= 12; $b++)
                            @php $r = $rows->get($b); $p = $r && (float) $r->target > 0 ? round((float) $r->capaian / (float) $r->target * 100) : null; @endphp
                            <tr class="border-t border-slate-100">
                                <td class="font-semibold text-[13px]">{{ $bulanLabels[$b] }}</td>
                                <td><input name="nilai[{{ $b }}][target]" value="{{ $r?->target ? rtrim(rtrim(number_format($r->target, 2, '.', ''), '0'), '.') : '' }}" class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm focus:border-teal-500 outline-none" inputmode="decimal"></td>
                                <td><input name="nilai[{{ $b }}][capaian]" value="{{ $r?->capaian ? rtrim(rtrim(number_format($r->capaian, 2, '.', ''), '0'), '.') : '' }}" class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm focus:border-teal-500 outline-none" inputmode="decimal"></td>
                                <td class="text-right text-xs font-bold {{ $p === null ? 'text-slate-300' : ($p >= 100 ? 'text-emerald-600' : ($p >= 80 ? 'text-amber-600' : 'text-rose-500')) }}">{{ $p !== null ? $p . '%' : '—' }}</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-100 flex items-center gap-3 bg-slate-50/60">
                <p class="text-[11px] text-slate-400 flex-1">Kosongkan kedua sel bulan untuk menghapus barisnya.</p>
                <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-500 active:scale-95 transition">Simpan TA {{ $tahun }}</button>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <h3 class="font-bold mb-1">Impor berkas per tahun</h3>
        <p class="text-xs text-slate-500 mb-3">CSV/XLSX berisi baris <code>bulan,target,capaian</code> (bulan: 1–12 atau “Jan..Des”), atau dua kolom <code>target,capaian</code> urut Januari→Desember. Nilai lama bulan yang sama akan ditimpa.</p>
        <form method="POST" action="{{ route('admin.statistik.import') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf
            <div class="flex gap-2">
                <label class="text-xs font-bold text-slate-500 flex-1">Tahun tujuan
                    <select name="tahun" class="mt-1 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm bg-white">
                        @foreach ($tahunOptions as $y)<option value="{{ $y }}" @selected($y === $tahun)>{{ $y }}</option>@endforeach
                    </select>
                </label>
            </div>
            <input type="file" name="berkas" accept=".csv,.xlsx,.xls,.ods,.txt" required class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white">
            <button class="w-full rounded-lg bg-slate-900 py-2 text-sm font-bold text-white hover:bg-slate-700 active:scale-95 transition">Impor</button>
        </form>
    </div>
</div>
@endsection
