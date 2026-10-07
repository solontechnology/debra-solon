<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotarisRekanan extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function nomorPpats()
    {
        return $this->hasMany(NomorPpat::class);
    }
}
