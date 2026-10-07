<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembeli extends Model
{
    protected $guarded = [];
     protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function jobDivisiBadanUsahaPembeli()
    {
        return $this->hasOne(JobDivisiBadanUsahaPembeli::class, 'pembeli_id', 'id');
    }
    public function files()
    {
        return $this->morphMany(JobDivisiFile::class, 'fileable');
    }
}
