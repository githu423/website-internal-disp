@extends('layouts.dashboard')
@section('title', $b->nama)
@section('content')

<div class="mb-4 flex flex-wrap items-center gap-2">
    <a href="{{ route('bidang.index') }}" class="text-sm font-semibold text-slate-500 hover:text-teal-600">← Semua bidang</a>
    <span class="text-slate-300">|</span>
    @foreach ($semuaBidang as $sb)
        <a href="{{ route('bidang.show', $sb) }}" @class(['rounded-full px-3 py-1 text-xs font-bold border transition inline-flex items-center gap-1', $sb->id === $b->id ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-400', $sb->id === auth()->user()->id_bidang && $sb->id !== $b->id ? 'border-emerald-400 text-emerald-700' : ''])>@if ($sb->id === auth()->user()->id_bidang)✓@endif {{ $sb->kode }} · {{ $sb->nama }}</a>
    @endforeach
</div>

<div class="card p-6 mb-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-0">
    <div class="flex flex-wrap items-center gap-4">
        <span class="h-14 w-14 rounded-2xl bg-white/10 grid place-items-center font-black text-xl">{{ $b->kode }}</span>
        <div class="flex-1 min-w-[220px]">
            <h2 class="font-extrabold text-lg leading-tight">{{ $b->nama }}
                @if (auth()->user()->id_bidang === $b->id)
                    <span class="align-middle ml-2 rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/40 px-2.5 py-0.5 text-[11px] font-extrabold">✓ ANDA DI BIDANG INI</span>
                @elseif (auth()->user()->isAdmin())
                    <span class="align-middle ml-2 rounded-full bg-amber-500/20 text-amber-200 border border-amber-400/40 px-2.5 py-0.5 text-[11px] font-extrabold">MODE ADMIN — SELURUH DINAS</span>
                @else
                    <span class="align-middle ml-2 rounded-full bg-white/10 text-slate-300 border border-white/20 px-2.5 py-0.5 text-[11px] font-bold">Sedang melihat bidang lain — arsip privat tidak ditampilkan</span>
                @endif
            </h2>
            <p class="text-sm text-slate-300 mt-1">{{ $b->deskripsi }}</p>
            <p class="text-xs text-slate-400 mt-1.5">Ketua: {{ $b->ketua ?: '—' }}</p>
        </div>
        @if ($bolehKelola)
            <details class="relative">
                <summary class="cursor-pointer list-none rounded-lg bg-white/10 hover:bg-white/20 px-3 py-1.5 text-xs font-bold">✏️ Kelola halaman ▾</summary>
                <form method="POST" action="{{ route('bidang.update', $b) }}" class="absolute right-0 top-8 z-20 w-80 rounded-xl bg-white p-3.5 text-slate-700 shadow-2xl">
                    @csrf @method('PATCH')
                    <label class="text-xs font-bold text-slate-500">Nama bidang<input name="nama" value="{{ $b->nama }}" required class="mt-1 mb-2 block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500">Ketua<input name="ketua" value="{{ $b->ketua }}" class="mt-1 mb-2 block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500">Deskripsi<textarea name="deskripsi" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">{{ $b->deskripsi }}</textarea></label>
                    <button class="mt-2 w-full rounded-lg bg-slate-900 py-1.5 text-sm font-bold text-white hover:bg-slate-700">Simpan</button>
                </form>
            </details>
        @endif
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

            @if ($bolehTambahAgenda)
                <form method="POST" action="{{ route('agenda.store') }}" class="mt-3 space-y-2 rounded-xl border border-dashed border-teal-300 bg-teal-50/40 p-3">
                    @csrf
                    <input type="hidden" name="id_bidang" value="{{ $b->id }}">
                    <p class="text-[11px] font-bold text-teal-700">Tambah agenda bidang ini</p>
                    <input name="judul" placeholder="Judul kegiatan *" required maxlength="180" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="tanggal" required class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                        <input type="time" name="jam_mulai" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                    </div>
                    <button class="w-full rounded-lg bg-teal-600 py-1.5 text-xs font-bold text-white hover:bg-teal-500 active:scale-95 transition">Simpan Agenda</button>
                </form>
            @endif
        </div>

        <div class="card p-5">
            <h3 class="font-bold mb-1">Dokumen Bidang</h3>
            <p class="text-[11px] text-slate-400 mb-3">{{ $bolehKelola ? 'Termasuk arsip privat 🔒 yang tidak terlihat bidang lain.' : 'Hanya dokumen publik milik bidang ini yang tampil untuk Anda.' }}</p>
            @forelse ($dokumens as $d)
                <div class="flex items-center gap-1.5 py-1.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                    <span class="text-[13px] font-medium text-slate-600 truncate flex-1">{{ $d->visibility === 'bidang' ? '🔒' : '📄' }} {{ $d->judul }}</span>
                    <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-teal-600 hover:underline whitespace-nowrap">Lihat</a>
                    <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600 whitespace-nowrap">Unduh</a>
                    @if ($bolehKelola)
                        <form method="POST" action="{{ route('dokumen.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen beserta filenya?')">@csrf @method('DELETE')
                            <button class="text-[11px] font-bold text-rose-500 hover:underline whitespace-nowrap">Hapus</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada dokumen.</p>
            @endforelse

            @if ($bolehKelola)
                <form method="POST" action="{{ route('bidang.dokumen', $b) }}" enctype="multipart/form-data" class="mt-3 space-y-2 rounded-xl border border-dashed border-teal-300 bg-teal-50/40 p-3">
                    @csrf
                    <p class="text-[11px] font-bold text-teal-700">Simpan arsip privat bidang (khusus anggota)</p>
                    <input name="judul" placeholder="Judul dokumen *" required maxlength="180" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                    <input type="file" name="file" required class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-teal-600 file:px-2.5 file:py-1.5 file:text-xs file:font-bold file:text-white" onchange="const n=this.nextElementSibling;if(this.files[0]){n.textContent='📎 '+this.files[0].name;n.classList.remove('hidden')}else{n.classList.add('hidden')}">
                    <p class="hidden mt-1 text-[11px] font-semibold text-teal-700 truncate"></p>
                    <button class="w-full rounded-lg bg-teal-600 py-1.5 text-xs font-bold text-white hover:bg-teal-500 active:scale-95 transition">Unggah ke bidang ini</button>
                </form>
            @endif
            <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="inline-block mt-3 text-xs font-bold text-teal-600 hover:underline">Semua dokumen bidang ini →</a>
        </div>
    </div>
</div>
@endsection
