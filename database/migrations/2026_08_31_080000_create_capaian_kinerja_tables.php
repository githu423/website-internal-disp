<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Matriks Capaian Kinerja (target vs realisasi per indikator per tahun) — sumber: CAPAIAN KINERJA.csv
        Schema::create('capaian_sasaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('nomor')->default(1);
            $table->string('judul', 500);
            $table->string('cakupan', 160)->nullable();   // mis. "2020" dari catatan "Sasaran Tahun : 2020"
            $table->unsignedInteger('urut')->default(0);
        });

        Schema::create('capaian_indikator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sasaran_id')->constrained('capaian_sasaran')->cascadeOnDelete();
            $table->string('nama', 500);
            $table->string('satuan', 80)->nullable();
            $table->unsignedInteger('urut')->default(0);
        });

        Schema::create('capaian_tahunan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_id')->constrained('capaian_indikator')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('target', 120)->nullable();     // disimpan apa adanya (bisa angka, huruf, atau nilai ujian)
            $table->string('realisasi', 120)->nullable();
            $table->string('persen', 24)->nullable();       // mis. "84.36%" atau ">100"
            $table->unique(['indikator_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_tahunan');
        Schema::dropIfExists('capaian_indikator');
        Schema::dropIfExists('capaian_sasaran');
    }
};
