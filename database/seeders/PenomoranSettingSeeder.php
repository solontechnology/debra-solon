<?php

namespace Database\Seeders;

use App\Models\PenomoranSetting;
use Illuminate\Database\Seeder;

class PenomoranSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'notaris' => [
                'reset_period' => 'month',
                'mode' => 'automatic',
            ],

            'ppat' => [
                'reset_period' => 'year',
                'mode' => 'automatic',
            ],

            'waarmerking' => [
                'reset_period' => 'year',
                'mode' => 'automatic',
            ],

            'surat-keluar' => [
                'reset_period' => 'year',
                'mode' => 'automatic',
            ],

            'legalisasi' => [
                'reset_period' => 'year',
                'mode' => 'automatic',
            ],

            'wasiat' => [
                'reset_period' => 'month',
                'mode' => 'automatic',
            ],

            'covernot' => [
                'reset_period' => 'year',
                'mode' => 'automatic',
            ],
        ];

        foreach ($settings as $kategori => $data) {
            PenomoranSetting::updateOrCreate(
                ['kategori' => $kategori],
                $data
            );
        }
    }
}