<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PadController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatistikController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/kartu', [DashboardController::class, 'kartu'])->name('dashboard.kartu');
    Route::get('/search', [SearchController::class, 'index'])->name('search');

    // modul publik
    Route::get('/dokumen', [DokumenController::class, 'index'])->name('dokumen.index');
    Route::post('/dokumen', [DokumenController::class, 'store'])->name('dokumen.store');
    Route::get('/dokumen/{dokumen}/unduh', [DokumenController::class, 'show'])->name('dokumen.unduh');
    Route::get('/dokumen/{dokumen}/lihat', [DokumenController::class, 'lihat'])->name('dokumen.lihat');
    Route::get('/dokumen/{dokumen}', [DokumenController::class, 'buka'])->name('dokumen.show');
    Route::patch('/dokumen/{dokumen}/akses', [DokumenController::class, 'ubahAkses'])->name('dokumen.akses');
    Route::delete('/dokumen/{dokumen}', [DokumenController::class, 'destroy'])->name('dokumen.destroy');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::post('/agenda', [AgendaController::class, 'store'])->name('agenda.store');
    Route::patch('/agenda/{agenda}/status', [AgendaController::class, 'selesai'])->name('agenda.status');
    Route::delete('/agenda/{agenda}', [AgendaController::class, 'destroy'])->name('agenda.destroy');

    Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::get('/kinerja', [StatistikController::class, 'kinerja'])->name('kinerja.publik');
    Route::get('/kinerja/capaian', [StatistikController::class, 'capaian'])->name('kinerja.capaian');
    Route::get('/statistik', [StatistikController::class, 'index'])->name('statistik.index');
    Route::get('/statistik/data', [StatistikController::class, 'data'])->name('statistik.data');
    Route::get('/pad', [PadController::class, 'index'])->name('pad.index');

    // profil pribadi: SEMUA user tanpa terkecuali boleh melihat & mengisi datanya sendiri
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::patch('/profil', [ProfilController::class, 'simpan'])->name('profil.simpan');
    Route::post('/profil/ganti-password', [ProfilController::class, 'gantiPassword'])->name('profil.ganti-password');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/unread', [NotifikasiController::class, 'unread'])->name('notifikasi.unread');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.bacaSemua');

    // modul bidang: profil dilihat semua user; kelola hanya anggota bidangnya / admin
    Route::get('/bidang', [BidangController::class, 'index'])->name('bidang.index');
    Route::get('/bidang/{bidang}', [BidangController::class, 'show'])->name('bidang.show');
    Route::patch('/bidang/{bidang}', [BidangController::class, 'update'])->name('bidang.update');
    Route::post('/bidang/{bidang}/dokumen', [BidangController::class, 'unggahDokumen'])->name('bidang.dokumen');
    Route::post('/bidang/{bidang}/agenda', [BidangController::class, 'tambahAgenda'])->name('bidang.agenda');

    // ===== ADMIN =====
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('akun', [Admin\AkunController::class, 'index'])->name('akun.index');
        Route::post('akun', [Admin\AkunController::class, 'store'])->name('akun.store');
        Route::patch('akun/{akun}', [Admin\AkunController::class, 'update'])->name('akun.update');
        Route::delete('akun/{akun}', [Admin\AkunController::class, 'destroy'])->name('akun.destroy');
        Route::post('akun/sinkron-peran', [Admin\AkunController::class, 'sinkronPeran'])->name('akun.sinkron');

        Route::get('pegawai', [Admin\PegawaiController::class, 'index'])->name('pegawai.index');
        Route::post('pegawai', [Admin\PegawaiController::class, 'store'])->name('pegawai.store');
        Route::patch('pegawai/{pegawai}', [Admin\PegawaiController::class, 'update'])->name('pegawai.update');
        Route::delete('pegawai/{pegawai}', [Admin\PegawaiController::class, 'destroy'])->name('pegawai.destroy');
        Route::post('pegawai/import', [Admin\PegawaiController::class, 'import'])->name('pegawai.import');

        Route::get('dokumen', [Admin\DokumenController::class, 'index'])->name('dokumen.index');
        Route::patch('dokumen/{dokumen}', [Admin\DokumenController::class, 'update'])->name('dokumen.update');
        Route::delete('dokumen/{dokumen}', [Admin\DokumenController::class, 'destroy'])->name('dokumen.destroy');

        Route::get('agenda', [Admin\AgendaController::class, 'index'])->name('agenda.index');
        Route::post('agenda', [Admin\AgendaController::class, 'store'])->name('agenda.store');
        Route::patch('agenda/{agenda}', [Admin\AgendaController::class, 'update'])->name('agenda.update');
        Route::delete('agenda/{agenda}', [Admin\AgendaController::class, 'destroy'])->name('agenda.destroy');

        Route::get('kinerja', [Admin\KinerjaController::class, 'index'])->name('kinerja.index');
        Route::post('kinerja', [Admin\KinerjaController::class, 'store'])->name('kinerja.store');
        Route::patch('kinerja/{kinerja}', [Admin\KinerjaController::class, 'update'])->name('kinerja.update');
        Route::delete('kinerja/{kinerja}', [Admin\KinerjaController::class, 'destroy'])->name('kinerja.destroy');
        Route::post('kinerja/import', [Admin\KinerjaController::class, 'import'])->name('kinerja.import');

        Route::get('capaian-kinerja', [Admin\CapaianKinerjaController::class, 'index'])->name('capaian.index');
        Route::post('capaian-kinerja/import', [Admin\CapaianKinerjaController::class, 'import'])->name('capaian.import');

        // statistik: format file "Data Statistik Pariwisata" (indikator x tahun) — v17
        Route::get('statistik', [Admin\StatistikController::class, 'index'])->name('statistik.index');
        Route::post('statistik', [Admin\StatistikController::class, 'store'])->name('statistik.store');
        Route::post('statistik/import', [Admin\StatistikController::class, 'import'])->name('statistik.import');
        Route::delete('statistik/{statistik}', [Admin\StatistikController::class, 'destroy'])->name('statistik.destroy');
        Route::post('capaian-kinerja/sasaran', [Admin\CapaianKinerjaController::class, 'storeSasaran'])->name('capaian.sasaran.store');
        Route::patch('capaian-kinerja/sasaran/{sasaran}', [Admin\CapaianKinerjaController::class, 'updateSasaran'])->name('capaian.sasaran.update');
        Route::delete('capaian-kinerja/sasaran/{sasaran}', [Admin\CapaianKinerjaController::class, 'destroySasaran'])->name('capaian.sasaran.destroy');
        Route::post('capaian-kinerja/indikator', [Admin\CapaianKinerjaController::class, 'storeIndikator'])->name('capaian.indikator.store');
        Route::patch('capaian-kinerja/indikator/{indikator}', [Admin\CapaianKinerjaController::class, 'updateIndikator'])->name('capaian.indikator.update');
        Route::delete('capaian-kinerja/indikator/{indikator}', [Admin\CapaianKinerjaController::class, 'destroyIndikator'])->name('capaian.indikator.destroy');
        Route::post('capaian-kinerja/indikator/{indikator}/nilai', [Admin\CapaianKinerjaController::class, 'simpanNilai'])->name('capaian.nilai');

        Route::get('pad', [Admin\PadController::class, 'index'])->name('pad.index');
        Route::post('pad', [Admin\PadController::class, 'store'])->name('pad.store');
        Route::patch('pad/{pad}', [Admin\PadController::class, 'update'])->name('pad.update');
        Route::delete('pad/{pad}', [Admin\PadController::class, 'destroy'])->name('pad.destroy');
        Route::post('pad/import', [Admin\PadController::class, 'import'])->name('pad.import');

        Route::get('prestasi', [Admin\PrestasiController::class, 'index'])->name('prestasi.index');
        Route::post('prestasi', [Admin\PrestasiController::class, 'store'])->name('prestasi.store');
        Route::patch('prestasi/{prestasi}', [Admin\PrestasiController::class, 'update'])->name('prestasi.update');
        Route::post('prestasi/import', [Admin\PrestasiController::class, 'import'])->name('prestasi.import');
        Route::post('prestasi/{prestasi}/toggle-featured', [Admin\PrestasiController::class, 'toggleFeatured'])->name('prestasi.toggleFeatured');
        Route::post('prestasi/reset-featured', [Admin\PrestasiController::class, 'resetFeatured'])->name('prestasi.resetFeatured');
        Route::delete('prestasi/{prestasi}', [Admin\PrestasiController::class, 'destroy'])->name('prestasi.destroy');

        Route::get('log', [Admin\LogController::class, 'index'])->name('log.index');
    });
});

require __DIR__ . '/auth.php';
