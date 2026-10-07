<?php

$jobFields = [
    'parent' => 'Kode Parent',
    'process' => 'Proses',
    'debtor' => 'Debitur',
    'akad_type' => 'Jenis Akad',
    'bank' => 'Bank',
    'object' => 'Nomor Objek',
    'status' => 'Status',
    'created_at' => 'Tanggal Dibuat',
];

$formOrderFields = $jobFields + [
    'number' => 'Nomor Akta',
    'assigned_to' => 'Petugas',
    'remarks' => 'Keterangan',
];

$formOrderTypes = [
    'notaris' => ['label' => 'Notaris', 'category' => 'notaris', 'permission' => 'job/notaris/list'],
    'ppat' => ['label' => 'PPAT', 'category' => 'ppat', 'permission' => 'job/ppat/list'],
    'legalisasi' => ['label' => 'Legalisasi', 'category' => 'legalisasi', 'permission' => 'job/legalisasi/list'],
    'waarmerking' => ['label' => 'Waarmerking', 'category' => 'waarmerking', 'permission' => 'job/waarmerking/list'],
    'surat-keluar' => ['label' => 'Surat Keluar', 'category' => 'surat-keluar', 'permission' => 'job/surat-keluar/list'],
    'wasiat' => ['label' => 'Wasiat', 'category' => 'wasiat', 'permission' => 'job/wasiat/list'],
    'covernot' => ['label' => 'Cover Note', 'category' => 'covernot', 'permission' => 'job/covernot/list'],
    'pajak' => ['label' => 'Pajak', 'category' => 'pajak', 'permission' => 'job/pajak/list'],
    'pnbp' => ['label' => 'PNBP/Voucher', 'category' => 'pnbp_voucher', 'permission' => 'job/pnbp/list'],
    'operasional' => ['label' => 'Operasional', 'category' => 'operasional', 'permission' => 'job/ops/list'],
];

$exports = [
    'divisi' => [
        'label' => 'Job Divisi',
        'permission' => 'job/divisi/list',
        'fields' => $jobFields + [
            'akad_date' => 'Tanggal Akad',
            'estimate_internal' => 'Estimasi Selesai Internal',
            'estimate_external' => 'Estimasi Selesai Eksternal',
        ],
    ],
];

foreach ($formOrderTypes as $key => $type) {
    $exports[$key] = [
        'label' => 'Job '.$type['label'],
        'permission' => $type['permission'],
        'category' => $type['category'],
        'fields' => $formOrderFields + ($key === 'pnbp' ? [
            'virtual_account' => 'Nomor VA',
            'amount' => 'Nominal',
        ] : []),
    ];
}

$exports['penambahan-item'] = [
    'label' => 'Penambahan Item',
    'permission' => 'job/penambahan-item/list',
    'fields' => $jobFields + [
        'request_code' => 'Kode Pengajuan',
        'process' => 'Item Tambahan',
        'remarks' => 'Keterangan',
        'created_by' => 'Diajukan Oleh',
        'approved_by' => 'Disetujui Oleh',
    ],
];
$exports['pembatalan-item'] = [
    'label' => 'Pembatalan Item',
    'permission' => 'job/pembatalan-item/list',
    'fields' => $jobFields + [
        'request_code' => 'Kode Pengajuan',
        'process' => 'Item Dibatalkan',
        'remarks' => 'Keterangan',
        'created_by' => 'Diajukan Oleh',
        'approved_by' => 'Disetujui Oleh',
    ],
];

return $exports;
