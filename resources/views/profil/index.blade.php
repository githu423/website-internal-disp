@extends('layouts.dashboard')

@section('title', 'Profil Saya')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Data Kepegawaian & Pengaturan Akun Pribadi')

@section('content')
@php
    $isi = fn ($k) => trim((string) ($p->{$k} ?? ''));
    $ceklis = [
        'Tempat & tanggal lahir' => $isi('tempat_lahir') !== '' && $isi('tanggal_lahir') !== '',
        'Jenis kelamin' => $isi('jenis_kelamin') !== '',
        'Pangkat / golongan' => $isi('pangkat_golongan') !== '',
        'Pendidikan terakhir' => $isi('pendidikan') !== '',
        'Unit kerja' => $isi('unit_kerja') !== '',
        'TMT CPNS' => $isi('tmt_cpns') !== '',
        'TMT pangkat sekarang' => $isi('tmt_sekarang') !== '',
        'TMT jabatan' => $isi('tmt_jabatan') !== '',
    ];
    $terisi = count(array_filter($ceklis)); 
    $total = count($ceklis);
    $persen = round(($terisi / $total) * 100);
    $inp = 'w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition shadow-xs';
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-xs sm:text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <a href="{{ route('pegawai.index') }}" class="hover:text-purple-600 transition">Pegawai</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Profil Saya</span>
    </nav>

    <!-- 2. Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/70">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Profil Kepegawaian</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $u->isAdmin() ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                    {{ $u->isAdmin() ? 'Administrator' : ($isi('jenis_asn') ?: 'Pegawai') }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Lengkapi data profil kepegawaian Anda untuk sinkronisasi masa kerja dan arsip aparatur Dinas Pariwisata.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('pegawai.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2 bg-white border border-slate-200 text-slate-700 text-xs sm:text-sm font-semibold rounded-xl shadow-xs hover:bg-slate-50 hover:border-purple-300 hover:text-purple-700 active:scale-95 transition">
                <x-icon name="pegawai" class="w-4 h-4 text-purple-600" />
                <span>Lihat Daftar Pegawai</span>
            </a>
        </div>
    </div>

    <!-- 3. Grid Konten: Form Profil (Kiri) & Kartu Ringkasan (Kanan) -->
    <div class="grid lg:grid-cols-3 gap-5 items-start">
        
        <!-- Kolom Kiri: Form Data Kepegawaian -->
        <div class="lg:col-span-2 space-y-4 min-w-0">
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 sm:p-6 shadow-sm space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-base text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </span>
                            <span>Isi data kepegawaianmu</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                            Kolom yang Anda lengkapi di sini langsung terhubung ke <a class="text-purple-600 font-bold hover:underline" href="{{ route('pegawai.index') }}">Data Pegawai</a>, dan masa kerja (tahun, bulan, hari) dihitung otomatis mengikuti tanggal hari ini.
                        </p>
                    </div>
                </div>

                @if (! $p)
                    <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-800 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <x-icon name="alert" class="w-4 h-4 text-amber-600 shrink-0" />
                            <span>NIP Akun Belum Terdaftar di Tabel Pegawai</span>
                        </p>
                        <p>Menyimpan formulir ini akan membuatkan data aparatur baru ber-NIP <b>{{ $u->nip ?: '—' }}</b>.</p>
                        @if (! $u->nip)
                            <p class="text-amber-700 font-medium">Jika NIP belum terisi, hubungi Administrator untuk mengatur NIP akun Anda terlebih dahulu.</p>
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-700 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <x-icon name="alert" class="w-4 h-4 text-rose-600 shrink-0" />
                            <span>Periksa kembali isian formulir:</span>
                        </p>
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('profil.simpan') }}" class="space-y-6">
                    @csrf @method('PATCH')

                    <!-- Sub-bagian 1: Identitas Pribadi -->
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Identitas Diri &amp; Akun</span>
                        </div>
                        
                        <div class="grid sm:grid-cols-2 gap-3.5">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">
                                    Nama Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input class="{{ $inp }}" name="name" maxlength="120" required value="{{ old('name', $u->name) }}" placeholder="Nama lengkap beserta gelar jika ada">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Tempat Lahir</label>
                                <input class="{{ $inp }}" name="tempat_lahir" maxlength="100" placeholder="mis. Ciamis" value="{{ old('tempat_lahir', $isi('tempat_lahir')) }}">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Tanggal Lahir</label>
                                <input type="date" class="{{ $inp }}" name="tanggal_lahir" value="{{ old('tanggal_lahir', $p?->tanggal_lahir?->format('Y-m-d') ?? $isi('tanggal_lahir')) }}">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Jenis Kelamin</label>
                                <select class="{{ $inp }}" name="jenis_kelamin">
                                    <option value="">— Pilih Jenis Kelamin —</option>
                                    <option value="L" @selected(old('jenis_kelamin', $isi('jenis_kelamin')) === 'L')>Laki-laki (L)</option>
                                    <option value="P" @selected(old('jenis_kelamin', $isi('jenis_kelamin')) === 'P')>Perempuan (P)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Pendidikan Terakhir</label>
                                <input class="{{ $inp }}" name="pendidikan" maxlength="60" placeholder="mis. S1 Manajemen / D3 Pariwisata" value="{{ old('pendidikan', $isi('pendidikan')) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Sub-bagian 2: Kedudukan & Kepegawaian -->
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <span>Kedudukan &amp; Kepegawaian</span>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Jenis ASN</label>
                                <select class="{{ $inp }}" name="jenis_asn">
                                    <option value="">— Pilih Jenis ASN —</option>
                                    <option value="PNS" @selected(old('jenis_asn', $isi('jenis_asn')) === 'PNS')>PNS (Pegawai Negeri Sipil)</option>
                                    <option value="PPPK" @selected(old('jenis_asn', $isi('jenis_asn')) === 'PPPK')>PPPK (Pegawai Pemerintah dg Perjanjian Kerja)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Pangkat / Golongan</label>
                                <input class="{{ $inp }}" name="pangkat_golongan" maxlength="80" list="pangkat-op" placeholder="mis. PENATA (III/c)" value="{{ old('pangkat_golongan', $isi('pangkat_golongan')) }}">
                                <datalist id="pangkat-op">
                                    @foreach (['JURU (II/a)','JURU (II/b)','JURU (II/c)','PENGATUR (II/d)','PENGATUR (II/e)','PENGATUR (II/f)','PENATA (III/a)','PENATA (III/b)','PENATA (III/c)','PENATA (III/d)','PENATA TK. I (IV/a)','PENATA TK. I (IV/b)','PENATA TK. I (IV/c)'] as $pk)
                                        <option value="{{ $pk }}">
                                    @endforeach
                                </datalist>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Unit Kerja / Bidang</label>
                                <input class="{{ $inp }}" name="unit_kerja" maxlength="150" placeholder="mis. Sekretariat / Bidang Destinasi" value="{{ old('unit_kerja', $isi('unit_kerja')) }}">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Jabatan</label>
                                <input class="{{ $inp }}" name="jabatan" maxlength="150" placeholder="mis. Kepala Bidang / Pranata Humas" value="{{ old('jabatan', $isi('jabatan') ?: $u->jabatan) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Sub-bagian 3: Riwayat TMT & Masa Kerja -->
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-2 pb-1.5 border-b border-slate-100 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Riwayat TMT (Terhitung Mulai Tanggal)</span>
                        </div>

                        <div class="grid sm:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">TMT CPNS</label>
                                <input type="date" class="{{ $inp }}" name="tmt_cpns" value="{{ old('tmt_cpns', $p?->tmt_cpns?->format('Y-m-d') ?? $isi('tmt_cpns')) }}">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">TMT Pangkat Sekarang</label>
                                <input type="date" class="{{ $inp }}" name="tmt_sekarang" value="{{ old('tmt_sekarang', $p?->tmt_sekarang?->format('Y-m-d') ?? $isi('tmt_sekarang')) }}">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">TMT Jabatan</label>
                                <input type="date" class="{{ $inp }}" name="tmt_jabatan" value="{{ old('tmt_jabatan', $p?->tmt_jabatan?->format('Y-m-d') ?? $isi('tmt_jabatan')) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Form & Tombol Simpan -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p class="text-xs text-slate-400">
                            Setiap perubahan tersimpan otomatis dan tercatat pada log aktivitas sistem.
                        </p>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-95 text-white text-xs sm:text-sm font-bold rounded-xl shadow-sm shadow-purple-500/25 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan Perubahan Profil</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Kartu Profil, Kelengkapan Data & Informasi -->
        <div class="space-y-4">
            
            <!-- 1. Kartu Profil Ringkas Aparatur -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-4">
                <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
                    <div class="relative shrink-0">
                        <span class="h-14 w-14 rounded-2xl bg-gradient-to-tr from-purple-700 via-purple-600 to-indigo-600 grid place-items-center text-white text-base font-extrabold tracking-tight shadow-md shadow-purple-600/20 ring-4 ring-purple-50">
                            {{ strtoupper(mb_substr($u->name, 0, 2)) }}
                        </span>
                        <span class="absolute -bottom-1 -right-1 h-5 w-5 rounded-full bg-emerald-500 text-white grid place-items-center ring-2 ring-white shadow-xs" title="Akun Aktif">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-extrabold text-sm sm:text-base text-slate-900 truncate">{{ $u->name }}</p>
                        </div>
                        <span class="inline-flex items-center mt-1 px-2 py-0.5 text-[10px] font-extrabold rounded-full uppercase tracking-wider {{ $u->isAdmin() ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-purple-50 text-purple-700 border border-purple-200' }}">
                            {{ $u->role }}
                        </span>
                        <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $u->nip ?: 'Tanpa NIP' }}</p>
                    </div>
                </div>

                <dl class="text-xs space-y-2.5">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Unit / Bidang</dt>
                        <dd class="font-bold text-right text-slate-800">{{ $u->bidang?->nama ?? ($isi('unit_kerja') ?: 'Seluruh Dinas') }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Jabatan</dt>
                        <dd class="font-semibold text-right text-slate-700">{{ $isi('jabatan') ?: ($u->jabatan ?: '—') }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Pangkat / Gol.</dt>
                        <dd class="font-semibold text-right text-slate-700">{{ $isi('pangkat_golongan') ?: '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Masa Kerja</dt>
                        <dd class="font-bold text-right text-purple-700" title="Dihitung real-time sampai hari ini">{{ $p?->masa_kerja['label'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Usia</dt>
                        <dd class="font-bold text-right text-purple-700" title="Dihitung real-time dari tanggal lahir">
                            {{ $p?->umur !== null ? $p->umur . ' tahun' : '—' }}
                            @if ($p?->tanggal_lahir)
                                <span class="font-normal text-slate-400 text-[11px]">({{ $p->tanggal_lahir->translatedFormat('d M Y') }})</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">TMT CPNS</dt>
                        <dd class="font-semibold text-right text-slate-700">{{ $isi('tmt_cpns') ? \Carbon\Carbon::parse($isi('tmt_cpns'))->translatedFormat('d M Y') : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- 2. Kartu Status Kelengkapan Data Profil -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm space-y-3.5">
                <div class="flex items-center justify-between">
                    <h4 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-md bg-purple-50 text-purple-600 flex items-center justify-center">
                            <x-icon name="statistik" class="w-3.5 h-3.5" />
                        </span>
                        <span>Kelengkapan Data</span>
                    </h4>
                    <span class="text-xs font-black {{ $terisi === $total ? 'text-emerald-600' : 'text-purple-700' }}">
                        {{ $terisi }}/{{ $total }} ({{ $persen }}%)
                    </span>
                </div>

                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <span class="block h-full rounded-full {{ $terisi === $total ? 'bg-emerald-500' : 'bg-purple-600' }} transition-all duration-300" style="width: {{ $persen }}%"></span>
                </div>

                <ul class="space-y-1.5 text-xs">
                    @foreach ($ceklis as $nama => $sudah)
                        <li class="flex items-center gap-2.5 {{ $sudah ? 'text-slate-500' : 'text-slate-700' }}">
                            <span class="h-4 w-4 grid place-items-center rounded-full text-[9px] font-bold {{ $sudah ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                {{ $sudah ? '✓' : '•' }}
                            </span>
                            <span class="{{ $sudah ? 'line-through decoration-slate-300 text-slate-400' : 'font-medium' }}">{{ $nama }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($terisi === $total)
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-2.5 text-[11.5px] text-emerald-800 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Data profil Anda sudah terisi lengkap dan akurat!</span>
                    </div>
                @else
                    <p class="text-[11.5px] text-slate-400 leading-relaxed">
                        Lengkapi kolom yang tersisa agar profil Anda tampil utuh di tabel kepegawaian dinas.
                    </p>
                @endif
            </div>

        </div>
    </div>

</div>
@endsection
