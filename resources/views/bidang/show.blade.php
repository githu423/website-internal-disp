@extends('layouts.dashboard')

@section('title', $b->nama)
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Profil Bidang & Unit Kerja Dinas')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="text-slate-400 hover:text-purple-600 transition">Kantor</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="text-slate-400 hover:text-purple-600 transition">Bidang Dinas</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold truncate">{{ $b->nama }}</span>
    </nav>

    <!-- 2. Navigasi Cepat Switcher Antar-Bidang -->
    <div class="flex flex-wrap items-center gap-1.5 pb-2">
        <a href="{{ route('bidang.index') }}" class="text-xs font-bold text-slate-500 hover:text-purple-600 transition mr-2">
            ← Semua Bidang
        </a>
        <span class="text-slate-300 mr-1">|</span>
        @foreach ($semuaBidang as $sb)
            <a href="{{ route('bidang.show', $sb) }}" 
               class="rounded-full px-3 py-1 text-xs font-bold border transition inline-flex items-center gap-1 {{ $sb->id === $b->id ? 'bg-purple-600 text-white border-transparent shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-700' }} {{ $sb->id === auth()->user()->id_bidang && $sb->id !== $b->id ? 'ring-1 ring-emerald-400 text-emerald-700' : '' }}">
                @if ($sb->id === auth()->user()->id_bidang)✓@endif {{ $sb->kode }} · {{ $sb->nama }}
            </a>
        @endforeach
    </div>

    <!-- 3. Hero Card Profil Bidang -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-md border-0">
        <div class="flex flex-wrap items-center gap-4">
            <span class="h-14 w-14 rounded-2xl bg-white/10 grid place-items-center font-black text-xl shrink-0">
                {{ $b->kode }}
            </span>
            <div class="flex-1 min-w-[220px]">
                <h2 class="font-extrabold text-lg sm:text-xl leading-tight flex items-center flex-wrap gap-2">
                    {{ $b->nama }}
                    @if (auth()->user()->id_bidang === $b->id)
                        <span class="rounded-full bg-emerald-500/20 text-emerald-200 border border-emerald-400/40 px-2.5 py-0.5 text-[11px] font-extrabold">
                            ✓ ANDA DI BIDANG INI
                        </span>
                    @elseif (auth()->user()->isAdmin())
                        <span class="rounded-full bg-amber-500/20 text-amber-200 border border-amber-400/40 px-2.5 py-0.5 text-[11px] font-extrabold">
                            MODE ADMIN — SELURUH DINAS
                        </span>
                    @else
                        <span class="rounded-full bg-white/10 text-slate-300 border border-white/20 px-2.5 py-0.5 text-[11px] font-medium">
                            Bidang lain — arsip privat dirahasiakan
                        </span>
                    @endif
                </h2>
                <p class="text-sm text-slate-300 mt-1.5 leading-relaxed">{{ $b->deskripsi ?: 'Belum ada deskripsi tugas fungsi.' }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Kepala / Koordinator: <span class="text-slate-200">{{ $b->ketua ?: '—' }}</span></p>
            </div>
            @if ($bolehKelola)
                <details class="relative">
                    <summary class="cursor-pointer list-none rounded-xl bg-white/10 hover:bg-white/20 px-3.5 py-2 text-xs font-bold transition">
                        ✏️ Edit Profil Bidang ▾
                    </summary>
                    <form method="POST" action="{{ route('bidang.update', $b) }}" class="absolute right-0 top-9 z-20 w-80 rounded-2xl bg-white p-4 text-slate-700 shadow-2xl border border-slate-200">
                        @csrf @method('PATCH')
                        <div class="space-y-2.5 text-xs">
                            <label class="block font-bold text-slate-600">Nama Bidang
                                <input name="nama" value="{{ $b->nama }}" required class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                            </label>
                            <label class="block font-bold text-slate-600">Ketua
                                <input name="ketua" value="{{ $b->ketua }}" class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                            </label>
                            <label class="block font-bold text-slate-600">Deskripsi
                                <textarea name="deskripsi" rows="3" class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">{{ $b->deskripsi }}</textarea>
                            </label>
                            <button class="mt-2 w-full rounded-xl bg-purple-600 py-2 text-sm font-bold text-white hover:bg-purple-700 active:scale-95 transition shadow-sm">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </details>
            @endif
        </div>
    </div>

    <style>
    @media (max-width: 1023px) {
        .bidang-grid { display: flex; flex-direction: column; }
        .bidang-grid > .bidang-aksi { order: 1; }
        .bidang-grid > .bidang-pegawai { order: 2; }
    }
    </style>

    <!-- 4. Konten Utama: Pegawai (Kiri) & Agenda / Dokumen (Kanan) -->
    <div class="grid lg:grid-cols-3 gap-5 bidang-grid">
        <!-- Kolom Kiri: Tabel Aparatur Bidang -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden lg:col-span-2 bidang-pegawai">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>👥</span> Aparatur Bidang ({{ $pegawais->count() }})
                </h3>
            </div>
            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full">
                    <tbody>
                    @forelse ($pegawais as $p)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-semibold text-[13px] text-slate-900">
                                {{ $p->nama }}
                                <span class="block font-normal text-[11px] text-slate-400">
                                    {{ $p->jabatan ?: '—' }} @if ($p->umur !== null) · {{ $p->umur }} th @endif
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs font-mono text-slate-500">{{ $p->nip }}</td>
                            <td class="py-3 px-4 text-xs whitespace-nowrap text-right text-slate-600">
                                {{ $p->masa_kerja['label'] }}
                                <span class="block text-[10px] text-slate-400">
                                    {{ $p->umur !== null ? 'Lahir ' . $p->tanggal_lahir->translatedFormat('d M Y') : 'Tgl lahir —' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8">
                                <x-empty icon="pegawai" title="Belum ada pegawai terdata di bidang ini" />
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                <span class="font-extrabold text-slate-800">Rekap:</span>
                <span><b class="text-slate-900">{{ $statistik['semua'] }}</b> Total</span>
                <span class="text-sky-700">♂ <b>{{ $statistik['l'] }}</b> L</span>
                <span class="text-pink-600">♀ <b>{{ $statistik['p'] }}</b> P</span>
                <span class="text-purple-700"><b>{{ $statistik['pns'] }}</b> PNS</span>
                <span class="text-indigo-700"><b>{{ $statistik['nonpns'] }}</b> Non-PNS</span>
                <span class="text-amber-700"><b>{{ $statistik['nonaktif'] }}</b> Non-Aktif</span>
                <span class="text-slate-500"><b>{{ $statistik['pensiun'] }}</b> Pensiun</span>
            </div>
        </div>

        <!-- Kolom Kanan: Agenda & Dokumen Bidang -->
        <div class="space-y-4 bidang-aksi">
            <!-- Card Agenda Bidang -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="font-extrabold text-sm text-slate-900 mb-3 flex items-center gap-2">
                    <span>📅</span> Agenda Bidang
                </h3>
                <div class="space-y-2">
                    @forelse ($agenda as $a)
                        <div class="flex gap-2.5 py-2 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                            <span class="text-xs font-bold text-purple-700 whitespace-nowrap w-12 shrink-0">
                                {{ $a->tanggal->translatedFormat('d M') }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-slate-800 leading-snug">{{ $a->judul }}</p>
                                @if ($a->jam_mulai)
                                    <span class="block text-[11px] text-slate-400 mt-0.5">{{ $a->jam_mulai }}{{ $a->lokasi ? ' · '.$a->lokasi : '' }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-2">Belum ada agenda terdaftar untuk bidang ini.</p>
                    @endforelse
                </div>

                @if ($bolehKelola)
                    <details class="mt-3">
                        <summary class="cursor-pointer list-none rounded-xl border border-dashed border-purple-300 bg-purple-50/40 px-3 py-2 text-xs font-bold text-purple-700 hover:bg-purple-50 transition">
                            ➕ Tambah Agenda Bidang
                        </summary>
                        <form method="POST" action="{{ route('bidang.agenda', $b) }}" class="mt-2.5 space-y-2 text-xs">
                            @csrf
                            <input name="judul" required maxlength="180" placeholder="Judul agenda *" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 focus:outline-none focus:border-purple-400">
                            <div class="flex gap-2">
                                <input type="date" name="tanggal" required class="flex-1 rounded-xl border border-slate-200 px-3 py-1.5">
                                <input type="time" name="jam_mulai" value="09:00" class="w-24 rounded-xl border border-slate-200 px-2 py-1.5">
                            </div>
                            <input name="lokasi" maxlength="120" placeholder="Lokasi (opsional)" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 focus:outline-none focus:border-purple-400">
                            <button class="w-full rounded-xl bg-purple-600 py-2 font-bold text-white hover:bg-purple-700 active:scale-95 transition shadow-sm">
                                Simpan Agenda
                            </button>
                        </form>
                    </details>
                @endif
                <a href="{{ route('agenda.index', ['bulan' => now()->format('Y-m')]) }}" class="inline-block mt-3 text-xs font-bold text-purple-600 hover:underline">
                    Lihat Agenda di Kalender Dinas →
                </a>
            </div>

            <!-- Card Dokumen Bidang -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="font-extrabold text-sm text-slate-900 mb-1 flex items-center gap-2">
                    <span>📄</span> Dokumen Bidang
                </h3>
                <p class="text-[11px] text-slate-400 mb-3">
                    {{ $bolehKelola ? 'Termasuk arsip privat 🔒 yang tidak terlihat oleh bidang lain.' : 'Hanya dokumen publik milik bidang ini yang dapat diakses.' }}
                </p>

                @if ($dokumens->isNotEmpty())
                    <div class="relative mb-2">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🔍</span>
                        <input data-doc-q type="search" placeholder="Cari judul berkas…" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-1.5 pl-8 pr-8 text-xs outline-none focus:border-purple-400 focus:bg-white transition">
                        <button data-doc-clear type="button" hidden class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-1.5 py-0.5 text-[10px] font-bold text-slate-400 hover:bg-slate-100">✕</button>
                    </div>
                    <p data-doc-info class="mb-1.5 text-[10.5px] font-semibold text-slate-400"></p>
                @endif

                <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto thin-scroll">
                @forelse ($dokumens as $d)
                    <div data-doc-item data-doc-text="{{ \Illuminate\Support\Str::lower($d->judul.' '.$d->file_name.' '.($d->kategori ?? '')) }}" class="flex items-center gap-2 py-2">
                        <span class="shrink-0 text-sm leading-none">{{ $d->visibility === 'bidang' ? '🔒' : '📄' }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('dokumen.show', $d) }}" class="block truncate text-xs font-bold text-slate-800 hover:text-purple-600 transition">{{ $d->judul }}</a>
                            <span class="block truncate text-[10.5px] text-slate-400">{{ $d->file_name }} · {{ number_format((int) $d->file_size / 1024) }} KB</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-purple-600 hover:underline whitespace-nowrap">Lihat</a>
                            <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:text-purple-600 whitespace-nowrap">Unduh</a>
                        </div>
                    </div>
                @empty
                    <p class="py-2 text-xs text-slate-400">Belum ada dokumen yang diunggah.</p>
                @endforelse
                </div>

                @if ($dokumens->isNotEmpty())
                    <p data-doc-none hidden class="py-2 text-xs text-slate-400">Tidak ada dokumen yang cocok.</p>
                @endif

                @if ($bolehKelola)
                    <form method="POST" action="{{ route('bidang.dokumen', $b) }}" enctype="multipart/form-data" class="mt-3 space-y-2 rounded-xl border border-dashed border-purple-300 bg-purple-50/30 p-3 text-xs">
                        @csrf
                        <p class="text-[11px] font-bold text-purple-700">Simpan Arsip Privat Bidang (Khusus Anggota)</p>
                        <input name="judul" placeholder="Judul dokumen *" required maxlength="180" class="w-full rounded-xl border border-slate-200 px-3 py-1.5 focus:outline-none focus:border-purple-400">
                        <input type="file" name="file" required class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-purple-600 file:px-3 file:py-1 file:text-xs file:font-bold file:text-white file:cursor-pointer">
                        <button class="w-full rounded-xl bg-purple-600 py-1.5 font-bold text-white hover:bg-purple-700 active:scale-95 transition shadow-sm">
                            Unggah ke Bidang Ini
                        </button>
                    </form>
                @endif

                <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="inline-block mt-3 text-xs font-bold text-purple-600 hover:underline">
                    Semua Dokumen Bidang Ini →
                </a>
            </div>
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

