@extends('layouts.dashboard')

@section('title', 'Kelola Prestasi')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Daftar Prestasi & Penghargaan Dinas Pariwisata')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Prestasi</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Prestasi &amp; Penghargaan Dinas</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $totalCount }} Penghargaan
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Daftar capaian dan penghargaan — tampil otomatis pada slider showcase di Dashboard Utama.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('dashboard') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                &larr; Lihat di Dashboard
            </a>
            <button data-modal-open="modal-prestasi" 
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Prestasi
            </button>
        </div>
    </div>

    <!-- 3. Info Rotasi & Pengaturan Tampilan Dashboard -->
    <div class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50/70 via-white to-purple-50/30 p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <span class="text-2xl select-none shrink-0">🏆</span>
                <div>
                    <h4 class="text-sm font-extrabold text-slate-900">Mekanisme Rotasi Galeri Dashboard</h4>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed max-w-3xl">
                        Dashboard Utama menampilkan cuplikan prestasi dinas yang berotasi otomatis setiap hari. Untuk memastikan penghargaan tertentu <strong>pasti tampil hari ini</strong>, klik tombol <strong>☆ Jadikan Pilihan</strong> pada tabel di bawah.
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-2.5">
                        @if ($featuredCount > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                ⭐ {{ $featuredCount }} dipilih manual
                            </span>
                            <span class="text-xs text-slate-500">
                                + {{ max(0, 10 - $featuredCount) }} slot lainnya diisi rotasi otomatis
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                🔄 Mode Penuh: Berotasi otomatis setiap hari
                            </span>
                        @endif
                        <span class="text-xs text-slate-400">·</span>
                        <span class="text-xs text-slate-500">Total <strong>{{ $totalCount }}</strong> prestasi terdata</span>
                    </div>
                </div>
            </div>

            @if ($featuredCount > 0)
                <form method="POST" action="{{ route('admin.prestasi.resetFeatured') }}" onsubmit="return confirm('Kembalikan semua slot ke rotasi otomatis harian?')">
                    @csrf
                    <button type="submit" class="rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold px-3.5 py-2 transition active:scale-95 shadow-sm whitespace-nowrap">
                        🔄 Reset ke Rotasi Penuh
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- 4. Card Impor Berkas Prestasi -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-start gap-3.5 mb-3">
            <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                <x-icon name="upload" class="h-5 w-5" />
            </span>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-[15px] text-slate-900">Impor Berkas Daftar Prestasi (Excel/CSV)</h3>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                    Format file daftar penghargaan dinas: blok diawali baris «TAHUN 20XX», lalu kolom No | Nama Penghargaan | Nama Penerima | Pemberi Penghargaan | Tahun | Tingkat. Data tahun yang sama diperbarui otomatis.
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.prestasi.import') }}" enctype="multipart/form-data" data-import class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 mt-3">
            @csrf
            <label class="flex-1 min-w-0 border border-slate-200 rounded-xl p-1 bg-white cursor-pointer">
                <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                    class="block w-full text-xs text-slate-500 file:mr-2.5 file:rounded-lg file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white file:cursor-pointer transition">
            </label>
            <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-[.98] disabled:opacity-50 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 transition whitespace-nowrap">
                Proses &amp; Impor Berkas
            </button>
        </form>
    </div>

    <!-- 5. Kontainer Tabel Data Prestasi -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[840px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="py-3 px-4">Nama Penghargaan</th>
                        <th class="py-3 px-3">Penerima</th>
                        <th class="py-3 px-3">Pemberi</th>
                        <th class="w-20 text-center py-3 px-3">Tahun</th>
                        <th class="w-32 py-3 px-3">Tingkat</th>
                        <th class="w-44 text-center py-3 px-3">Tampil Dashboard</th>
                        <th class="w-24 text-right py-3 px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($items as $p)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4 font-semibold text-[13px] text-slate-900 leading-snug">
                            {{ $p->nama_penghargaan }}
                            @if ($p->is_featured)
                                <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
                                    ⭐ PILIHAN HARI INI
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-xs text-slate-600">{{ $p->nama_penerima ?: '—' }}</td>
                        <td class="py-3.5 px-3 text-xs text-slate-500">{{ $p->pemberi_penghargaan ?: '—' }}</td>
                        <td class="py-3.5 px-3 text-xs text-center font-bold text-slate-900">TA {{ $p->tahun }}</td>
                        <td class="py-3.5 px-3 text-xs text-slate-600">
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                {{ $p->tingkat ?: 'Kabupaten' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            <form method="POST" action="{{ route('admin.prestasi.toggleFeatured', $p) }}">
                                @csrf
                                @if ($p->is_featured)
                                    <button type="submit" title="Klik untuk mengembalikan ke rotasi otomatis" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 transition active:scale-95 shadow-sm">
                                        ⭐ Tampil Hari Ini
                                    </button>
                                @else
                                    <button type="submit" title="Prioritaskan tampil di galeri dashboard hari ini" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-semibold text-slate-600 bg-white hover:bg-purple-50 hover:text-purple-700 border border-slate-200 transition active:scale-95">
                                        ☆ Jadikan Pilihan
                                    </button>
                                @endif
                            </form>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <button data-modal-open="edit-prestasi-{{ $p->id }}" 
                                        class="px-2 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('admin.prestasi.destroy', $p) }}" onsubmit="return confirm('Hapus data prestasi ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Edit Prestasi --}}
                    <div id="edit-prestasi-{{ $p->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
                        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-2xl text-sm">
                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                                <div>
                                    <h3 class="font-extrabold text-base text-slate-900">Edit Prestasi / Penghargaan</h3>
                                    <p class="text-xs text-slate-400 mt-0.5">ID #{{ $p->id }}</p>
                                </div>
                                <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
                            </div>
                            @if ($errors->any())
                                <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                                    {{ $errors->first() }}
                                </div>
                            @endif
                            <form method="POST" action="{{ route('admin.prestasi.update', $p) }}" class="space-y-3">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Penghargaan *</label>
                                    <input name="nama_penghargaan" value="{{ $p->nama_penghargaan }}" required maxlength="500" 
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Penerima</label>
                                    <input name="nama_penerima" value="{{ $p->nama_penerima }}" maxlength="255" 
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pemberi Penghargaan</label>
                                    <input name="pemberi_penghargaan" value="{{ $p->pemberi_penghargaan }}" maxlength="255" 
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahun *</label>
                                        <input type="number" name="tahun" value="{{ $p->tahun }}" min="1900" max="2100" required 
                                               class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tingkat</label>
                                        <input name="tingkat" value="{{ $p->tingkat }}" maxlength="50" placeholder="Provinsi / Nasional" 
                                               class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                    </div>
                                </div>
                                <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                                    Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center">
                            <x-empty icon="prestasi" title="Belum ada data prestasi dinas" desc="Tambah secara manual melalui tombol di kanan atas atau impor berkas data." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal Tambah Prestasi Manual --}}
<div id="modal-prestasi" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Tambah Prestasi Baru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Input data penghargaan yang diraih dinas.</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('admin.prestasi.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Penghargaan *</label>
                <input name="nama_penghargaan" placeholder="mis. Juara 1 Anugerah Desa Wisata Indonesia" required maxlength="500" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Penerima</label>
                <input name="nama_penerima" placeholder="Nama pegawai / unit bidang / dinas" maxlength="255" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pemberi Penghargaan</label>
                <input name="pemberi_penghargaan" placeholder="mis. Kementerian Pariwisata dan Ekonomi Kreatif" maxlength="255" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahun *</label>
                    <input type="number" name="tahun" value="{{ now()->year }}" min="1900" max="2100" required 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tingkat</label>
                    <input name="tingkat" maxlength="50" placeholder="Provinsi / Nasional" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
            </div>
            <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Simpan Prestasi
            </button>
        </form>
    </div>
</div>
@endsection
