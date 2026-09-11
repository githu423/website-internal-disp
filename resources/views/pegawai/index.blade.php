@extends('layouts.dashboard')
@section('title', 'Data Pegawai')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-extrabold">Aparatur Dinas Pariwisata</h2>
    <form method="GET" class="flex gap-2">
        @if ($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
        <input name="q" value="{{ request('q') }}" placeholder="{{ $bolehKelengkapan ? 'Cari nama / NIP / jabatan… atau ketik: belum lengkap' : 'Cari nama / NIP / jabatan…' }}" class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
        <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 active:scale-95 transition">Cari</button>
    </form>
</div>

{{-- Konfirmasi Dinas 10 Sep: kartu non-aktif/pensiun di dashboard masuk ke sini terfilter otomatis --}}
<div class="flex flex-wrap items-center gap-1.5 mb-3">
    <span class="text-[11px] font-bold uppercase tracking-wide text-slate-400 mr-1">Status:</span>
    @foreach (['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Non-Aktif', 'pensiun' => 'Pensiun'] as $sv => $slab)
        <a href="{{ route('pegawai.index', array_merge(request()->query(), ['status' => $sv ?: null, 'page' => null])) }}" @class(['rounded-lg px-3 py-1.5 text-[12.5px] font-bold border transition active:scale-95', $statusFilter === $sv ? ($sv === 'nonaktif' ? 'bg-amber-500 text-white border-amber-500' : ($sv === 'pensiun' ? 'bg-slate-700 text-white border-slate-700' : ($sv === 'aktif' ? 'bg-teal-600 text-white border-teal-600' : 'bg-slate-900 text-white border-slate-900'))) : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $slab }}</a>
    @endforeach
</div>
<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[880px]">
            <thead><tr><th>Nama / NIP</th><th>Jabatan</th><th>Pangkat / Gol.</th><th>Pendidikan</th><th>Umur</th><th>Masa Kerja</th><th>Unit Kerja</th></tr></thead>
            <tbody>
            @forelse ($pegawai as $p)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="h-9 w-9 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 text-white grid place-items-center text-[11px] font-bold shrink-0">{{ strtoupper(mb_substr($p->nama, 0, 2)) }}</span>
                            <div><p class="font-semibold text-[13px] leading-tight">{{ $p->nama }} @if ($p->status === 'nonaktif')<span class="text-[9px] font-extrabold bg-amber-100 text-amber-700 rounded px-1 py-0.5 align-middle">NON-AKTIF</span>@elseif ($p->status === 'pensiun')<span class="text-[9px] font-extrabold bg-slate-200 text-slate-600 rounded px-1 py-0.5 align-middle">PENSIUN</span>@endif</p><p class="text-[11px] text-slate-400 font-mono">{{ $p->nip }} · {{ $p->jenis_kelamin === 'P' ? '♀' : '♂' }} {{ $p->jenis_asn }}</p>@if ($modeBelum)<p class="mt-0.5 text-[10.5px] font-bold text-rose-600">belum lengkap: {{ implode(', ', $p->kolomKurang()) }}</p>@endif</div>
                        </div>
                    </td>
                    <td class="text-[13px]">{{ $p->jabatan ?? '—' }}</td>
                    <td class="text-xs">{{ $p->pangkat_golongan ?? '—' }}</td>
                    <td class="text-xs">{{ $p->pendidikan ?? '—' }}</td>
                    <td class="text-xs font-semibold whitespace-nowrap" title="dihitung dari tanggal lahir">{{ $p->umur !== null ? $p->umur . ' tahun' : '—' }}<span class="block font-normal text-[10px] text-slate-400">{{ $p->tanggal_lahir?->translatedFormat('d M Y') }}</span></td>
                    <td class="text-xs font-semibold whitespace-nowrap">{{ $p->masa_kerja['label'] }}<span class="block font-normal text-[10px] text-slate-400">sejak {{ $p->tmt_cpns ? \Carbon\Carbon::parse($p->tmt_cpns)->translatedFormat('M Y') : '—' }}</span></td>
                    <td class="text-[11px] text-slate-500">{{ $p->bidang?->nama ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty icon="pegawai" title="{{ $modeBelum ? 'Semua pegawai sudah lengkap datanya 🎉' : 'Tidak ada pegawai ditemukan' }}" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($pegawai->hasPages())<div class="p-3">{{ $pegawai->links() }}</div>@endif
</div>

<!-- Rekap jumlah — selalu dihitung dari SELURUH data, tidak berubah oleh kotak cari -->
<div class="card mt-3.5 px-4 py-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-[12.5px] text-slate-500" title="Rekap keseluruhan data pegawai (tidak terpengaruh pencarian)">
    <span class="font-extrabold text-[13px] text-slate-700">Rekap:</span>
    <span><b class="text-[15px] text-slate-900">{{ $statistik['semua'] }}</b> semuanya</span>
    <span class="text-sky-700">♂ <b>{{ $statistik['l'] }}</b> laki-laki</span>
    <span class="text-pink-600">♀ <b>{{ $statistik['p'] }}</b> perempuan</span>
    <span class="text-teal-700"><b>{{ $statistik['pns'] }}</b> PNS</span>
    <span class="text-indigo-700"><b>{{ $statistik['nonpns'] }}</b> non-PNS (PPPK/belum terisi)</span>
    <span class="text-emerald-700"><b>{{ $statistik['aktif'] }}</b> aktif</span>
    <span class="text-amber-700"><b>{{ $statistik['nonaktif'] }}</b> non-aktif</span>
    <span class="text-slate-500"><b>{{ $statistik['pensiun'] }}</b> pensiun</span>
    @if ($bolehKelengkapan)
        <span class="text-emerald-700 font-bold">✓ <b>{{ $statistik['lengkap'] }}</b> sudah lengkap</span>
        @if ($statistik['belum'] > 0 || request('belum'))
            <a href="{{ request()->fullUrlWithQuery(['belum' => request('belum') ? null : 1]) }}"
               class="ml-auto inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[11.5px] font-bold transition {{ request('belum') ? 'bg-rose-600 text-white border-rose-600' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}">⚠ {{ $statistik['belum'] }} pegawai belum lengkap datanya — {{ request('belum') ? 'tampilkan semua' : 'saring' }}</a>
        @endif
    @endif
</div>

@endsection
