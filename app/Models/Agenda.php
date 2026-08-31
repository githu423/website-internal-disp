<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $table = 'agenda';
    protected $fillable = ['judul', 'deskripsi', 'tanggal', 'jam_mulai', 'jam_selesai',
        'lokasi', 'kategori', 'status', 'id_bidang', 'created_by'];
    protected $casts = ['tanggal' => 'date'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }

    public function scopeTerdekat($q)
    {
        return $q->where('status', 'belum')->whereDate('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')->orderBy('jam_mulai');
    }

    public function scopeTerlewat($q)
    {
        return $q->where('status', 'belum')->whereDate('tanggal', '<', now()->toDateString())
            ->orderByDesc('tanggal');
    }

    public function getStatusLamaAttribute(): string
    {
        if ($this->status !== 'belum') return '—';
        $d = now()->startOfDay()->diffInDays($this->tanggal, false);
        return $d > 0 ? "$d hari lagi" : ($d == 0 ? 'hari ini' : abs($d).' hari lalu (terlewat)');
    }
}
