<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 40)->unique();
            $table->string('nama');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable(); // L / P
            $table->string('jabatan')->nullable();
            $table->string('pangkat_golongan')->nullable();
            $table->string('jenis_asn', 10)->nullable(); // PNS/PPPK
            $table->date('tmt_cpns')->nullable();
            $table->date('tmt_sekarang')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('status', 20)->default('aktif');
            $table->foreignId('id_bidang')->nullable()->constrained('bidang')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('kategori')->nullable();
            $table->string('visibility', 20)->default('publik'); // publik|bidang|rahasia
            $table->foreignId('id_bidang')->nullable()->constrained('bidang')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('dibaca_count')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('agenda', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal');
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('kategori', 30)->nullable(); // rapat|kegiatan|kunjungan|lainnya
            $table->string('status', 20)->default('belum'); // belum|selesai|dibatalkan
            $table->foreignId('id_bidang')->nullable()->constrained('bidang')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('isi')->nullable();
            $table->string('tipe', 20)->default('info'); // info|penting|agenda|import
            $table->string('url')->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama')->nullable();
            $table->string('aksi');
            $table->string('keterangan')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('agenda');
        Schema::dropIfExists('dokumen');
        Schema::dropIfExists('pegawai');
    }
};
