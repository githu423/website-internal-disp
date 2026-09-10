@extends('layouts.dashboard')
@section('title', 'Kelola Pegawai')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h2 class="text-lg font-extrabold">Data Pegawai <span class="text-xs font-bold text-slate-400 bg-slate-100 rounded-full px-2 py-1 align-middle">{{ $total }} orang</span>@if ($belumLengkap > 0 || request('belum'))
        <a href="{{ request()->fullUrlWithQuery(['belum' => request('belum') ? null : 1]) }}" class="ml-1.5 align-middle inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10.5px] font-extrabold transition {{ request('belum') ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' }}">⚠ {{ $belumLengkap }} belum · ✓ {{ $lengkap }} lengkap — {{ request('belum') ? 'reset filter' : 'saring' }}</a>@endif</h2>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex gap-2">
            @if (request('belum'))<input type="hidden" name="belum" value="1">@endif
            <select name="bidang" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm">
                <option value="">Semua bidang</option>
                @foreach ($bidangs as $b)<option value="{{ $b->id }}" @selected(request('bidang') == $b->id)>{{ $b->nama }}</option>@endforeach
            </select>
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama/NIP… atau: belum lengkap" class="w-52 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500">
        </form>
        <button data-modal-open="modal-pegawai" class="rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold px-3.5 py-2 transition inline-flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4" /> Tambah</button>
    </div>
</div>

