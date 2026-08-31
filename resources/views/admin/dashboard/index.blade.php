@extends('layouts.dashboard')
@section('title', 'Ringkasan Admin')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
    <x-stat label="Akun Pengguna" :value="$stats['users']" tone="sky" icon="akun" :href="route('admin.akun.index')" />
    <x-stat label="Data Pegawai" :value="$stats['pegawai']" tone="teal" icon="pegawai" :href="route('admin.pegawai.index')" />
    <x-stat label="Dokumen" :value="$stats['dokumen']" tone="violet" icon="dokumen" :href="route('admin.dokumen.index')" />
    <x-stat label="Agenda Aktif" :value="$stats['agenda']" tone="amber" icon="agenda" :href="route('admin.agenda.index')" />
    <x-stat label="Baris Kinerja" :value="$stats['kinerja']" tone="emerald" icon="kinerja" :href="route('admin.kinerja.index')" />
    <x-stat label="Objek PAD" :value="$stats['pad']" tone="rose" icon="pad" :href="route('admin.pad.index')" />
</div>
<div class="card overflow-hidden">
    <h3 class="font-bold px-5 pt-4 pb-3 border-b border-slate-100">Aktivitas Terakhir</h3>
    <table class="data w-full">
        <tbody>
        @forelse ($logs as $l)
            <tr><td class="whitespace-nowrap text-xs text-slate-400">{{ $l->created_at->diffForHumans() }}</td>
                <td class="font-semibold text-[13px]">{{ $l->nama }}</td>
                <td class="text-xs"><span class="rounded bg-slate-100 px-1.5 py-0.5 font-bold text-slate-600">{{ $l->aksi }}</span></td>
                <td class="text-xs text-slate-500">{{ $l->keterangan }}</td></tr>
        @empty
            <tr><td><x-empty icon="log" title="Belum ada aktivitas tercatat" /></td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-2.5 border-t border-slate-100 text-right"><a href="{{ route('admin.log.index') }}" class="text-xs font-bold text-teal-600 hover:underline">Log lengkap →</a></div>
</div>
@endsection
