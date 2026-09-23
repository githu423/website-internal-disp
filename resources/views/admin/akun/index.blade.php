@extends('layouts.dashboard')

@section('title', 'Akun & Akses')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Manajemen Pengguna, Hak Akses, dan Kredensial SIPARDIS')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Pengaturan</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Akun &amp; Hak Akses</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pengguna &amp; Hak Akses</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $users->total() }} Pengguna
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Kelola peran Administrator, Operator Bidang, dan akun pegawai dinas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <form method="POST" action="{{ route('admin.akun.sinkron') }}">
                @csrf
                <button type="submit" title="Selaraskan peran, bidang & status semua akun dengan jabatan/unit kerja & status di data pegawai"
                        class="cursor-pointer inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-purple-50 border border-purple-200 text-purple-700 text-sm font-semibold rounded-xl hover:bg-purple-100 active:scale-95 transition-all duration-150">
                    <span>⟳</span> Sinkronkan Peran
                </button>
            </form>
            <button data-modal-open="modal-akun" 
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <x-icon name="plus" class="h-4 w-4" />
                Buat Akun Baru
            </button>
        </div>
    </div>

    <!-- 3. Filter Toolbar -->
    <form method="GET" class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="relative flex-1">
                <input name="q" value="{{ request('q') }}" placeholder="Cari nama pengguna atau NIP login..." 
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                <span class="absolute left-3 top-2.5 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
            </div>

            <x-select name="role" :options="['' => 'Semua Peran (Role)', 'admin' => 'Admin (Super)', 'bidang' => 'Operator Bidang', 'pegawai' => 'Pegawai']" :value="request('role')" autoSubmit class="w-full sm:w-56" />
            <x-select name="status" :options="['' => 'Semua Status', 'aktif' => 'Aktif', 'nonaktif' => 'Non-Aktif', 'pensiun' => 'Pensiun']" :value="request('status')" autoSubmit class="w-full sm:w-40" />
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs md:text-sm rounded-xl shadow-sm transition">
                Saring Akun
            </button>
            @if(request('q') || request('role') || request('status'))
                <a href="{{ route('admin.akun.index') }}" class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- 4. Kontainer Tabel Data Akun -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[760px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="py-3 px-4">Pengguna &amp; NIP</th>
                        <th class="py-3 px-3">Peran Akses</th>
                        <th class="py-3 px-3">Bidang Tugas</th>
                        <th class="py-3 px-3">Jabatan</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($users as $usr)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-[13px] text-slate-900 leading-snug">{{ $usr->name }}</p>
                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">
                                {{ $usr->nip }}
                                @if ($usr->pegawai?->umur !== null) 
                                    <span class="font-sans font-medium text-slate-500" title="Umur terhitung dari data pegawai">· {{ $usr->pegawai->umur }} th</span>
                                @endif
                            </p>
                        </td>
                        <td class="py-3.5 px-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ ['admin' => 'bg-purple-100 text-purple-800 border border-purple-200', 'bidang' => 'bg-sky-50 text-sky-700 border border-sky-200'][$usr->role] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ ucfirst($usr->role) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-3">
                            @if($usr->bidang)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                    {{ $usr->bidang->nama }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 italic">Dinas (Semua)</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-xs text-slate-600">
                            {{ $usr->jabatan ?? '—' }}
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            @php
                                $stMap = [
                                    'aktif'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'nonaktif' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'pensiun'  => 'bg-slate-100 text-slate-600 border-slate-200',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $stMap[$usr->status] ?? 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                {{ ucfirst($usr->status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                <button data-modal-open="ak-{{ $usr->id }}" 
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition">
                                    Ubah
                                </button>
                                @if ($usr->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.akun.destroy', $usr) }}" data-confirm="Hapus akun login {{ $usr->nip }}?" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Ubah Akun --}}
                    <div id="ak-{{ $usr->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
                        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
                            <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                                <div>
                                    <h3 class="font-extrabold text-base text-slate-900">Ubah Akun Pengguna</h3>
                                    <p class="text-xs text-slate-400 mt-0.5">NIP: {{ $usr->nip }}</p>
                                </div>
                                <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
                            </div>
                            <form method="POST" action="{{ route('admin.akun.update', $usr) }}" class="space-y-3">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Lengkap</label>
                                    <input name="name" value="{{ $usr->name }}" required 
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Peran (Role)</label>
                                        <select name="role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                            @foreach (['admin','bidang','pegawai'] as $rl)
                                                <option value="{{ $rl }}" @selected($usr->role === $rl)>{{ ucfirst($rl) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status Akun</label>
                                        <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                            @foreach (['aktif','nonaktif','pensiun'] as $st)
                                                <option value="{{ $st }}" @selected($usr->status === $st)>{{ ucfirst($st) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Bidang Tugas</label>
                                    <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                        <option value="">Otomatis / Tanpa Bidang</option>
                                        @foreach ($bidangs as $b)
                                            <option value="{{ $b->id }}" @selected($usr->id_bidang == $b->id)>{{ $b->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Ganti Password</label>
                                    <input type="password" name="password" placeholder="Kosongkan bila tidak ingin diubah" 
                                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                </div>

                                <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                                    Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center">
                            <x-empty icon="pegawai" title="Pengguna tidak ditemukan" desc="Coba periksa kata kunci pencarian atau buat akun baru." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Modal Buat Akun Baru --}}
<div id="modal-akun" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Buat Akun Login Baru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Kredensial akses aplikasi SIPARDIS.</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('admin.akun.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Pengguna *</label>
                <input name="name" placeholder="Nama lengkap" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">NIP / Username Login *</label>
                <input name="nip" placeholder="19XXXXXXXXXXXXXX" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-mono focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Password Awal *</label>
                <input type="text" name="password" placeholder="Minimal 8 karakter" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Peran (Role)</label>
                    <select name="role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        <option value="">Otomatis dari Jabatan</option>
                        <option value="pegawai">Pegawai</option>
                        <option value="bidang">Operator Bidang</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Bidang Tugas</label>
                    <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        <option value="">Bidang...</option>
                        @foreach ($bidangs as $b)
                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Jabatan (Opsional)</label>
                <input name="jabatan" placeholder="mis. Staf Bidang Pemasaran" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status Awal</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                    <option value="aktif" selected>Aktif</option>
                    <option value="nonaktif">Non-Aktif</option>
                    <option value="pensiun">Pensiun</option>
                </select>
            </div>

            <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Buat Akun Pengguna
            </button>
        </form>
    </div>
</div>
@endsection
