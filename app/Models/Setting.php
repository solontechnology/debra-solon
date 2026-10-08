<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function namaNotaris(): string
    {
        return Cache::rememberForever(
            'setting_perusahaan_nama_notaris',
            fn () => static::query()->value('nama_perusahaan') ?: 'Notaris'
        );
    }

    public function skNotaris()
    {
        return $this->hasOne(skNotaris::class,'perusahaan_id');
    }

    public function skPpat()
    {
        return $this->hasOne(skPpat::class, 'perusahaan_id');
    }
}
