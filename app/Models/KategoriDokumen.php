<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kategori dokumen bertingkat (induk → anak).
 */
class KategoriDokumen extends Model
{
    protected $table = 'kategori_dokumen';
    protected $fillable = ['nama', 'induk_id'];

    // ===== RELASI =====
    public function induk()
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    public function anak()
    {
        return $this->hasMany(self::class, 'induk_id')->orderBy('nama');
    }

    public function dokumen()
    {
        return $this->hasMany(Dokumen::class, 'id_kategori');
    }

    // ===== JEJAK KATEGORI =====
    /** Jejak nama kategori dari akar sampai ke kategori ini (untuk tampilan "Induk / Anak"). */
    public function jejak(): array
    {
        $jejak = [$this->nama];
        $node = $this->induk;
        while ($node) {
            $jejak[] = $node->nama;
            $node = $node->induk;
        }

        return array_reverse($jejak);
    }
}
