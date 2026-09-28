<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportJobOps implements FromCollection, WithHeadings, WithMapping
{
    protected $items;
    protected $rowNumber = 0;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID Parent',
            'Grup Pekerjaan',
            'Nama Debitur',
            'Nama Bank',
            'Objek Nomor Sertifikat',
            'Objek Nama Sertifikat',
            'Nama Proses',
            'Biaya',
            'Tanggal Mulai',
            'Status',
            'Remarks'
        ];
    }

    public function map($item): array
        {
            $this->rowNumber++;

            // Handle Status logic safely
            $statusJobOps = $item->statusJobOps;
            if ($statusJobOps instanceof \Illuminate\Database\Eloquent\Collection) {
                $statusJobOps = $statusJobOps->sortByDesc('id')->first();
            }

            $statusText = 'belum dikerjakan';
            if ($item->status === 'dispo') {
                $statusText = 'dispo';
            } elseif ($statusJobOps) {
                $statusText = $statusJobOps->status ?? 'belum dikerjakan';
            }

            return [
                $this->rowNumber,                                               // 1. No.
                $item->jobDivisi->kode ?? '-',                                  // 2. ID Parent
                $item->jobDivisi->jenisAkad->nama ?? '-',                       // 3. Grup Pekerjaan
                $item->jobDivisi->debitur ? $item->jobDivisi->debitur->pluck('nama')->implode(', ') : '-', // 4. Nama Debitur
                $item->jobDivisi->bank->nama ?? '-',                   // 5. Nama Bank
                $item->jobDivisi->objek ? $item->jobDivisi->objek->pluck('no_sertifikat')->implode(', ') : '-',                  // 6. Objek No Sertifikat
                $item->jobDivisi->objek ? $item->jobDivisi->objek->pluck('jenis_sertifikat')->implode(', ') : '-',                  // 6. Objek No Sertifikat
                $item->nama ?? '-',                                             // 7. Nama Proses
                $item->harga_proses ?? 0,                                       // 8. Biaya
                $item->created_at ? $item->created_at->format('Y-m-d') : '-',   // 9. Tanggal Mulai
                $statusText,                                                    // 10. Status
                $statusJobOps->keterangan ?? '-'                                // 11. remarks
            ];
        }
}