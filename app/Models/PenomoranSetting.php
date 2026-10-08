<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    public static function formatNomor(
        string $format,
        int $sequence,
        CarbonInterface $date,
        string $monthFormat = 'number',
        string $suffix = ''
    ): string {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return preg_replace_callback('/n+|m+|y+/', function ($match) use (
            $sequence,
            $date,
            $monthFormat,
            $romanMonths,
            $suffix
        ) {
            $token = $match[0];
            $length = strlen($token);

            return match ($token[0]) {
                'n' => str_pad((string) $sequence, $length, '0', STR_PAD_LEFT).$suffix,
                'm' => $monthFormat === 'roman'
                    ? $romanMonths[$date->month]
                    : str_pad((string) $date->month, $length, '0', STR_PAD_LEFT),
                'y' => $length === 2 ? $date->format('y') : $date->format('Y'),
            };
        }, $format);
    }
}
