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

<style>
/* mobile-first halaman bidang: kartu Agenda & Dokumen naik ke atas, tabel Pegawai turun paling bawah
   (dipasang inline agar tidak bergantung pada build Tailwind; ≥1024px susunan desktop seperti semula) */
@media (max-width: 1023px) {
    .bidang-grid { display: flex; flex-direction: column; }
    .bidang-grid > .bidang-aksi { order: 1; }
    .bidang-grid > .bidang-pegawai { order: 2; }
}
</style>
<div class="grid lg:grid-cols-3 gap-4 bidang-grid">
    <div class="card overflow-hidden lg:col-span-2 bidang-pegawai">
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
    <div class="space-y-4 bidang-aksi">
        <div class="card p-5">
            <h3 class="font-bold mb-3">Agenda Bidang</h3>
            @forelse ($agenda as $a)
                <div class="flex gap-2.5 py-1.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}"><span class="text-[11px] font-bold text-teal-600 whitespace-nowrap w-13">{{ $a->tanggal->translatedFormat('d M') }}</span><p class="text-[13px] leading-snug">{{ $a->judul }}@if ($a->jam_mulai)<span class="block text-[11px] text-slate-400">{{ $a->jam_mulai }}{{ $a->lokasi ? ' · '.$a->lokasi : '' }}</span>@endif</p></div>
            @empty
                <p class="text-sm text-slate-400">Belum ada agenda.</p>
            @endforelse
            @if ($bolehKelola)
                <details class="mt-2.5">
                    <summary class="cursor-pointer list-none rounded-lg border border-dashed border-teal-400 bg-teal-50/50 px-2.5 py-1.5 text-xs font-bold text-teal-700 hover:bg-teal-50">➕ Tambah agenda bidang</summary>
                    <form method="POST" action="{{ route('bidang.agenda', $b) }}" class="mt-2 space-y-1.5">
                        @csrf
                        <input name="judul" required maxlength="180" placeholder="Judul agenda *" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                        <div class="flex gap-1.5">
                            <input type="date" name="tanggal" required class="flex-1 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                            <input type="time" name="jam_mulai" value="09:00" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                        </div>
                        <input name="lokasi" maxlength="120" placeholder="Lokasi (opsional)" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                        <button class="w-full rounded-lg bg-teal-600 py-1.5 text-xs font-bold text-white hover:bg-teal-500 active:scale-95 transition">Simpan Agenda</button>
                    </form>
                </details>
            @endif
            <a href="{{ route('agenda.index', ['bulan' => now()->format('Y-m')]) }}" class="inline-block mt-2.5 text-xs font-bold text-teal-600 hover:underline">Lihat agenda di kalender dinas →</a>
        </div>

        <div class="card p-5">
            <h3 class="font-bold mb-1">Dokumen Bidang</h3>
            <p class="text-[11px] text-slate-400 mb-3">{{ $bolehKelola ? 'Termasuk arsip privat 🔒 yang tidak terlihat bidang lain.' : 'Hanya dokumen publik milik bidang ini yang tampil untuk Anda.' }}</p>
            @if ($dokumens->isNotEmpty())
                <div class="relative mb-1.5">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[13px] text-slate-400">🔍</span>
                    <input data-doc-q type="search" placeholder="Cari judul / file dokumen…" autocomplete="off"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50/70 py-1.5 pl-8 pr-16 text-[13px] outline-none transition focus:border-teal-400 focus:bg-white">
                    <button data-doc-clear type="button" hidden class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-1.5 py-0.5 text-[10px] font-bold text-slate-400 hover:bg-slate-100">✕</button>
                </div>
                <p data-doc-info class="mb-1 text-[10.5px] font-semibold text-slate-400"></p>
            @endif
            <div class="divide-y divide-slate-100">
            @forelse ($dokumens as $d)
                <div data-doc-item data-doc-text="{{ \Illuminate\Support\Str::lower($d->judul.' '.$d->file_name.' '.($d->kategori ?? '')) }}" class="flex items-center gap-2 py-2">
                    <span class="shrink-0 text-[15px] leading-none">{{ $d->visibility === 'bidang' ? '🔒' : '📄' }}</span>
                    <span class="min-w-0 flex-1">
                        <a href="{{ route('dokumen.show', $d) }}" class="block truncate text-[13px] font-semibold text-slate-700 hover:text-teal-600">{{ $d->judul }}</a>
                        <span class="block truncate text-[10.5px] text-slate-400">{{ $d->file_name }} · {{ number_format((int) $d->file_size / 1024) }} KB · {{ $d->created_at?->translatedFormat('d M Y') }}@if($d->kategori) · {{ $d->kategori }}@endif</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-teal-600 hover:underline whitespace-nowrap">Lihat</a>
                        <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600 whitespace-nowrap">Unduh</a>
                        @if ($bolehKelola)
                            <form method="POST" action="{{ route('dokumen.destroy', $d) }}" onsubmit="return confirm('Hapus dokumen beserta filenya?')">@csrf @method('DELETE')
                                <button class="text-[11px] font-bold text-rose-500 hover:underline whitespace-nowrap">Hapus</button>
                            </form>
                        @endif
                    </span>
                </div>
            @empty
                <p class="py-2 text-sm text-slate-400">Belum ada dokumen.</p>
            @endforelse
            </div>
            @if ($dokumens->isNotEmpty())
                <p data-doc-none hidden class="py-2 text-[12px] text-slate-400">Tidak ada dokumen yang cocok.</p>
            @endif

            @if ($bolehKelola)
                <form method="POST" action="{{ route('bidang.dokumen', $b) }}" enctype="multipart/form-data" class="mt-3 space-y-2 rounded-xl border border-dashed border-teal-300 bg-teal-50/40 p-3">
                    @csrf
                    <p class="text-[11px] font-bold text-teal-700">Simpan arsip privat bidang (khusus anggota)</p>
                    <input name="judul" placeholder="Judul dokumen *" required maxlength="180" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-500 outline-none">
                    <input type="file" name="file" required class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-md file:border-0 file:bg-teal-600 file:px-2.5 file:py-1.5 file:text-xs file:font-bold file:text-white">
                    <button class="w-full rounded-lg bg-teal-600 py-1.5 text-xs font-bold text-white hover:bg-teal-500 active:scale-95 transition">Unggah ke bidang ini</button>
                </form>
            @endif
            <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="inline-block mt-3 text-xs font-bold text-teal-600 hover:underline">Semua dokumen bidang ini →</a>
        </div>
    </div>
</div>

<script>
(function(){
    const q=document.querySelector('[data-doc-q]'); if(!q) return;
    const rows=[...document.querySelectorAll('[data-doc-item]')];
    const info=document.querySelector('[data-doc-info]');
    const none=document.querySelector('[data-doc-none]');
    const clr=document.querySelector('[data-doc-clear]');
    const upd=()=>{
        const t=q.value.trim().toLowerCase();
        let n=0; rows.forEach(r=>{const hit=!t||r.dataset.docText.includes(t); r.hidden=!hit; if(hit)n++;});
        if(info) info.textContent = t ? ('Menampilkan '+n+' dari '+rows.length+' dokumen untuk “'+q.value.trim()+'”') : ('Total '+rows.length+' dokumen di bidang ini');
        if(none) none.hidden = !(t && n===0);
        if(clr) clr.hidden = !t;
    };
    q.addEventListener('input',upd);
    if(clr) clr.addEventListener('click',()=>{q.value='';upd();q.focus();});
    upd();
})();
</script>
@endsection
