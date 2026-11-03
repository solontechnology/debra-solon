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
$aktaFields = [
    'parent'      => 'Parent',
    'process'     => 'Proses',
    'number'      => 'Nomor Akta',
    'status'      => 'Status',
    'assigned_to' => 'Petugas',
    'assigned_qc' => 'Petugas QC',
    'object'      => 'Nomor Objek',
    'participant' => 'Nama Penghadap',
    'bank'        => 'Nama Bank',
    'status_akad' => 'Status Akad',
];
$divisiFields = [
    'parent'            => 'Kode',
    'status'            => 'Status',
    // 'progress'          => 'Progress',
    'participant'       => 'Nama Penghadap',
    'bank'              => 'Bank',
    'object'            => 'Objek',
    'akad_type'         => 'Jenis Akad',
    'akad_date'         => 'Tanggal Akad',
    'estimate_internal' => 'Est. Internal',
    'estimate_external' => 'Est. Eksternal',
    'person_in_charge'  => 'Penanggung Jawab',
    'created_by_user'   => 'Dibuat Oleh',
];
$opsFields = [
    'parent'      => 'Parent',
    'status_akad' => 'Status Akad',
    'process'     => 'Proses',
    'object'      => 'Nomor Objek',
    'participant' => 'Nama Penghadap',
    'bank'        => 'Nama Bank',
    'process_fee' => ['label' => 'Biaya Proses', 'permission' => 'job/ops/biaya proses'],
    'work_status' => 'Status Pengerjaan',
];
$pajakFields = [
    'parent'       => 'Parent',
    'process'      => 'Proses',
    'object'       => 'Nomor Objek',
    'participant' => 'Nama Penghadap',
    'bank'         => 'Nama Bank',
    'tax_payment'  => 'Pembayaran',
    'billing_code' => 'Kode Billing',
    'status_akad'  => 'Status Akad',
    'status'       => 'Status',
];

$pnbpFields = [
    'parent'          => 'Parent',
    'process'         => 'Proses',
    'object'          => 'Nomor Objek',
    'participant'     => 'Nama Penghadap',
    'bank'            => 'Nama Bank',
    'pnbp_officer'    => 'Nama Petugas',
    'status_akad'     => 'Status Akad',
    'status'          => 'Status',
    'virtual_account' => 'Nomor VA',
    'amount'          => 'Nominal',
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
        'fields' => $divisiFields,
    ],
];

foreach ($formOrderTypes as $key => $type) {
    $isAkta = in_array($key, ['notaris', 'ppat', 'legalisasi', 'waarmerking', 'surat-keluar', 'wasiat', 'covernot'], true);

    $exports[$key] = [
        'label' => 'Job ' . $type['label'],
        'permission' => $type['permission'],
        'category' => $type['category'],
        'fields' => $isAkta
    ? $aktaFields
    : match ($key) {
        'operasional' => $opsFields,
        'pajak'       => $pajakFields,
        'pnbp'        => $pnbpFields,
        default       => $formOrderFields,
    },
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
