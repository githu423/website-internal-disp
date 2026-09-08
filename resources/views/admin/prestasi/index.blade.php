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

<div class="card p-4 mb-4">
    <form method="POST" action="{{ route('admin.prestasi.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2.5">
        @csrf
        <input type="file" name="file" required class="text-sm">
        <button class="rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold px-4 py-2 active:scale-95 transition">Import Berkas</button>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data w-full">
        <thead>
            <tr class="text-left">
                <th>Nama Penghargaan</th>
                <th>Penerima</th>
                <th>Pemberi</th>
                <th class="w-16 text-center">Tahun</th>
                <th class="w-24">Tingkat</th>
                <th class="w-44 text-center">Tampil Dashboard</th>
                <th class="w-16"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $p)
            <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                <td class="font-medium text-[13px] leading-snug">
                    {{ $p->nama_penghargaan }}
                    @if ($p->is_featured)
                        <span class="ml-1 inline-block text-[10px] font-extrabold px-1.5 py-0.2 rounded bg-amber-100 text-amber-800">PILIHAN HARI INI</span>
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
                <td class="text-right pr-3">
                    <form method="POST" action="{{ route('admin.prestasi.destroy', $p) }}" onsubmit="return confirm('Hapus data prestasi ini?')">
                        @csrf @method('DELETE')
                        <button class="text-[11px] font-bold text-rose-500 hover:underline">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-6 text-slate-400">Belum ada data prestasi.</td></tr>
        @endforelse
        </tbody>
    </table>
    @if ($items->hasPages())<div class="px-4 py-3 border-t border-slate-100">{{ $items->links() }}</div>@endif
</div>
@endsection
