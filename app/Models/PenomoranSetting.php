<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenomoranSetting extends Model
{
    protected $guarded = [];

    public static function kategori()
    {
        return [
            'notaris' => 'Notaris',
            'ppat' => 'PPAT',
            'waarmerking' => 'Waarmerking',
            'surat-keluar' => 'Surat Keluar',
            'legalisasi' => 'Legalisasi',
            'wasiat' => 'Wasiat',
            'covernot' => 'Covernot',
        ];
    }
}