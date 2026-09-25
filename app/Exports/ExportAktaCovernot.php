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


public function map($item): array
    {
        $this->rowNumber++;

        // 1. Safely handle the relation (in case it's a Collection or from activeCovernote)
        $nomorPpat = $item->activeCovernote ?? $item->nomorPpat;
        if ($nomorPpat instanceof \Illuminate\Support\Collection) {
            $nomorPpat = $nomorPpat->first();
        }


        $tanggalCovernote = '-';
        if (!empty($nomorPpat) && !empty($nomorPpat->tanggal)) {
            $tanggalCovernote = date('Y-m-d', strtotime($nomorPpat->tanggal));
        }


        $expiredCovernote = '-';
        if (!empty($nomorPpat) && !empty($nomorPpat->tanggal_expired)) {
            $expiredCovernote = date('Y-m-d', strtotime($nomorPpat->tanggal_expired));
        }

        $namaDebitur = '-';
        if (!empty($item->jobDivisi->debitur)) {
            $namaDebitur = $item->jobDivisi->debitur instanceof \Illuminate\Support\Collection 
                ? $item->jobDivisi->debitur->pluck('nama')->implode(', ') 
                : ($item->jobDivisi->debitur->nama ?? '-');
        }

        $namaObjek = '-';
        if (!empty($item->jobDivisi->objek)) {
            $namaObjek = $item->jobDivisi->objek instanceof \Illuminate\Support\Collection 
                ? $item->jobDivisi->objek->pluck('no_sertifikat')->implode(', ') 
                : ($item->jobDivisi->objek->no_sertifikat ?? '-');
        }

        return [
            $this->rowNumber,                                                       
            $item->jobDivisi->kode ?? '-',                                          
            $item->jobDivisi->jenisAkad->nama ?? '-',                               
            $namaDebitur,                                                           
            $item->jobDivisi->bank->nama ?? '-', 
            $namaObjek,                                                             
            $nomorPpat->nomor_covernote ?? $nomorPpat->nomor ?? '-', // Fixed undefined $covernote variable here
            $tanggalCovernote,                                                      
            $expiredCovernote,                                                      
        ];
    }
}