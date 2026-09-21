<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $table = 'dokumen';
    protected $fillable = ['judul', 'deskripsi', 'kategori', 'id_kategori', 'visibility', 'id_bidang',
        'file_path', 'file_name', 'file_size', 'mime', 'dibaca_count', 'uploaded_by'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function kategoriRel() { return $this->belongsTo(KategoriDokumen::class, 'id_kategori'); }

    /** Label kategori berjenjang "Induk / Anak", fallback ke teks lama / 'Umum'. */
    public function getKategoriLabelAttribute(): string
    {
        if ($this->kategoriRel) {
            return implode(' / ', $this->kategoriRel->jejak());
        }

        return $this->kategori ?: 'Umum';
    }

    /** aturan visibilitas untuk user tertentu */
    public function bolehDilihat(?User $user): bool
    {
        if (!$user) return $this->visibility === 'publik';
        if ($user->isAdmin() || $user->id === $this->uploaded_by) return true;
        if ($this->visibility === 'publik') return true;
        if ($this->visibility === 'bidang') {
            if (!$this->id_bidang) return true; // arsip tanpa bidang (unggalan admin) tetap terbuka
            return (int) $user->id_bidang === (int) $this->id_bidang; // privat: hanya sebidang
        }
        return false; // rahasia: hanya uploader & admin
    }
}
