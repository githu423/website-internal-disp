<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruang_rapat', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('tanggal_rapat')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('link_virtual')->nullable();
            $table->foreignId('id_bidang')->nullable()->constrained('bidang')->nullOnDelete();
            $table->string('pic_nama')->nullable();
            $table->string('pic_kontak')->nullable();
            $table->string('token', 64)->unique();
            $table->string('pin_hash')->nullable();
            $table->boolean('izinkan_unduh_default')->default(true);
            $table->date('berlaku_sampai');
            $table->string('status', 20)->default('aktif')->index();
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('dibuka_count')->default(0);
            $table->timestamps();
        });

        Schema::create('ruang_rapat_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ruang_rapat')->constrained('ruang_rapat')->cascadeOnDelete();
            $table->foreignId('id_dokumen')->constrained('dokumen')->cascadeOnDelete();
            $table->string('kelompok', 20);
            $table->boolean('izinkan_unduh')->nullable();
            $table->timestamps();
            $table->unique(['id_ruang_rapat', 'id_dokumen'], 'ruang_rapat_dokumen_unik');
            $table->index(['id_ruang_rapat', 'kelompok'], 'ruang_rapat_dokumen_kelompok_idx');
        });

        Schema::create('ruang_rapat_kehadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ruang_rapat')->constrained('ruang_rapat')->cascadeOnDelete();
            $table->string('nama');
            $table->string('instansi');
            $table->string('kontak');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruang_rapat_kehadiran');
        Schema::dropIfExists('ruang_rapat_dokumen');
        Schema::dropIfExists('ruang_rapat');
    }
};
