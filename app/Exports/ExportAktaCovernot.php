<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportAktaCovernot implements FromCollection, WithHeadings, WithMapping
{
    protected $items;
    protected $rowNumber = 0;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function collection()
    {
        return $this->items instanceof \Illuminate\Pagination\LengthAwarePaginator 
            ? $this->items->getCollection() 
            : collect($this->items);
    }

    public function headings(): array
    {
        return [
            'No.',
            'ID Parent',
            'Grup Pekerjaan',
            'Nama Debitur',
            'Nama Bank',
            'Objek',
            'Nomor Covernote',
            'Tanggal Covernote',
            'Expired Covernote'
        ];
    }

    /**
     * @param mixed $item
     */
    public function map($item): array
    {
        /** @var \App\Models\JobDivisiFormOrder $item */
        $this->rowNumber++;

        // Safely extract covernote using your fail-safe logic
        $covernote = null;
        $relation = $item->nomorPpat;

        if ($relation instanceof \Illuminate\Database\Eloquent\Collection) {
            $covernote = $relation->first();
        } elseif ($relation instanceof \Illuminate\Database\Eloquent\Model) {
            $covernote = $relation;
        } elseif (is_iterable($relation)) {
            $covernote = collect($relation)->first();
        } else {
            $covernote = $item->nomorPpat()->first();
        }

        // Format dates safely
        $tanggalCovernote = '-';
        if ($covernote && !empty($covernote->tanggal_covernote)) {
            $tanggalCovernote = date('Y-m-d', strtotime($covernote->tanggal_covernote));
        }

        $expiredCovernote = '-';
        if ($covernote && !empty($covernote->expired_covernote)) {
            $expiredCovernote = date('Y-m-d', strtotime($covernote->expired_covernote));
        }

        // Handle Debitur (using collection check or direct property depending on relationship)
        $namaDebitur = '-';
        if ($item->jobDivisi->debitur) {
            $namaDebitur = $item->jobDivisi->debitur instanceof \Illuminate\Support\Collection 
                ? $item->jobDivisi->debitur->pluck('nama')->implode(', ') 
                : ($item->jobDivisi->debitur->nama ?? '-');
        }

        // Handle Objek
        $namaObjek = '-';
        if ($item->jobDivisi->objek) {
            $namaObjek = $item->jobDivisi->objek instanceof \Illuminate\Support\Collection 
                ? $item->jobDivisi->objek->pluck('no_sertifikat')->implode(', ') 
                : ($item->jobDivisi->objek->no_sertifikat ?? '-');
        }

        return [
            $this->rowNumber,                                                               
            $item->jobDivisi->kode ?? '-',                                                  
            $item->jobDivisi->jenisAkad->nama ?? '-',                                       
            $namaDebitur,                                                                   
            $item->jobDivisi->bank->nama ?? '-', // Fixed to match listBank relation
            $namaObjek,                                                                     
            $covernote->nomor_covernote ?? '-',                                             
            $tanggalCovernote,                                                              
            $expiredCovernote,                                                              
        ];
    }
}