{{-- 10 Sep 2026: pesan error validasi kini SELALU terlihat (dulu tersembunyi di dalam modal tambah) --}}
@if ($errors->any())
<div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-[13px]">
    <b>⚠ Gagal menyimpan:</b> {{ $errors->first() }}<br>
    <span class="text-rose-500">Perbaiki kolom yang dimaksud lalu tekan Simpan lagi — kolom lain boleh dibiarkan kosong.</span>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[700px]">
                <thead><tr><th>Nama / NIP</th><th>Jabatan</th><th>Bidang</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($pegawai as $p)
                    <tr>
                        <td><p class="font-semibold text-[13px]">{{ $p->nama }}</p><p class="text-[11px] text-slate-400 font-mono">{{ $p->nip }}</p>@if ($modeBelum)<p class="mt-0.5 text-[10.5px] font-bold text-rose-600">belum lengkap: {{ implode(', ', $p->kolomKurang()) }}</p>@endif</td>
                        <td class="text-xs">{{ $p->jabatan ?? '—' }}<span class="block text-[10.5px] text-slate-400">{{ $p->pangkat_golongan }} · {{ $p->masa_kerja['label'] }}</span></td>
                        <td class="text-xs text-slate-500">{{ $p->bidang?->nama ?? '—' }}</td>
                        <td><span class="text-[10.5px] font-bold rounded-full px-2 py-0.5 {{ $p->status === 'aktif' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            <button data-modal-open="pg-{{ $p->id }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600">Edit</button>
                            <form method="POST" action="{{ route('admin.pegawai.destroy', $p) }}" data-confirm="Hapus {{ $p->nama }}?" class="inline">@csrf @method('DELETE')
                                <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <div id="pg-{{ $p->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
                        <div class="card w-full max-w-lg p-6 text-sm">
                            <div class="flex justify-between mb-2"><h3 class="font-bold">Edit {{ $p->nama }}</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
                            <p class="mb-3 text-[11.5px] text-slate-600 bg-teal-50 border border-teal-100 rounded-lg px-3 py-2">✏️ Cukup ubah kolom yang diperlukan lalu tekan <b>Simpan</b> — kolom lain boleh dibiarkan kosong. Nama &amp; NIP wajib terisi.</p>
                            <form method="POST" action="{{ route('admin.pegawai.update', $p) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                @csrf @method('PATCH')
                                <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Nama lengkap <span class="text-rose-500">*</span></span>
                                    <input name="nama" value="{{ $p->nama }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">NIP <span class="text-rose-500">*</span></span>
                                    <input name="nip" value="{{ $p->nip }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Status kepegawaian</span>
                                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white">@foreach (['aktif','nonaktif','pensiun'] as $s)<option @selected($p->status === $s)>{{ $s }}</option>@endforeach</select></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jenis ASN</span>
                                    <select name="jenis_asn" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white"><option value="">— belum diketahui —</option>@foreach (['PNS','PPPK'] as $j)<option @selected($p->jenis_asn === $j)>{{ $j }}</option>@endforeach</select></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jenis kelamin</span>
                                    <select name="jenis_kelamin" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white"><option value="">— belum diketahui —</option>@foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $k => $v)<option value="{{ $k }}" @selected($p->jenis_kelamin === $k)>{{ $v }}</option>@endforeach</select></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tempat lahir</span>
                                    <input name="tempat_lahir" value="{{ $p->tempat_lahir }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tanggal lahir</span>
                                    <input type="date" name="tanggal_lahir" value="{{ $p->tanggal_lahir?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pangkat/gol. awal</span>
                                    <input name="pangkat_awal" value="{{ $p->pangkat_awal }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="mis. Pengatur Muda (II/a)"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT CPNS <span class="normal-case font-medium text-slate-400">(awal pengangkatan)</span></span>
                                    <input type="date" name="tmt_cpns" value="{{ $p->tmt_cpns?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pangkat/gol. terakhir</span>
                                    <input name="pangkat_golongan" value="{{ $p->pangkat_golongan }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="mis. Penata (III/c)"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT pangkat sekarang</span>
                                    <input type="date" name="tmt_sekarang" value="{{ $p->tmt_sekarang?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jabatan <span class="normal-case font-medium text-slate-400">(tulis persis sesuai SK)</span></span>
                                    <input name="jabatan" value="{{ $p->jabatan }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="mis. Kepala Sub Bagian Umum dan Kepegawaian Dispar Pada …"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT jabatan</span>
                                    <input type="date" name="tmt_jabatan" value="{{ $p->tmt_jabatan?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pendidikan terakhir</span>
                                    <input name="pendidikan" value="{{ $p->pendidikan }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="mis. S-1"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Unit kerja <span class="normal-case font-medium text-slate-400">(sesuai file Excel)</span></span>
                                    <input name="unit_kerja" value="{{ $p->unit_kerja }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Penempatan bidang <span class="normal-case font-medium text-slate-400">(kelompok kerja — menentukan "Bidang Saya" &amp; agenda bidang)</span></span>
                                    <select name="id_bidang" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white"><option value="">— belum ditempatkan —</option>@foreach ($bidangs as $b)<option value="{{ $b->id }}" @selected($p->id_bidang == $b->id)>{{ $b->nama }}</option>@endforeach</select></label>
                                <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat struktural</span>
                                    <input name="diklat_struktural" value="{{ $p->diklat_struktural }}" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="mis. Diklat PIM Tk. III 2021"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat fungsional</span>
                                    <input name="diklat_fungsional" value="{{ $p->diklat_fungsional }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat teknis</span>
                                    <input name="diklat_teknis" value="{{ $p->diklat_teknis }}" class="w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                <button class="sm:col-span-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5">Simpan</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="5"><x-empty icon="pegawai" title="Tidak ada data" desc="Import file daftar pegawai atau tambah manual." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($pegawai->hasPages())<div class="p-3">{{ $pegawai->links() }}</div>@endif
    </div>

    <div class="space-y-4">
        <x-import-card :action="route('admin.pegawai.import')" title="Import daftar pegawai (Excel/CSV)"
            desc="Format: kolom No., NAMA LENGKAP, N I P, tempat/tanggal lahir, pangkat, TMT CPNS, jabatan, dst. Data lama dengan NIP sama diperbarui; pegawai baru otomatis dapat akun akses (password default dinas)." />
    </div>
</div>

<div id="modal-pegawai" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-lg p-6 text-sm">
        <div class="flex justify-between mb-2"><h3 class="font-bold">Tambah Pegawai Manual</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
        <p class="mb-3 text-[11.5px] text-slate-600 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">Wajib hanya <b>Nama</b> &amp; <b>NIP</b> — kolom lain boleh diisi nanti lewat tombol Edit.</p>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.pegawai.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            @csrf
            <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Nama lengkap <span class="text-rose-500">*</span></span>
                <input name="nama" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">NIP <span class="text-rose-500">*</span></span>
                <input name="nip" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Status kepegawaian</span>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 bg-white">@foreach (['aktif','nonaktif','pensiun'] as $s)<option>{{ $s }}</option>@endforeach</select></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jenis ASN</span>
                <select name="jenis_asn" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">— belum diketahui —</option><option>PNS</option><option>PPPK</option></select></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jenis kelamin</span>
                <select name="jenis_kelamin" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">— belum diketahui —</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tempat lahir</span>
                <input name="tempat_lahir" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Tanggal lahir</span>
                <input type="date" name="tanggal_lahir" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pangkat/gol. awal</span>
                <input name="pangkat_awal" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="mis. Pengatur Muda (II/a)"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT CPNS <span class="normal-case font-medium text-slate-400">(awal pengangkatan)</span></span>
                <input type="date" name="tmt_cpns" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pangkat/gol. terakhir</span>
                <input name="pangkat_golongan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="mis. Penata (III/c)"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT pangkat sekarang</span>
                <input type="date" name="tmt_sekarang" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Jabatan <span class="normal-case font-medium text-slate-400">(tulis persis sesuai SK)</span></span>
                <input name="jabatan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="mis. Pengadministrasi Umum Pada Dinas Pariwisata"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">TMT jabatan</span>
                <input type="date" name="tmt_jabatan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Pendidikan terakhir</span>
                <input name="pendidikan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="mis. S-1"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Unit kerja <span class="normal-case font-medium text-slate-400">(sesuai file Excel)</span></span>
                <input name="unit_kerja" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Penempatan bidang <span class="normal-case font-medium text-slate-400">(kelompok kerja — menentukan "Bidang Saya" &amp; agenda bidang)</span></span>
                <select name="id_bidang" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">— belum ditempatkan —</option>@foreach ($bidangs as $b)<option value="{{ $b->id }}">{{ $b->nama }}</option>@endforeach</select></label>
            <label class="block sm:col-span-2"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat struktural</span>
                <input name="diklat_struktural" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" placeholder="mis. Diklat PIM Tk. III 2021"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat fungsional</span>
                <input name="diklat_fungsional" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <label class="block"><span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Diklat teknis</span>
                <input name="diklat_teknis" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
            <button class="sm:col-span-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan</button>
        </form>
    </div>
</div>
@endsection