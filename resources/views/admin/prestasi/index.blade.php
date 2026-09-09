@extends('layouts.dashboard')
@section('title', 'Kelola Prestasi')
@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Kelola Prestasi &amp; Penghargaan</h2>
        <p class="text-sm text-slate-500">Daftar penghargaan yang pernah diraih dinas — tampil sebagai slider cuplikan di Dashboard.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-200 bg-white text-slate-700 text-xs font-bold px-3 py-2 hover:bg-slate-50 transition">← Lihat di Dashboard</a>
        <button data-modal-open="modal-prestasi" class="rounded-lg bg-teal-600 hover:bg-teal-700 active:scale-95 text-white text-sm font-bold px-3.5 py-2 transition inline-flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4" /> Tambah Prestasi</button>
    </div>
</div>

<!-- Info Rotasi & Pengaturan Tampilan Dashboard -->
<div class="rounded-xl border border-amber-200 bg-gradient-to-r from-amber-50/70 via-white to-amber-50/40 p-4 mb-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="text-2xl select-none">💡</span>
            <div>
                <h4 class="text-sm font-bold text-slate-800">Mekanisme Tampilan di Dashboard</h4>
                <p class="text-xs text-slate-600 mt-0.5 max-w-3xl">
                    Dashboard menampilkan <strong>10 prestasi</strong>. Secara otomatis, sistem akan <strong>merotasi penghargaan yang tampil setiap hari</strong>.
                    Jika Anda ingin penghargaan tertentu <em>pasti tampil hari ini</em>, klik tombol <strong>⭐ Jadikan Pilihan</strong> pada tabel di bawah.
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @if ($featuredCount > 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            ⭐ {{ $featuredCount }} prestasi dipilih manual
                        </span>
                        <span class="text-xs text-slate-500">
                            + {{ max(0, 10 - $featuredCount) }} slot lainnya diisi otomatis oleh rotasi harian
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                            🔄 Mode Penuh: 10 prestasi berotasi otomatis setiap hari
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
                <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold px-3 py-1.5 transition active:scale-95 shadow-sm">
                    🔄 Reset ke Rotasi Penuh
                </button>
            </form>
        @endif
    </div>
</div>

<x-import-card :action="route('admin.prestasi.import')" title="Import Daftar Prestasi (Excel/CSV)"
    desc="Format file daftar penghargaan dinas: blok diawali baris «TAHUN 20XX», lalu header kolom No | Nama Penghargaan | Nama Penerima | Pemberi Penghargaan | Tahun Penghargaan | Tingkat (opsional), lalu baris data bernomor. Data tahun yang sama diganti dengan isi file terbaru." />

<div class="card mt-4 overflow-hidden">
    <table class="data w-full">
        <thead>
            <tr class="text-left">
                <th>Nama Penghargaan</th>
                <th>Penerima</th>
                <th>Pemberi</th>
                <th class="w-16 text-center">Tahun</th>
                <th class="w-24">Tingkat</th>
                <th class="w-44 text-center">Tampil Dashboard</th>
                <th class="w-24"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $p)
            <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                <td class="font-medium text-[13px] leading-snug">
                    {{ $p->nama_penghargaan }}
                    @if ($p->is_featured)
                        <span class="ml-1 inline-block text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">PILIHAN HARI INI</span>
                    @endif
                </td>
                <td class="text-[13px] text-slate-500">{{ $p->nama_penerima ?: '—' }}</td>
                <td class="text-[13px] text-slate-500">{{ $p->pemberi_penghargaan ?: '—' }}</td>
                <td class="text-[13px] text-center font-bold">{{ $p->tahun }}</td>
                <td class="text-[13px]">{{ $p->tingkat ?: '—' }}</td>
                <td class="text-center">
                    <form method="POST" action="{{ route('admin.prestasi.toggleFeatured', $p) }}">
                        @csrf
                        @if ($p->is_featured)
                            <button type="submit" title="Klik untuk membatalkan pilihan ini" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 transition active:scale-95 shadow-sm">
                                ⭐ Tampil Hari Ini
                            </button>
                        @else
                            <button type="submit" title="Klik untuk memprioritaskan tampil di dashboard hari ini" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 hover:text-slate-800 border border-slate-200 transition active:scale-95">
                                ☆ Jadikan Pilihan
                            </button>
                        @endif
                    </form>
                </td>
                <td class="text-right pr-3 whitespace-nowrap">
                    <button data-modal-open="edit-prestasi-{{ $p->id }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600 transition">Edit</button>
                    <form method="POST" action="{{ route('admin.prestasi.destroy', $p) }}" onsubmit="return confirm('Hapus data prestasi ini?')" class="inline">
                        @csrf @method('DELETE')
                        <button class="text-[11px] font-bold text-rose-500 hover:underline">Hapus</button>
                    </form>
                </td>
            </tr>

            {{-- modal edit prestasi --}}
            <div id="edit-prestasi-{{ $p->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
                <div class="card w-full max-w-lg p-6 text-sm">
                    <div class="flex justify-between items-center mb-3"><h3 class="font-bold text-[15px]">Edit Prestasi #{{ $p->id }}</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
                    @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
                    <form method="POST" action="{{ route('admin.prestasi.update', $p) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @csrf @method('PATCH')
                        <div class="col-span-2"><label class="text-xs text-slate-500">Nama penghargaan *</label><input name="nama_penghargaan" value="{{ $p->nama_penghargaan }}" required maxlength="500" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
                        <div class="col-span-2"><label class="text-xs text-slate-500">Nama penerima</label><input name="nama_penerima" value="{{ $p->nama_penerima }}" maxlength="255" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
                        <div class="col-span-2"><label class="text-xs text-slate-500">Pemberi penghargaan</label><input name="pemberi_penghargaan" value="{{ $p->pemberi_penghargaan }}" maxlength="255" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
                        <label class="text-xs text-slate-500">Tahun *<input type="number" name="tahun" value="{{ $p->tahun }}" min="1900" max="2100" required class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                        <label class="text-xs text-slate-500">Tingkat (Kab/Kota, Provinsi…)<input name="tingkat" value="{{ $p->tingkat }}" maxlength="50" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
                        <button class="col-span-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        @empty
            <tr><td colspan="7" class="text-center py-6 text-slate-400">Belum ada data prestasi — tambah manual lewat tombol «Tambah Prestasi» di kanan atas, atau import file di atas.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if ($items->hasPages())<div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>@endif
</div>

{{-- modal tambah prestasi manual --}}
<div id="modal-prestasi" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-lg p-6 text-sm">
        <div class="flex justify-between items-center mb-3"><h3 class="font-bold text-[15px]">Tambah Prestasi</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.prestasi.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            @csrf
            <div class="col-span-2"><label class="text-xs text-slate-500">Nama penghargaan *</label><input name="nama_penghargaan" placeholder="mis. Pemenang ADIPURA" required maxlength="500" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <div class="col-span-2"><label class="text-xs text-slate-500">Nama penerima</label><input name="nama_penerima" placeholder="nama pegawai / bidang / dinas" maxlength="255" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <div class="col-span-2"><label class="text-xs text-slate-500">Pemberi penghargaan</label><input name="pemberi_penghargaan" placeholder="mis. Gubernur Jawa Barat" maxlength="255" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <label class="text-xs text-slate-500">Tahun *<input type="number" name="tahun" value="{{ now()->year }}" min="1900" max="2100" required class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="text-xs text-slate-500">Tingkat (Kab/Kota, Provinsi…)<input name="tingkat" maxlength="50" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <button class="col-span-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan Prestasi</button>
        </form>
    </div>
</div>
@endsection
