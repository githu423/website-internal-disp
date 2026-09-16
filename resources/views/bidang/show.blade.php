@extends('layouts.dashboard')

@section('title', $b->nama)
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Profil Bidang & Unit Kerja Dinas')

@section('content')
@php
    $u = auth()->user();
    $isMine = $u->id_bidang === $b->id;

    // Helper identifikasi icon & tema per bidang
    $getBidangMeta = function ($item) {
        $nama = strtolower($item->nama ?? '');
        $kode = strtolower($item->kode ?? '');
        
        if (str_contains($nama, 'sekretariat') || str_contains($kode, 'sekret')) {
            return [
                'icon' => '🏛️',
                'color' => 'from-purple-600 to-indigo-600',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'tag' => 'Administrasi & Kepegawaian',
            ];
        }
        if (str_contains($nama, 'destinasi')) {
            return [
                'icon' => '🏞️',
                'color' => 'from-emerald-600 to-teal-600',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'tag' => 'Destinasi & Daya Tarik Wisata',
            ];
        }
        if (str_contains($nama, 'pemasaran') || str_contains($nama, 'promosi')) {
            return [
                'icon' => '📢',
                'color' => 'from-blue-600 to-indigo-600',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                'tag' => 'Pemasaran & Promosi Wisata',
            ];
        }
        if (str_contains($nama, 'ekonomi kreatif') || str_contains($nama, 'ekraf')) {
            return [
                'icon' => '🎨',
                'color' => 'from-pink-600 to-purple-600',
                'badge' => 'bg-pink-50 text-pink-700 border-pink-200',
                'tag' => 'Ekonomi Kreatif & Kemitraan',
            ];
        }
        if (str_contains($nama, 'kawali')) {
            return [
                'icon' => '🏯',
                'color' => 'from-amber-600 to-orange-600',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'tag' => 'UPTD Budaya & Sejarah Galuh',
            ];
        }
        if (str_contains($nama, 'ciamis') || str_contains($nama, 'uptd')) {
            return [
                'icon' => '🏖️',
                'color' => 'from-teal-600 to-cyan-600',
                'badge' => 'bg-teal-50 text-teal-700 border-teal-200',
                'tag' => 'UPTD Rekreasi & Objek Wisata',
            ];
        }

        return [
            'icon' => '🏢',
            'color' => 'from-purple-600 to-indigo-600',
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'tag' => 'Unit Pelaksana Teknis',
        ];
    };

    $currentMeta = $getBidangMeta($b);
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="hover:text-purple-600 transition">Kantor</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('bidang.index') }}" class="hover:text-purple-600 transition">Bidang Dinas</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold truncate">{{ $b->nama }}</span>
    </nav>

    <!-- 2. Navigasi Switcher Antar-Bidang (Pill Carousel Modern) -->
    <div class="flex items-center gap-2 overflow-x-auto thin-scroll pb-1">
        <a href="{{ route('bidang.index') }}" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-purple-300 hover:text-purple-700 transition shadow-sm shrink-0">
            &larr; Semua Bidang
        </a>
        <div class="h-4 w-px bg-slate-200 shrink-0 mx-1"></div>
        @foreach ($semuaBidang as $sb)
            @php 
                $sbMeta = $getBidangMeta($sb);
                $isActive = $sb->id === $b->id;
                $isUserBidang = $sb->id === $u->id_bidang;
            @endphp
            <a href="{{ route('bidang.show', $sb) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition shrink-0 {{ $isActive ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/30' : 'bg-white border border-slate-200 text-slate-700 hover:border-purple-300 hover:text-purple-700 hover:bg-slate-50' }}">
                <span>{{ $sbMeta['icon'] }}</span>
                <span>{{ $sb->nama }}</span>
                @if ($isUserBidang && !$isActive)
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" title="Bidang Anda"></span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- 3. Hero Card Profil Bidang -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <span class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-gradient-to-br {{ $currentMeta['color'] }} text-white text-3xl sm:text-4xl grid place-items-center shadow-md shadow-purple-500/20 shrink-0 select-none">
                    {{ $currentMeta['icon'] }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900">
                            {{ $b->nama }}
                        </h1>
                        @if ($isMine)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                ✓ BIDANG ANDA
                            </span>
                        @elseif ($u->isAdmin())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                MODE ADMINISTRATOR
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                Unit Kerja Eksternal
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed max-w-3xl">
                        {{ $b->deskripsi ?: 'Unit kerja operasional dalam lingkup Dinas Pariwisata Kabupaten Ciamis.' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                        <span class="font-bold text-slate-700">Kepala / Koordinator:</span>
                        <span class="text-purple-700 font-semibold">{{ $b->ketua ?: '—' }}</span>
                    </p>
                </div>
            </div>

            @if ($bolehKelola)
                <div class="shrink-0">
                    <button data-modal-open="modal-edit-bidang" 
                            class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:border-purple-300 hover:text-purple-700 text-xs sm:text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 active:scale-95 transition">
                        <span>✏️</span> Edit Profil Bidang
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- 4. Konten Utama: Tabel Aparatur (Kiri) & Agenda / Dokumen (Kanan) -->
    <div class="grid lg:grid-cols-3 gap-5">
        <!-- Kolom Kiri: Tabel Aparatur Bidang -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                            <span>👥</span> Aparatur Bidang
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $pegawais->count() }} personel aparatur bertugas di unit ini</p>
                    </div>
                </div>

                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[620px]">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                <th class="py-3 px-4">Nama &amp; NIP</th>
                                <th class="py-3 px-3">Jabatan</th>
                                <th class="py-3 px-3">Golongan / Pangkat</th>
                                <th class="py-3 px-4 text-right">Masa Kerja &amp; Usia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($pegawais as $p)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <span class="h-9 w-9 rounded-xl bg-gradient-to-br {{ $currentMeta['color'] }} text-white text-xs font-bold grid place-items-center shrink-0 shadow-sm">
                                            {{ strtoupper(mb_substr($p->nama, 0, 2)) }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-[13px] text-slate-900 leading-snug">{{ $p->nama }}</p>
                                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $p->nip ?: 'Tanpa NIP' }} · {{ $p->jenis_asn }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-700 font-medium">
                                    {{ $p->jabatan ?: '—' }}
                                </td>
                                <td class="py-3.5 px-3 text-xs text-slate-600">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                        {{ $p->pangkat_golongan ?: '—' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-right whitespace-nowrap">
                                    <p class="font-semibold text-slate-800">{{ $p->masa_kerja['label'] }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $p->umur !== null ? $p->umur . ' tahun' : '—' }}
                                    </p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center">
                                    <x-empty icon="pegawai" title="Belum ada pegawai di bidang ini" desc="Admin dapat mengatur penempatan pegawai melalui menu Kelola Pegawai." />
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Rekap Statistik Bawah Tabel -->
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-500 font-medium">
                    <span class="font-extrabold text-slate-800">Komposisi:</span>
                    <span><b class="text-slate-900">{{ $statistik['semua'] }}</b> Total</span>
                    <span class="text-sky-700">♂ <b>{{ $statistik['l'] }}</b> Laki-laki</span>
                    <span class="text-pink-600">♀ <b>{{ $statistik['p'] }}</b> Perempuan</span>
                    <span class="text-purple-700"><b>{{ $statistik['pns'] }}</b> PNS</span>
                    <span class="text-indigo-700"><b>{{ $statistik['nonpns'] }}</b> Non-PNS</span>
                    <span class="text-emerald-700"><b>{{ $statistik['aktif'] }}</b> Aktif</span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Agenda & Dokumen Bidang -->
        <div class="space-y-5">
            <!-- 1. Card Agenda Bidang -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span>📅</span> Agenda Bidang
                    </h3>
                    <a href="{{ route('agenda.index', ['bulan' => now()->format('Y-m')]) }}" class="text-xs font-bold text-purple-600 hover:underline">
                        Kalender Dinas &rarr;
                    </a>
                </div>

                <div class="space-y-2.5">
                    @forelse ($agenda as $a)
                        <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 grid place-items-center text-center">
                                <span class="text-xs font-black leading-none">{{ $a->tanggal->format('d') }}</span>
                                <span class="text-[8.5px] font-bold uppercase mt-0.5">{{ $a->tanggal->translatedFormat('M') }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-slate-900 leading-snug">{{ $a->judul }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $a->jam_mulai ? substr((string)$a->jam_mulai, 0, 5) . ' WIB' : 'Waktu menyusul' }}
                                    @if ($a->lokasi) · 📍 {{ $a->lokasi }} @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">Belum ada agenda terdaftar untuk bidang ini.</p>
                    @endforelse
                </div>

                @if ($bolehKelola)
                    <details class="group mt-2">
                        <summary class="cursor-pointer list-none rounded-xl border border-dashed border-purple-300 bg-purple-50/50 hover:bg-purple-50 px-3.5 py-2 text-xs font-bold text-purple-700 transition flex items-center justify-between">
                            <span>➕ Tambah Agenda Bidang</span>
                            <span class="transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <form method="POST" action="{{ route('bidang.agenda', $b) }}" class="mt-2.5 space-y-2.5 text-xs bg-white p-3 rounded-xl border border-slate-200">
                            @csrf
                            <input name="judul" required maxlength="180" placeholder="Judul agenda *" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 focus:outline-none focus:border-purple-400">
                            <div class="grid grid-cols-2 gap-2">
                                <input type="date" name="tanggal" required class="rounded-xl border border-slate-200 px-3 py-1.5">
                                <input type="time" name="jam_mulai" value="09:00" class="rounded-xl border border-slate-200 px-2.5 py-1.5">
                            </div>
                            <input name="lokasi" maxlength="120" placeholder="Lokasi kegiatan" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 focus:outline-none focus:border-purple-400">
                            <button class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 font-bold text-white py-2 active:scale-95 transition shadow-sm">
                                Simpan Agenda
                            </button>
                        </form>
                    </details>
                @endif
            </div>

            <!-- 2. Card Dokumen Bidang -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                            <span>📄</span> Dokumen Bidang
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            {{ $bolehKelola ? 'Termasuk dokumen privat sebidang.' : 'Dokumen publik unit ini.' }}
                        </p>
                    </div>
                    <a href="{{ route('dokumen.index', ['bidang' => $b->id]) }}" class="text-xs font-bold text-purple-600 hover:underline">
                        Semua &rarr;
                    </a>
                </div>

                @if ($dokumens->isNotEmpty())
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">🔍</span>
                        <input data-doc-q type="search" placeholder="Cari judul berkas…" autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-1.5 pl-8 pr-8 text-xs outline-none focus:border-purple-400 focus:bg-white transition">
                        <button data-doc-clear type="button" hidden class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-1.5 py-0.5 text-[10px] font-bold text-slate-400 hover:bg-slate-100">✕</button>
                    </div>
                @endif

                <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto thin-scroll">
                @forelse ($dokumens as $d)
                    <div data-doc-item data-doc-text="{{ \Illuminate\Support\Str::lower($d->judul.' '.$d->file_name.' '.($d->kategori ?? '')) }}" 
                         class="flex items-center gap-2.5 py-2.5">
                        <span class="shrink-0 text-base leading-none">{{ $d->visibility === 'bidang' ? '🔒' : '📄' }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('dokumen.show', $d) }}" class="block truncate text-xs font-bold text-slate-800 hover:text-purple-600 transition">{{ $d->judul }}</a>
                            <span class="block truncate text-[10.5px] text-slate-400">{{ $d->file_name }} · {{ number_format((int) $d->file_size / 1024) }} KB</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-purple-600 hover:bg-purple-50 px-2 py-0.5 rounded transition">Lihat</a>
                            <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:bg-slate-100 px-2 py-0.5 rounded transition">Unduh</a>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-xs text-slate-400 text-center">Belum ada dokumen yang diunggah.</p>
                @endforelse
                </div>

                @if ($bolehKelola)
                    <form method="POST" action="{{ route('bidang.dokumen', $b) }}" enctype="multipart/form-data" 
                          class="mt-2 space-y-2 rounded-xl border border-dashed border-purple-300 bg-purple-50/30 p-3 text-xs">
                        @csrf
                        <p class="text-[11px] font-bold text-purple-700">Unggah Dokumen Internal Bidang</p>
                        <input name="judul" placeholder="Judul dokumen *" required maxlength="180" 
                               class="w-full rounded-xl border border-slate-200 px-3 py-1.5 bg-white focus:outline-none focus:border-purple-400">
                        <input type="file" name="file" required 
                               class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-purple-600 file:px-3 file:py-1 file:text-xs file:font-bold file:text-white file:cursor-pointer">
                        <button class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 py-1.5 font-bold text-white active:scale-95 transition shadow-sm">
                            Unggah ke Bidang Ini
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Modal Edit Profil Bidang --}}
@if ($bolehKelola)
<div id="modal-edit-bidang" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Edit Profil Bidang</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ $b->nama }}</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        <form method="POST" action="{{ route('bidang.update', $b) }}" class="space-y-3">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Bidang *</label>
                <input name="nama" value="{{ $b->nama }}" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kepala / Koordinator</label>
                <input name="ketua" value="{{ $b->ketua }}" placeholder="Nama kepala bidang / unit" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Deskripsi &amp; Uraian Tugas</label>
                <textarea name="deskripsi" rows="3" placeholder="Uraian tugas fungsi pokok bidang..." 
                          class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">{{ $b->deskripsi }}</textarea>
            </div>
            <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Simpan Perubahan
            </button>
        </form>
    </div>
</div>
@endif

<script>
(function(){
    const q=document.querySelector('[data-doc-q]'); if(!q) return;
    const rows=[...document.querySelectorAll('[data-doc-item]')];
    const clr=document.querySelector('[data-doc-clear]');
    const upd=()=>{
        const t=q.value.trim().toLowerCase();
        rows.forEach(r=>{const hit=!t||r.dataset.docText.includes(t); r.hidden=!hit;});
        if(clr) clr.hidden = !t;
    };
    q.addEventListener('input',upd);
    if(clr) clr.addEventListener('click',()=>{q.value='';upd();q.focus();});
    upd();
})();
</script>
@endsection
