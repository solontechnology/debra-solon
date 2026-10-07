<?php

namespace App\Exports;

use App\Models\NomorPpat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PenomoranExport implements FromCollection, WithHeadings
{
    protected $kategori;

    public function __construct($kategori = null)
    {
        $this->kategori = $kategori;
    }

    public function collection()
    {
        return NomorPpat::with('formOrder.jobDivisi', 'pekerjaan', 'notarisRekanan')
            ->when($this->kategori, function ($query) {
                $query->where('kategori', $this->kategori);
            })
            ->get()
            ->map(function ($item) {

                return [
                    'parent' => $item->formOrder->jobDivisi->kode ?? 'Notaris Luar',
                    'nomor' => $item->nomor,

                    'proses' => $item->form_order_id
                        ? $item->pekerjaan->nama ?? '-'
                        : $item->formOrder->nama ?? '-',

                    'notaris_rekanan' => $item->notarisRekanan->nama ?? '-',
                    'debitur' => $item->nama_debitur_notaris_pengambil ?? '-',

                    'tanggal' => $item->tanggal,

                    'pemilik_nomor' => $item->pemilik_nomor,
                    'dipakai_oleh' => $item->pemakai_nomor,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Parent',
            'Nomor',
            'Proses',
            'Notaris Rekanan',
            'Debitur',
            'Tanggal',
            'Pemilik Nomor',
            'Dipakai Oleh',
        ];
    }
}
