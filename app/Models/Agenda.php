<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Agenda dinas / bidang.
 *
 * Status: belum, selesai, dibatalkan.
 */
class Agenda extends Model
{
    protected $table = 'agenda';
    protected $fillable = ['judul', 'deskripsi', 'tanggal', 'jam_mulai', 'jam_selesai',
        'lokasi', 'kategori', 'status', 'id_bidang', 'created_by'];
    protected $casts = ['tanggal' => 'date'];

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // ===== SCOPE: AGENDA TERDEKAT (STATUS BELUM, TANGGAL >= HARI INI) =====
    public function scopeTerdekat($query)
    {
        return $query->where('status', 'belum')->whereDate('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')->orderBy('jam_mulai');
    }

    // ===== SCOPE: AGENDA TERLEWAT (STATUS BELUM, TANGGAL < HARI INI) =====
    public function scopeTerlewat($query)
    {
        return $query->where('status', 'belum')->whereDate('tanggal', '<', now()->toDateString())
            ->orderByDesc('tanggal');
    }

    // ===== LABEL "x hari lagi / hari ini / terlewat" =====
    public function getStatusLamaAttribute(): string
    {
        if ($this->status !== 'belum') {
            return '—';
        }
        $selisihHari = now()->startOfDay()->diffInDays($this->tanggal, false);

        return $selisihHari > 0 ? "$selisihHari hari lagi" : ($selisihHari == 0 ? 'hari ini' : abs($selisihHari).' hari lalu (terlewat)');
    }
}
