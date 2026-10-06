@extends('layouts.dashboard')

@section('title', $ruangRapat->judul)
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Pengaturan tautan dokumen untuk tamu rapat')

@section('content')
    @php
        $isKedaluwarsa = $ruangRapat->sudahKedaluwarsa();
        $labelStatus = $ruangRapat->status === 'nonaktif' ? 'Nonaktif' : ($isKedaluwarsa ? 'Kedaluwarsa' : 'Aktif');
        $badgeStatus = $labelStatus === 'Aktif'
            ? 'border border-emerald-200 bg-emerald-50 text-emerald-600'
            : 'border border-slate-200 bg-slate-100 text-slate-500';
        $kelompokLabels = [
            'agenda' => 'Agenda',
            'materi' => 'Materi',
            'pendukung' => 'Pendukung',
            'notulen' => 'Notulen',
        ];
        $dokumenPerKelompok = $ruangRapat->dokumen->groupBy(fn ($dokumen) => $dokumen->pivot->kelompok);
    @endphp

    <div class="space-y-6">
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <a href="{{ route('ruang-rapat.index') }}" class="hover:text-purple-600 transition">Ruang Rapat</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="max-w-[220px] truncate font-semibold text-slate-700">{{ $ruangRapat->judul }}</span>
        </nav>

        <div class="flex flex-col gap-4 border-b border-slate-200/60 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $ruangRapat->judul }}</h1>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold {{ $badgeStatus }}">{{ $labelStatus }}</span>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $ruangRapat->bidang?->nama ?: 'Rapat lintas bidang' }} · {{ $ruangRapat->dokumen->count() }} dokumen</p>
            </div>
            <a href="{{ route('ruang-rapat.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                Kembali ke daftar
            </a>
        </div>

        @if (session('sukses'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('sukses') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-bold">Periksa kembali isian berikut.</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="clean-card overflow-hidden">
            <div class="border-b border-slate-200/70 bg-slate-50/70 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-purple-50 text-purple-600"><x-icon name="admin" class="h-4 w-4" /></span>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Tautan tamu</h2>
                        <p class="text-xs text-slate-500">Siapa pun dengan tautan ini dapat membuka paket selama masih aktif.</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:p-6">
                <input type="text" readonly value="{{ $tautanPublik }}" aria-label="Tautan tamu" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                <a href="{{ $tautanPublik }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                    <x-icon name="dokumen" class="h-4 w-4" /> Buka sebagai tamu
                </a>
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-200/70 px-5 py-3 text-xs text-slate-500 sm:px-6">
                <span>{{ $ruangRapat->pin_hash ? 'PIN aktif' : 'Tanpa PIN' }}</span>
                <span>Unduh default: {{ $ruangRapat->izinkan_unduh_default ? 'Diizinkan' : 'Tidak diizinkan' }}</span>
                <span>{{ $ruangRapat->dibuka_count }} akses dokumen</span>
                <span>Berlaku sampai {{ $ruangRapat->berlaku_sampai->translatedFormat('d F Y') }}</span>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(300px,.8fr)]">
            <section class="clean-card space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Informasi dan pengaturan akses</h2>
                    <p class="mt-1 text-xs text-slate-500">Kosongkan PIN untuk mempertahankan PIN saat ini; gunakan opsi hapus untuk menonaktifkannya.</p>
                </div>

                <form method="POST" action="{{ route('ruang-rapat.update', $ruangRapat) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="judul" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Judul rapat <span class="text-rose-500">*</span></label>
                        <input id="judul" name="judul" type="text" maxlength="180" required value="{{ old('judul', $ruangRapat->judul) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                    </div>
                    <div>
                        <label for="deskripsi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">{{ old('deskripsi', $ruangRapat->deskripsi) }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="tanggal_rapat" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tanggal dan waktu</label>
                            <input id="tanggal_rapat" name="tanggal_rapat" type="datetime-local" value="{{ old('tanggal_rapat', $ruangRapat->tanggal_rapat?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        <div>
                            <label for="lokasi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Lokasi</label>
                            <input id="lokasi" name="lokasi" type="text" maxlength="180" value="{{ old('lokasi', $ruangRapat->lokasi) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        <div>
                            <label for="link_virtual" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tautan rapat virtual</label>
                            <input id="link_virtual" name="link_virtual" type="url" maxlength="255" value="{{ old('link_virtual', $ruangRapat->link_virtual) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        <div>
                            <label for="pic_nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">PIC</label>
                            <input id="pic_nama" name="pic_nama" type="text" maxlength="180" value="{{ old('pic_nama', $ruangRapat->pic_nama) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        <div>
                            <label for="pic_kontak" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Kontak PIC</label>
                            <input id="pic_kontak" name="pic_kontak" type="text" maxlength="180" value="{{ old('pic_kontak', $ruangRapat->pic_kontak) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        @if (auth()->user()->isAdmin())
                            <div>
                                <label for="id_bidang" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Bidang</label>
                                <select id="id_bidang" name="id_bidang" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                    <option value="">Lintas bidang / tidak ditentukan</option>
                                    @foreach ($bidangs as $bidang)
                                        <option value="{{ $bidang->id }}" @selected((string) old('id_bidang', $ruangRapat->id_bidang) === (string) $bidang->id)>{{ $bidang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pin" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">PIN baru (opsional)</label>
                            <input id="pin" name="pin" type="password" minlength="4" maxlength="32" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Kosongkan bila tidak diubah">
                        </div>
                        <div>
                            <label for="berlaku_sampai" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Berlaku sampai <span class="text-rose-500">*</span></label>
                            <input id="berlaku_sampai" name="berlaku_sampai" type="date" min="{{ now()->toDateString() }}" required value="{{ old('berlaku_sampai', $ruangRapat->berlaku_sampai->toDateString()) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                        </div>
                        @if ($ruangRapat->pin_hash)
                            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:col-span-2">
                                <input type="checkbox" name="hapus_pin" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20">
                                <span class="text-sm font-semibold text-slate-700">Hapus PIN dan izinkan tamu membuka tautan tanpa PIN</span>
                            </label>
                        @endif
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 sm:col-span-2">
                            <input type="hidden" name="izinkan_unduh_default" value="0">
                            <input type="checkbox" name="izinkan_unduh_default" value="1" @checked(old('izinkan_unduh_default', $ruangRapat->izinkan_unduh_default ? '1' : '0') === '1') class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20">
                            <span>
                                <span class="block text-sm font-bold text-slate-700">Izinkan unduhan secara default</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Pilihan akses khusus tiap dokumen tetap mengikuti override di paket.</span>
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-purple-500/30 transition hover:from-purple-700 hover:to-indigo-700">
                        <x-icon name="kinerja" class="h-4 w-4" /> Simpan pengaturan
                    </button>
                </form>
            </section>

            <div class="space-y-5">
                <section class="clean-card space-y-4 p-5 sm:p-6">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Dokumen dalam paket</h2>
                        <p class="mt-1 text-xs text-slate-500">Semua dokumen di sini berstatus Publik.</p>
                    </div>
                    @if ($ruangRapat->dokumen->isEmpty())
                        <x-empty icon="dokumen" title="Belum ada dokumen" desc="Dokumen akan tampil di sini setelah ditambahkan." />
                    @else
                        <div class="space-y-4">
                            @foreach ($dokumenPerKelompok as $kelompok => $items)
                                <div>
                                    <h3 class="mb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ $kelompokLabels[$kelompok] ?? ucfirst($kelompok) }}</h3>
                                    <div class="space-y-2">
                                        @foreach ($items as $dokumen)
                                            @php
                                                $ext = strtolower(pathinfo($dokumen->file_name, PATHINFO_EXTENSION));
                                                $bolehUnduh = $dokumen->pivot->izinkan_unduh === null
                                                    ? $ruangRapat->izinkan_unduh_default
                                                    : (bool) $dokumen->pivot->izinkan_unduh;
                                            @endphp
                                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                                                <x-file-icon :ext="$ext" class="h-9 w-7 rounded-md text-[9px]" />
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-xs font-bold text-slate-800">{{ $dokumen->judul }}</p>
                                                    <p class="mt-0.5 text-[10px] text-slate-500">{{ $bolehUnduh ? 'Lihat dan unduh' : 'Lihat saja' }}</p>
                                                </div>
                                                <a href="{{ route('rapat.lihat', ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id]) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-bold text-slate-600 transition hover:border-purple-300 hover:text-purple-600">Lihat</a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="clean-card space-y-4 p-5 sm:p-6">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Unggah notulen</h2>
                        <p class="mt-1 text-xs text-slate-500">File baru disimpan sebagai dokumen Publik dan otomatis masuk ke kelompok Notulen.</p>
                    </div>
                    <form method="POST" action="{{ route('ruang-rapat.notulen.upload', $ruangRapat) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label for="judul-notulen" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Judul (opsional)</label>
                            <input id="judul-notulen" name="judul" type="text" maxlength="180" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Diambil dari nama file bila kosong">
                        </div>
                        <div>
                            <label for="file-notulen" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">File notulen <span class="text-rose-500">*</span></label>
                            <input id="file-notulen" name="file" type="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.txt" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-700 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                            @error('file')
                                <span class="mt-1 block text-xs font-semibold text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                            <x-icon name="upload" class="h-4 w-4" /> Unggah notulen
                        </button>
                    </form>
                </section>

                <section class="clean-card flex items-start gap-3 border-rose-200 p-5 sm:p-6">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="alert" class="h-4 w-4" /></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-sm font-extrabold text-slate-900">Matikan tautan tamu</h2>
                        @if ($ruangRapat->status === 'nonaktif')
                            <p class="mt-1 text-xs text-slate-500">Tautan ini sudah dinonaktifkan secara manual.</p>
                        @else
                            <p class="mt-1 text-xs text-slate-500">Tamu tidak dapat membuka dokumen setelah tautan dinonaktifkan. Aksi ini tidak menghapus data.</p>
                            <form method="POST" action="{{ route('ruang-rapat.nonaktifkan', $ruangRapat) }}" class="mt-3">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-rose-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50">Nonaktifkan tautan</button>
                            </form>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
