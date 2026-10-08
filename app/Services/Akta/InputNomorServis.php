<?php

namespace App\Services\Akta;

use App\Models\NomorPpat;
use App\Models\PenomoranSetting;
use Carbon\Carbon;
use Exception;

class InputNomorServis
{
    public function resolveForSystem(string $kategori, string $tanggalNomor, ?string $manualNumber): string
    {
        $setting = PenomoranSetting::query()->where('kategori', $kategori)->first();
        if (!$setting) {
            throw new Exception("Pengaturan penomoran untuk kategori {$kategori} belum tersedia.");
        }

        if ($setting->mode !== 'manual') {
            return $this->execute($kategori, $tanggalNomor);
        }

        $nomor = trim((string) $manualNumber);
        if ($nomor === '') {
            throw new Exception('Nomor wajib diisi karena kategori ini menggunakan penomoran manual.');
        }

        $date = Carbon::parse($tanggalNomor);
        $duplicateQuery = NomorPpat::query()
            ->where('kategori', $kategori)
            ->where('nomor', $nomor)
            ->where('rekanan', 0);

        if ($setting->reset_period === 'month') {
            $duplicateQuery->whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year);
        } else {
            $duplicateQuery->whereYear('tanggal', $date->year);
        }

        if ($duplicateQuery->exists()) {
            throw new Exception("Nomor {$nomor} sudah digunakan pada periode tersebut.");
        }

        return $nomor;
    }

    public function execute(string $kategori, string $tanggal_nomor): string
    {
        $tanggalNomor = Carbon::parse($tanggal_nomor);
        $setting = PenomoranSetting::where('kategori', $kategori)->first();

        if (! $setting) {
            throw new Exception(
                "Pengaturan penomoran untuk kategori {$kategori} belum tersedia."
            );
        }

        if ($setting->mode === 'manual') {
            throw new Exception("Kategori {$kategori} menggunakan metode manual.");
        }

        $isBackDate = $tanggalNomor->copy()->startOfDay()->isBefore(
            Carbon::now()->startOfDay()
        );

        $getCancelNomor = NomorPpat::query()
            ->whereDate('tanggal', $tanggalNomor)
            ->orderByRaw('CAST(nomor AS UNSIGNED) DESC')
            ->where('rekanan', 0)
            ->where('kategori', $kategori)
            ->whereHas('formOrder', function ($query) use ($kategori) {
                $query->where('status', 'Dibatalkan')
                    ->where('kategori', $kategori);
            })
            ->first();

        if ($getCancelNomor) {
            return (string) $getCancelNomor->nomor;
        }

        $periodQuery = NomorPpat::query()
            ->where('rekanan', 0)
            ->where('kategori', $kategori);

        if ($setting->reset_period === 'month') {
            $periodQuery->whereMonth('tanggal', $tanggalNomor->month)
                ->whereYear('tanggal', $tanggalNomor->year);
        } else {
            $periodQuery->whereYear('tanggal', $tanggalNomor->year);
        }

        $periodRecords = $periodQuery->get(['nomor', 'tanggal']);
        $periodSequence = $periodRecords
            ->map(fn ($record) => $this->parseSequence((string) $record->nomor)['sequence'])
            ->max() ?? 0;

        $sameDayRecord = $periodRecords
            ->filter(fn ($record) => Carbon::parse($record->tanggal)->isSameDay($tanggalNomor))
            ->sort(function ($left, $right) {
                $leftNumber = $this->parseSequence((string) $left->nomor);
                $rightNumber = $this->parseSequence((string) $right->nomor);

                return [$rightNumber['sequence'], $rightNumber['suffix']]
                    <=> [$leftNumber['sequence'], $leftNumber['suffix']];
            })
            ->first();

        $sequence = 1;
        $suffix = '';

        if ($periodSequence > 0) {
            if ($sameDayRecord && $isBackDate) {
                $lastSameDay = $this->parseSequence((string) $sameDayRecord->nomor);
                $sequence = $lastSameDay['sequence'];

                if ($lastSameDay['suffix'] !== '') {
                    $suffix = $lastSameDay['suffix'];
                    $suffix++;
                } else {
                    $suffix = 'A';
                }
            } else {
                $sequence = $periodSequence + 1;
            }
        }

        return PenomoranSetting::formatNomor(
            $setting->format ?: 'nnn/CN/mm/yyyy',
            $sequence,
            $tanggalNomor,
            $setting->month_format ?: 'number',
            $suffix
        );
    }

    private function parseSequence(string $nomor): array
    {
        if (preg_match('/^\s*(\d+)([A-Za-z]*)/', $nomor, $matches) !== 1) {
            return ['sequence' => 0, 'suffix' => ''];
        }

        return [
            'sequence' => (int) $matches[1],
            'suffix' => strtoupper($matches[2]),
        ];
    }
}
