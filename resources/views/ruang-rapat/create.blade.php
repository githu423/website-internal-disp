@extends('layouts.dashboard')

@section('title', 'Buat Ruang Rapat')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Buat tautan dokumen rapat untuk peserta dari luar dinas')

@section('content')
    @php
        $u = auth()->user();
        $kelompokOptions = [
            'agenda' => 'Agenda',
            'materi' => 'Materi',
            'pendukung' => 'Pendukung',
            'notulen' => 'Notulen',
        ];
        $unduhOptions = [
            '' => 'Ikuti pengaturan default',
            '1' => 'Izinkan unduh',
            '0' => 'Tidak boleh unduh',
        ];
        $pilihanLama = old('dokumen', []);
        $dokumenTerpilih = is_array($pilihanLama) ? array_map('strval', $pilihanLama) : [];
    @endphp

    <div class="space-y-6">
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <a href="{{ route('ruang-rapat.index') }}" class="hover:text-purple-600 transition">Ruang Rapat</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Buat Baru</span>
        </nav>

        <div class="flex flex-col gap-3 border-b border-slate-200/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Buat Ruang Rapat</h1>
                <p class="mt-1 text-sm text-slate-500">Hanya dokumen berstatus Publik yang dapat dimasukkan ke tautan tamu.</p>
            </div>
            <a href="{{ route('ruang-rapat.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                Kembali ke daftar
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-bold">Ruang Rapat belum dapat disimpan.</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('ruang-rapat.store') }}" class="space-y-5">
            @csrf

            <section class="clean-card space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Informasi rapat</h2>
                    <p class="mt-1 text-xs text-slate-500">Informasi ini akan terlihat oleh tamu yang membuka tautan.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="judul" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Judul rapat <span class="text-rose-500">*</span></label>
                        <input id="judul" name="judul" type="text" maxlength="180" required value="{{ old('judul') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Contoh: Rapat Koordinasi Persiapan Festival">
                    </div>
                    <div class="md:col-span-2">
                        <label for="deskripsi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="2000" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Keterangan singkat atau tujuan rapat">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div>
                        <label for="tanggal_rapat" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tanggal dan waktu</label>
                        <input id="tanggal_rapat" name="tanggal_rapat" type="datetime-local" value="{{ old('tanggal_rapat') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                    </div>
                    <div>
                        <label for="lokasi" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Lokasi</label>
                        <input id="lokasi" name="lokasi" type="text" maxlength="180" value="{{ old('lokasi') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Contoh: Ruang Rapat Utama">
                    </div>
                    <div>
                        <label for="link_virtual" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Tautan rapat virtual</label>
                        <input id="link_virtual" name="link_virtual" type="url" maxlength="255" value="{{ old('link_virtual') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="https://meet.google.com/...">
                    </div>
                    <div>
                        <label for="pic_nama" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">PIC</label>
                        <input id="pic_nama" name="pic_nama" type="text" maxlength="180" value="{{ old('pic_nama') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Nama penanggung jawab">
                    </div>
                    <div>
                        <label for="pic_kontak" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Kontak PIC</label>
                        <input id="pic_kontak" name="pic_kontak" type="text" maxlength="180" value="{{ old('pic_kontak') }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Nomor telepon atau email">
                    </div>
                    @if ($u->isAdmin())
                        <div>
                            <label for="id_bidang" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Bidang</label>
                            <select id="id_bidang" name="id_bidang" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                <option value="">Lintas bidang / tidak ditentukan</option>
                                @foreach ($bidangs as $bidang)
                                    <option value="{{ $bidang->id }}" @selected((string) old('id_bidang') === (string) $bidang->id)>{{ $bidang->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </section>

            <section class="clean-card space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Keamanan dan masa berlaku</h2>
                    <p class="mt-1 text-xs text-slate-500">PIN tidak wajib. Jika digunakan, sistem hanya menyimpan hash PIN.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="pin" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">PIN tamu (opsional)</label>
                        <input id="pin" name="pin" type="password" minlength="4" maxlength="32" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Minimal 4 karakter">
                    </div>
                    <div>
                        <label for="berlaku_sampai" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Berlaku sampai <span class="text-rose-500">*</span></label>
                        <input id="berlaku_sampai" name="berlaku_sampai" type="date" min="{{ now()->toDateString() }}" required value="{{ old('berlaku_sampai', now()->addDays(30)->toDateString()) }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                    </div>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 md:col-span-2">
                        <input type="hidden" name="izinkan_unduh_default" value="0">
                        <input type="checkbox" name="izinkan_unduh_default" value="1" @checked(old('izinkan_unduh_default', '1') === '1') class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20">
                        <span>
                            <span class="block text-sm font-bold text-slate-700">Izinkan unduhan secara default</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Setiap dokumen dapat mengubah pengaturan ini secara terpisah.</span>
                        </span>
                    </label>
                </div>
            </section>

            <section class="clean-card space-y-4 p-5 sm:p-6">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">Pilih dokumen rapat</h2>
                        <p class="mt-1 text-xs text-slate-500">Dokumen bidang dan rahasia tidak dapat ditambahkan ke tautan tamu.</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $dokumen->count() }} dokumen publik tersedia</span>
                </div>

                @if ($dokumen->isEmpty())
                    <x-empty icon="dokumen" title="Belum ada dokumen publik" desc="Unggah dokumen dengan visibilitas Publik sebelum membuat paket rapat." />
                @else
                    <div class="space-y-3">
                        @foreach ($dokumen as $item)
                            @php $ext = strtolower(pathinfo($item->file_name, PATHINFO_EXTENSION)); @endphp
                            <div class="rounded-xl border border-slate-200 p-4 transition hover:border-purple-200">
                                <div class="flex items-start gap-3">
                                    <input id="dokumen-{{ $item->id }}" type="checkbox" name="dokumen[]" value="{{ $item->id }}" @checked(in_array((string) $item->id, $dokumenTerpilih, true)) class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500/20">
                                    <x-file-icon :ext="$ext" class="h-10 w-8 rounded-md text-[9px]" />
                                    <label for="dokumen-{{ $item->id }}" class="min-w-0 flex-1 cursor-pointer">
                                        <span class="block truncate text-sm font-bold text-slate-800">{{ $item->judul }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $item->file_name }} · {{ $item->bidang?->nama ?: 'Lintas bidang' }}</span>
                                    </label>
                                    <span class="shrink-0 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-600">Publik</span>
                                </div>
                                <div class="mt-4 grid gap-3 pl-7 sm:grid-cols-2">
                                    <div>
                                        <label for="kelompok-{{ $item->id }}" class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Kelompok</label>
                                        <select id="kelompok-{{ $item->id }}" name="kelompok[{{ $item->id }}]" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                            @foreach ($kelompokOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('kelompok.'.$item->id, 'materi') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="unduh-{{ $item->id }}" class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Akses unduh</label>
                                        <select id="unduh-{{ $item->id }}" name="izinkan_unduh[{{ $item->id }}]" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20">
                                            @foreach ($unduhOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('ruang-rapat.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">Batal</a>
                <button type="submit" @disabled($dokumen->isEmpty()) class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-purple-500/30 transition hover:from-purple-700 hover:to-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <x-icon name="plus" class="h-4 w-4" /> Buat Ruang Rapat
                </button>
            </div>
        </form>
    </div>
@endsection
