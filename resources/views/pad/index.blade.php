{{-- ===== HALAMAN: PAD — daftar target & realisasi per kategori. ===== --}}
@extends('layouts.dashboard')

@section('title', 'PAD — Retribusi Daerah')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Pendapatan Asli Daerah (Retribusi) Kabupaten Ciamis')

@section('content')
@php 
    $u = auth()->user();
    $useNum = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $totalTarget = $sum?->target ?? 0; 
    $totalRealisasi = $sum?->realisasi ?? 0;
    $pct = $totalTarget > 0 ? round($totalRealisasi / $totalTarget * 100, 2) : 0;
@endphp

<div class="space-y-6">

    {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Data &amp; Kinerja</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">PAD (Retribusi)</span>
    </nav>

    {{-- ===== BAGIAN: Header Halaman & Quick Actions ===== --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pendapatan Asli Daerah (Retribusi)</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    TA {{ $tahun }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Dikelola Dinas Pariwisata Kab. Ciamis · Realisasi penerimaan retribusi destinasi &amp; aset daerah.</p>
        </div>

        {{-- ===== BAGIAN: Quick Actions Bar ===== --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- ===== BAGIAN: Pilihan Tahun (Segmented Pill) ===== --}}
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach ($tahunList as $t)
                    <a href="{{ route('pad.index', ['tahun' => $t]) }}" 
                       class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $t == $tahun ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $t }}
                    </a>
                @endforeach
            </div>

            {{-- ===== BAGIAN: Tombol Utama (Hanya Admin: Kelola PAD) ===== --}}
            @if($u->isAdmin())
            <a href="{{ route('admin.pad.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Kelola PAD
            </a>
            @endif
        </div>
    </div>

    {{-- ===== BAGIAN: Stat Cards Ringkasan Target & Realisasi ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Target Anggaran {{ $tahun }}</p>
                <p class="text-xl sm:text-[21px] font-extrabold text-slate-900 tracking-tight mt-1 truncate">Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
                <p class="text-xs text-slate-500 mt-1 truncate">Target ketetapan dinas</p>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0 text-xl shadow-sm">
                🎯
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kumulatif Realisasi</p>
                <p class="text-xl sm:text-[21px] font-extrabold text-emerald-600 tracking-tight mt-1 truncate">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</p>
                <p class="text-xs text-emerald-600/80 mt-1 font-medium truncate">Penerimaan kas daerah</p>
            </div>
            <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0 text-xl shadow-sm">
                💰
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Persentase Capaian</p>
                <p class="text-xl sm:text-[21px] font-extrabold {{ $pct >= 100 ? 'text-emerald-600' : 'text-purple-700' }} tracking-tight mt-1 truncate">{{ $pct }}%</p>
                <div class="mt-2 flex items-center gap-2">
                    <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-purple-600' }}" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                    </div>
                    <span class="text-[11px] font-semibold {{ $pct >= 100 ? 'text-emerald-600' : 'text-slate-500' }} shrink-0">
                        {{ $pct >= 100 ? 'Tercapai 🎉' : 'Realisasi' }}
                    </span>
                </div>
            </div>
            <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0 text-xl shadow-sm">
                📊
            </div>
        </div>
    </div>

    {{-- ===== BAGIAN: Kontainer Tabel Data Retribusi ===== --}}
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[880px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="py-3 px-4">Retribusi / Objek Penerimaan</th>
                        <th class="py-3 px-4 text-right">Target</th>
                        <th class="py-3 px-4 text-right">Realisasi {{ now()->subMonth()->translatedFormat('M') }}</th>
                        <th class="py-3 px-4 text-right">Realisasi {{ now()->translatedFormat('M') }}</th>
                        <th class="py-3 px-4 text-right">Kumulatif</th>
                        <th class="py-3 px-4 text-right">Sisa Target</th>
                        <th class="py-3 px-4 min-w-[170px]">Progres (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($rows->groupBy('kategori') as $kat => $grup)
                    {{-- ===== BAGIAN: Header Kelompok Kategori ===== --}}
                    <tr class="bg-slate-100/60 border-t border-b border-slate-200/80">
                        <td colspan="7" class="py-2.5 px-4 font-extrabold text-xs uppercase tracking-wider text-purple-900 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-purple-600"></span>
                            {{ $kat ?: 'Tanpa Kategori' }}
                        </td>
                    </tr>
                    @foreach ($grup as $r)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-[13px] text-slate-900 leading-snug">{{ $r->objek }}</p>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">Rek. {{ $r->no_rekening ?: '—' }}</p>
                            </td>
                            <td class="py-3 px-4 text-right text-xs tabular-nums text-slate-700">
                                Rp {{ number_format($r->target_tahunan, 0, ',', '.') }}
                                @if ($r->target_perubahan > 0)
                                    <span class="block text-[10px] text-sky-600 font-bold mt-0.5">Perubahan: Rp {{ number_format($r->target_perubahan, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right text-xs tabular-nums text-slate-500">
                                Rp {{ number_format($r->realisasi_bulan_lalu, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right text-xs tabular-nums text-slate-500">
                                Rp {{ number_format($r->realisasi_bulan_ini, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right text-xs tabular-nums font-bold text-slate-900">
                                Rp {{ number_format($r->kumulatif, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right text-xs tabular-nums font-bold {{ $r->sisa_target < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($r->sisa_target, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                <x-prog :persen="$r->persentase" />
                            </td>
                        </tr>
                    @endforeach
                    @php $g = $perKategori[$kat] ?? null; @endphp
                    @if ($g)
                    <tr class="border-t border-slate-200 font-bold bg-slate-50/80 text-xs">
                        <td class="py-2.5 px-4 text-slate-600 uppercase tracking-wide">Jumlah Subtotal {{ $kat }}</td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-800">Rp {{ number_format($g['target'], 0, ',', '.') }}</td>
                        <td></td>
                        <td></td>
                        <td class="py-2.5 px-4 text-right tabular-nums text-slate-900 font-black">Rp {{ number_format($g['realisasi'], 0, ',', '.') }}</td>
                        <td class="py-2.5 px-4 text-right tabular-nums font-bold {{ $g['realisasi'] - $g['target'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            Rp {{ number_format($g['realisasi'] - $g['target'], 0, ',', '.') }}
                        </td>
                        <td class="py-2.5 px-4">
                            <x-prog :persen="$g['target'] > 0 ? round($g['realisasi'] / $g['target'] * 100, 2) : 0" />
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center">
                            <x-empty icon="pad" title="Belum ada data PAD TA {{ $tahun }}" desc="Admin dapat mengunggah file PAD (Excel/CSV) atau menambah manual lewat menu Kelola PAD." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 text-[11px] text-slate-400 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <span>Sisa Target = Kumulatif Realisasi − Target (nilai minus menandakan target belum tercapai).</span>
            <span class="font-medium text-slate-500">Sumber: Pembukuan PAD Resmi Dinas Pariwisata</span>
        </div>
    </div>

</div>
@endsection

