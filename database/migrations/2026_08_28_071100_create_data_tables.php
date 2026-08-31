<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kinerja fisik sedinas (program/kegiatan/sub kegiatan) — sumber: Kinerja Fisik Sedinas.csv
        Schema::create('capaian_program', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('level', 20); // program|kegiatan|sub_kegiatan
            $table->string('nama');
            $table->string('sumber_dana', 30)->nullable();
            $table->decimal('pagu_anggaran', 20, 0)->default(0);
            $table->string('indikator')->nullable();
            $table->decimal('target', 14, 2)->default(0);
            $table->string('satuan', 40)->nullable();
            $table->decimal('capaian', 14, 2)->default(0);
            $table->decimal('persentase', 7, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->foreignId('id_bidang')->nullable()->constrained('bidang')->nullOnDelete();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
            $table->index(['tahun', 'level']);
        });

        Schema::create('capaian_bulanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1..12
            $table->decimal('target', 14, 2)->default(0);
            $table->decimal('capaian', 14, 2)->default(0);
            $table->timestamps();
            $table->unique(['tahun', 'bulan']);
        });

        // PAD — sumber: PAD.csv (2024..2027)
        Schema::create('pad', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('kategori')->nullable(); // mis. Retribusi Rekreasi dan Olah Raga
            $table->string('no_rekening', 64)->nullable();
            $table->string('objek'); // mis. OW. Situ Lengkong
            $table->decimal('target_tahunan', 20, 0)->default(0);
            $table->decimal('target_perubahan', 20, 0)->default(0);
            $table->decimal('realisasi_bulan_lalu', 20, 0)->default(0); // Nov
            $table->decimal('realisasi_bulan_ini', 20, 0)->default(0);  // Des
            $table->decimal('kumulatif', 20, 0)->default(0);            // jumlah realisasi Jan-Des
            $table->decimal('persentase', 7, 2)->default(0);
            $table->decimal('sisa_target', 20, 0)->default(0);         // realisasi - target
            $table->timestamps();
            $table->index('tahun');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pad');
        Schema::dropIfExists('capaian_bulanan');
        Schema::dropIfExists('capaian_program');
    }
};
