<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuangRapatKehadiran extends Model
{
    protected $table = 'ruang_rapat_kehadiran';

    public $timestamps = false;

    protected $fillable = ['id_ruang_rapat', 'nama', 'instansi', 'kontak'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function ruangRapat()
    {
        return $this->belongsTo(RuangRapat::class, 'id_ruang_rapat');
    }
}
