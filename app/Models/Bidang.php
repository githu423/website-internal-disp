<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    protected $table = 'bidang';
    protected $fillable = ['kode', 'nama', 'ketua', 'deskripsi'];

    public function pegawais() { return $this->hasMany(Pegawai::class, 'id_bidang'); }
    public function users() { return $this->hasMany(User::class, 'id_bidang'); }
    public function dokumen() { return $this->hasMany(Dokumen::class, 'id_bidang'); }
    public function agenda() { return $this->hasMany(Agenda::class, 'id_bidang'); }
}
