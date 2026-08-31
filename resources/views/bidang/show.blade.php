@extends('layouts.dashboard')
@section('title', $b->nama)
@section('content')
<div class="mb-4"><a href="{{ route('bidang.index') }}" class="text-sm font-semibold text-slate-500 hover:text-teal-600">← Kembali ke daftar bidang</a></div>
<div class="card p-6 mb-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-0">
    <div class="flex flex-wrap items-center gap-4">
        <span class="h-14 w-14 rounded-2xl bg-white/10 grid place-items-center font-black text-xl">{{ $b->kode }}</span>
        <div class="flex-1 min-w-[220px]"><h2 class="font-extrabold text-lg leading-tight">{{ $b->nama }}</h2><p class="text-sm text-slate-300 mt-1">{{ $b->deskripsi }}</p></div>
    </div>
</div>
<div class="grid lg:grid-cols-3 gap-4">
    <div class="card overflow-hidden lg:col-span-2">
        <h3 class="font-bold px-5 pt-4 pb-3 border-b border-slate-100">Pegawai ({{ $pegawais->count() }})</h3>
        <table class="data w-full">
            <tbody>
            @forelse ($pegawais as $p)
                <tr><td class="font-semibold text-[13px]">{{ $p->nama }}<span class="block font-normal text-[11px] text-slate-400">{{ $p->jabatan ?: '—' }}</span></td>
                    <td class="text-xs font-mono text-slate-500">{{ $p->nip }}</td>
                    <td class="text-xs whitespace-nowrap text-right">{{ $p->masa_kerja['label'] }}</td></tr>
            @empty
                <tr><td><x-empty icon="pegawai" title="Belum ada pegawai terdata di bidang ini" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="font-bold mb-3">Agenda Terakhir</h3>
            @forelse ($agenda as $a)
                <div class="flex gap-2.5 py-1.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}"><span class="text-[11px] font-bold text-teal-600 whitespace-nowrap w-13">{{ $a->tanggal->translatedFormat('d M') }}</span><p class="text-[13px] leading-snug">{{ $a->judul }}</p></div>
            @empty
                <p class="text-sm text-slate-400">Belum ada agenda.</p>
            @endforelse
        </div>
        <div class="card p-5">
            <h3 class="font-bold mb-3">Dokumen Bidang</h3>
            @forelse ($dokumens as $d)
                <a href="{{ route('dokumen.unduh', $d) }}" class="block py-1.5 text-[13px] font-medium text-slate-600 hover:text-teal-600 truncate {{ $loop->last ? '' : 'border-b border-slate-100' }}">📄 {{ $d->judul }}</a>
            @empty
                <p class="text-sm text-slate-400">Belum ada dokumen.</p>
            @endforelse
            <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="inline-block mt-3 text-xs font-bold text-teal-600 hover:underline">Semua dokumen bidang ini →</a>
        </div>
    </div>
</div>
@endsection
