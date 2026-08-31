@extends('layouts.dashboard')
@section('title', 'Akun & Akses')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form method="GET" class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Cari nama/NIP…" class="w-56 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500">
        <select name="role" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm">
            <option value="">Semua peran</option>@foreach (['admin','bidang','pegawai'] as $rl)<option @selected(request('role') === $rl)>{{ $rl }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 active:scale-95 transition">Cari</button>
    </form>
    <button data-modal-open="modal-akun" class="rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold px-3.5 py-2 transition inline-flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4" /> Buat akun</button>
</div>
<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[760px]">
            <thead><tr><th>Nama / NIP</th><th>Peran</th><th>Bidang</th><th>Jabatan</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $usr)
                <tr>
                    <td><p class="font-semibold text-[13px]">{{ $usr->name }}</p><p class="text-[11px] text-slate-400 font-mono">{{ $usr->nip }}</p></td>
                    <td><span class="text-[10.5px] font-bold rounded-full px-2 py-0.5 {{ ['admin' => 'bg-amber-50 text-amber-700', 'bidang' => 'bg-sky-50 text-sky-700'][$usr->role] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($usr->role) }}</span></td>
                    <td class="text-xs text-slate-500">{{ $usr->bidang?->nama ?? '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $usr->jabatan ?? '—' }}</td>
                    <td><span class="text-[10.5px] font-bold rounded-full px-2 py-0.5 {{ $usr->status === 'aktif' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">{{ ucfirst($usr->status) }}</span></td>
                    <td class="text-right whitespace-nowrap">
                        <button data-modal-open="ak-{{ $usr->id }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600">Ubah</button>
                        @if ($usr->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.akun.destroy', $usr) }}" data-confirm="Hapus akun {{ $usr->nip }}?" class="inline">@csrf @method('DELETE')
                            <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600">Hapus</button>
                        </form>
                        @endif
                    </td>
                </tr>
                <div id="ak-{{ $usr->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
                    <div class="card w-full max-w-md p-6 text-sm">
                        <div class="flex justify-between mb-3"><h3 class="font-bold">Ubah akun — {{ $usr->nip }}</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
                        <form method="POST" action="{{ route('admin.akun.update', $usr) }}" class="space-y-2.5">
                            @csrf @method('PATCH')
                            <input name="name" value="{{ $usr->name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <select name="role" class="rounded-lg border border-slate-300 px-3 py-2 bg-white">@foreach (['admin','bidang','pegawai'] as $rl)<option @selected($usr->role === $rl)>{{ $rl }}</option>@endforeach</select>
                                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 bg-white">@foreach (['aktif','nonaktif'] as $st)<option @selected($usr->status === $st)>{{ $st }}</option>@endforeach</select>
                            </div>
                            <select name="id_bidang" class="w-full rounded-lg border border-slate-300 px-3 py-2 bg-white"><option value="">Tanpa bidang</option>@foreach ($bidangs as $b)<option value="{{ $b->id }}" @selected($usr->id_bidang == $b->id)>{{ $b->nama }}</option>@endforeach</select>
                            <input type="password" name="password" placeholder="Password baru (kosongkan jika tidak diganti)" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5">Simpan</button>
                        </form>
                    </div>
                </div>
            @endforeach
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())<div class="p-3">{{ $users->links() }}</div>@endif
</div>

<div id="modal-akun" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-md p-6 text-sm">
        <div class="flex justify-between mb-3"><h3 class="font-bold">Buat Akun Baru</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.akun.store') }}" class="space-y-2.5">
            @csrf
            <input name="name" placeholder="Nama *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <input name="nip" placeholder="NIP / username login *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <input type="text" name="password" placeholder="Password min. 8 karakter" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <select name="role" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option>pegawai</option><option>bidang</option><option>admin</option></select>
                <select name="id_bidang" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">Bidang…</option>@foreach ($bidangs as $b)<option value="{{ $b->id }}">{{ $b->nama }}</option>@endforeach</select>
            </div>
            <input name="jabatan" placeholder="Jabatan (opsional)" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Buat Akun</button>
        </form>
    </div>
</div>
@endsection
