<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    protected $guarded = [];

    public function pekerjaan()
{
    return $this->belongsTo(Pekerjaan::class, 'pekerjaan_id', 'id');
}
}
