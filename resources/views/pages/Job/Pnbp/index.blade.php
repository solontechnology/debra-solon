@extends('layouts.admin')

@section('title')
    Job PNBP/Voucher
@endsection

@push('css')
    <style>
        /* Standard Sticky Columns for Table Freeze */
        .table-freeze-pnbp {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-freeze-pnbp .freeze-parent {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: #fff;
        }

        .table-freeze-pnbp .freeze-proses {
            position: sticky;
            left: 120px; /* Sesuaikan offset sesuai lebar kolom parent */
            z-index: 2;
            background-color: #fff;
        }

        .table-freeze-pnbp thead .freeze-parent,
        .table-freeze-pnbp thead .freeze-proses {
            z-index: 3;
            background-color: #f8f9fa; /* Warna background thead (bg-light) */
        }
    </style>
@endpush

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">PNBP / Voucher</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Card Header --}}
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold m-0 text-dark">Daftar Job PNBP / Voucher</h5>
                <p class="text-muted small m-0">Kelola dan pantau proses penugasan serta pembayaran PNBP/Voucher</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @include('pages.Job.Pnbp._filter')
            </div>
        </div>

        {{-- Card Body / Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap table-freeze-pnbp">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-4 freeze-parent text-secondary text-uppercase font-monospace small fw-bold">Parent</th>
                            <th class="py-3 freeze-proses text-secondary text-uppercase font-monospace small fw-bold">Proses</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nomor Objek</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nama Debitur</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nama Bank</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nama Petugas</th>
                            <th class="py-3 text-center text-secondary text-uppercase font-monospace small fw-bold">Status Akad</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Status</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nominal</th>
                            <th class="py-3 pe-4 text-center text-secondary text-uppercase font-monospace small fw-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($items as $key => $item)
                            @php
                                $isSuperAdmin = auth()->user()->hasRole('super admin');
                                $isAssignedUser = $item->pnbp && $item->pnbp->user_id == auth()->id();
                                $canAssign = auth()->user()->can('job/pnbp/penugasan');
                                $canAccess = $isSuperAdmin || $isAssignedUser;
                            @endphp
                            <tr>
                                {{-- Sticky Column 1: Parent --}}
                                <td class="ps-4 freeze-parent fw-semibold">
                                    <span class="text-primary">{{ $item->jobDivisi->kode ?? '-' }}</span>
                                </td>

                                {{-- Sticky Column 2: Proses --}}
                                <td class="freeze-proses fw-medium">
                                    {{ $item->nama }}
                                </td>

                                {{-- Nomor Objek --}}
                                <td>
                                    @if ($item->jobDivisi?->objek?->isNotEmpty())
                                        <span class="text-muted">
                                            {{ $item->jobDivisi?->objek?->pluck('no_sertifikat')->implode(', ') }}
                                        </span>
                                    @else
                                        <span class="text-warning small">
                                            Belum di input no sertifikat
                                        </span>
                                    @endif
                                </td>

                                {{-- Nama Debitur --}}
                                <td class="text-uppercase text-wrap" style="max-width: 200px;">
                                    @if ($item->jobDivisi->debitur->isNotEmpty())
                                        <span>{{ $item->jobDivisi->debitur->pluck('nama')->implode(', ') }}</span>
                                    @else
                                        <span class="text-warning small">
                                            Belum di input nama debitur
                                        </span>
                                    @endif
                                </td>

                                {{-- Nama Bank --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($item->jobDivisi->listBank as $bank)
                                            <span class="badge bg-light text-dark border">{{ $bank->nama_bank }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- Nama Petugas --}}
                                <td>
                                    <span class="fw-medium text-dark">
                                        {{ $item->pnbp?->user?->name ?? '-' }}
                                    </span>
                                </td>

                                {{-- Status Akad --}}
                                <td class="text-center">
                                    @php
                                        $statusClass = [
                                            'Pra Akad'   => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            'Akad'       => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'Pending'    => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                            'Batal Akad' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'Selesai'    => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                        ];
                                        $statusAkad = $item->jobDivisi->status ?? '';
                                    @endphp

                                    <span class="badge rounded-pill {{ $statusClass[$statusAkad] ?? 'bg-secondary' }} px-3 py-2 fw-medium text-uppercase" style="font-size: 0.75rem;">
                                        {{ $statusAkad ?: '-' }}
                                    </span>
                                </td>

                                {{-- Status Process --}}
                                <td>
                                    @if (!$item->pnbp)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 fw-medium">
                                            Belum dikerjakan
                                        </span>
                                    @elseif ($item->pnbp->status === 'Penugasan')
                                        <span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 px-2.5 py-1.5 fw-medium">
                                            Penugasan
                                        </span>
                                    @elseif ($item->pnbp->status === 'Sedang Online')
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-2.5 py-1.5 fw-medium">
                                            Sedang Online
                                        </span>
                                    @elseif ($item->pnbp->status === 'Menunggu Pembayaran')
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2.5 py-1.5 fw-medium">
                                            Menunggu Pembayaran
                                        </span>
                                    @elseif ($item->pnbp->status === 'Terbayar')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-medium">
                                            Terbayar
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border px-2.5 py-1.5 fw-medium">
                                            {{ $item->pnbp->status }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Nominal --}}
                                <td class="fw-semibold text-dark">
                                    Rp {{ number_format($item->pnbp->nominal ?? 0, 0, ',', '.') }}
                                </td>

                                {{-- Action Buttons --}}
                                <td class="pe-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        {{-- BELUM ADA DATA --}}
                                        @if (!$item->pnbp)
                                            @if ($canAssign)
                                                @include('pages.Job.Pnbp._modal_assign', [
                                                    'key' => $key,
                                                    'item' => $item,
                                                    'users' => $users,
                                                ])
                                            @endif
                                        @else
                                            {{-- PENUGASAN --}}
                                            @if ($item->pnbp->status === 'Penugasan')
                                                @include('pages.Job.Pnbp._modal_assign', [
                                                    'key' => $key,
                                                    'item' => $item,
                                                    'users' => $users,
                                                ])
                                            @endif

                                            {{-- INPUT VA --}}
                                            @if ($item->pnbp->status === 'Sedang Online')
                                                @can('job/pnbp/input-va')
                                                    @include('pages.Job.Pnbp._modal_input_va', [
                                                        'key' => $key,
                                                        'item' => $item,
                                                    ])
                                                @endcan
                                            @endif

                                            {{-- PAYMENT --}}
                                            @if ($item->pnbp->status === 'Menunggu Pembayaran')
                                                @can('job/pnbp/payment')
                                                    @include('pages.Job.Pnbp._modal_payment', [
                                                        'key' => $key,
                                                        'item' => $item,
                                                    ])
                                                @endcan
                                            @endif
                                        @endif

                                        <x-job.file.file-job-divisi :jobDivisi="$item->jobDivisi" nama="pnbp" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                            fill="currentColor" class="bi bi-inbox text-secondary mb-2 opacity-50"
                                            viewBox="0 0 16 16">
                                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z" />
                                        </svg>
                                        <span class="fw-medium">Belum ada data Job PNBP / Voucher</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Pagination --}}
        <div class="card-footer bg-white border-top py-3 px-4 d-flex align-items-center justify-content-between">
            <div class="text-muted small">
                Menampilkan <strong>{{ $items->firstItem() ?? 0 }}</strong> - <strong>{{ $items->lastItem() ?? 0 }}</strong> dari <strong>{{ $items->total() }}</strong> data
            </div>
            <div class="m-0">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection