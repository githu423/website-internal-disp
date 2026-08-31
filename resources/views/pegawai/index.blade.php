@extends('layouts.dashboard')
@section('title', 'Data Pegawai')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-extrabold">Aparatur Dinas Pariwisata</h2>
    <form method="GET" class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Cari nama / NIP / jabatan…" class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
        <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 active:scale-95 transition">Cari</button>
    </form>
</div>
<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[880px]">
            <thead><tr><th>Nama / NIP</th><th>Jabatan</th><th>Pangkat / Gol.</th><th>Pendidikan</th><th>Masa Kerja</th><th>Unit Kerja</th></tr></thead>
            <tbody>
            @forelse ($pegawai as $p)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="h-9 w-9 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 text-white grid place-items-center text-[11px] font-bold shrink-0">{{ strtoupper(mb_substr($p->nama, 0, 2)) }}</span>
                            <div><p class="font-semibold text-[13px] leading-tight">{{ $p->nama }}</p><p class="text-[11px] text-slate-400 font-mono">{{ $p->nip }} · {{ $p->jenis_kelamin === 'P' ? '♀' : '♂' }} {{ $p->jenis_asn }}</p></div>
                        </div>
                    </td>
                    <td class="text-[13px]">{{ $p->jabatan ?? '—' }}</td>
                    <td class="text-xs">{{ $p->pangkat_golongan ?? '—' }}</td>
                    <td class="text-xs">{{ $p->pendidikan ?? '—' }}</td>
                    <td class="text-xs font-semibold whitespace-nowrap">{{ $p->masa_kerja['label'] }}<span class="block font-normal text-[10px] text-slate-400">sejak {{ $p->tmt_cpns ? \Carbon\Carbon::parse($p->tmt_cpns)->translatedFormat('M Y') : '—' }}</span></td>
                    <td class="text-[11px] text-slate-500">{{ $p->bidang?->nama ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty icon="pegawai" title="Tidak ada pegawai ditemukan" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($pegawai->hasPages())<div class="p-3">{{ $pegawai->links() }}</div>@endif
</div>
@endsection
