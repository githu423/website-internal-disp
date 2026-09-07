<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatistikNilai extends Model
{
    public $timestamps = false;

    protected $table = 'statistik_nilai';

    protected $fillable = ['id_statistik', 'tahun', 'nilai'];

    protected $casts = ['nilai' => 'float', 'tahun' => 'integer'];
}
