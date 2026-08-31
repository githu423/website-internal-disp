<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $table = 'dokumen';
    protected $fillable = ['judul', 'deskripsi', 'kategori', 'visibility', 'id_bidang',
        'file_path', 'file_name', 'file_size', 'mime', 'dibaca_count', 'uploaded_by'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    /** aturan visibilitas untuk user tertentu */
    public function bolehDilihat(?User $user): bool
    {
        if (!$user) return $this->visibility === 'publik';
        if ($user->isAdmin() || $user->id === $this->uploaded_by) return true;
        if ($this->visibility === 'publik') return true;
        if ($this->visibility === 'bidang') return true; // arsip lintas bidang: tampil universal, terlabel bidangnya
        return false; // rahasia: hanya uploader & admin
    }
}
