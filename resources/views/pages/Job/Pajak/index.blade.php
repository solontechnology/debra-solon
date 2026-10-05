@extends('layouts.admin')

@section('title')
    Job Pajak
@endsection

@push('css')
    <style>
        /* Standard Sticky Columns for Table Freeze */
        .table-freeze-akta {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-freeze-akta .freeze-parent {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: #fff;
        }

        .table-freeze-akta .freeze-proses {
            position: sticky;
            left: 120px;
            /* Sesuaikan offset sesuai lebar kolom parent */
            z-index: 2;
            background-color: #fff;
        }

        .table-freeze-akta thead .freeze-parent,
        .table-freeze-akta thead .freeze-proses {
            z-index: 3;
            background-color: #f8f9fa;
            /* Warna background thead (bg-light) */
        }
    </style>
@endpush

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Pajak</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Card Header --}}
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold m-0 text-dark">Daftar Job Pajak</h5>
                <p class="text-muted small m-0">Kelola dan pantau seluruh berkas proses pajak</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @include('pages.Job.Pajak._filter')
            </div>
        </div>

        {{-- Card Body / Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap table-freeze-akta">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-4 freeze-parent text-secondary text-uppercase font-monospace small fw-bold">
                                Parent</th>
                            <th class="py-3 freeze-proses text-secondary text-uppercase font-monospace small fw-bold">Proses
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nomor Objek</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nama Debitur</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Nama Bank</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Pembayaran</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Kode Billing</th>
                            <th class="py-3 text-center text-secondary text-uppercase font-monospace small fw-bold">Status
                                Akad</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Status</th>
                            <th class="py-3 pe-4 text-center text-secondary text-uppercase font-monospace small fw-bold">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($items as $key => $item)
                            <tr class="{{ isset($item->statusJobOps->last()->status_penolakan) ? 'bg-red-lt' : '' }}">
                                {{-- Sticky Column 1: Parent --}}
                                <td class="ps-4 freeze-parent fw-semibold">
                                    <a href="{{ route('job.divisi.show', $item->jobDivisi->id) }}"
                                        class="text-decoration-none">
                                        <span class="text-primary">{{ $item->jobDivisi->kode ?? '-' }}</span>
                                    </a>
                                </td>

                                {{-- Sticky Column 2: Proses --}}
                                <td class="freeze-proses fw-medium">
                                    {{ $item->nama }}
                                </td>

                                {{-- Nomor Objek --}}
                                <td>
                                    <span class="text-muted">
                                        {{ $item->jobDivisi?->objek?->pluck('no_sertifikat')->implode(', ') ?: '-' }}
                                    </span>
                                </td>

                                {{-- Nama Debitur --}}
                                @php
                                    $pihak = array_filter([
                                        'Debitur' => $item->jobDivisi->debitur->pluck('nama')->filter()->join(', '),
                                        'Pembeli' => $item->jobDivisi->listPembeli->pluck('nama')->filter()->join(', '),
                                        'Penjual' => $item->jobDivisi->listPenjual->pluck('nama')->filter()->join(', '),
                                    ]);
                                @endphp

                                <td class="text-uppercase">
                                    <div class="d-flex flex-column gap-1">
                                        @forelse ($pihak as $peran => $nama)
                                            <span>
                                                <small class="text-muted">{{ $peran }}:</small> {{ $nama }}
                                            </span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
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

                                {{-- Pembayaran --}}
                                <td class="fw-semibold text-dark">
                                    Rp. {{ number_format($item->harga_jual, 2, ',', '.') }}
                                </td>

                                {{-- Kode Billing --}}
                                <td>
                                    <span class="font-monospace text-secondary">
                                        {{ $item->finance->kode_billing ?? '-' }}
                                    </span>
                                </td>

                                {{-- Status Akad --}}
                                <td class="text-center">
                                    @php
                                        $statusClass = [
                                            'Pra Akad' =>
                                                'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            'Akad' =>
                                                'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'Pending' =>
                                                'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                            'Batal Akad' =>
                                                'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'Selesai' =>
                                                'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                        ];
                                        $status = $item->jobDivisi->status ?? '';
                                    @endphp

                                    <span
                                        class="badge rounded-pill {{ $statusClass[$status] ?? 'bg-secondary' }} px-3 py-2 fw-medium text-uppercase"
                                        style="font-size: 0.75rem;">
                                        {{ $status ?: '-' }}
                                    </span>
                                </td>

                                {{-- Status Process --}}
                                <td>
                                    @php $lastStatus = $item->statusJobOps->last(); @endphp

                                    @if ($lastStatus)
                                        @if ($lastStatus->status_penolakan)
                                            <span
                                                class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1.5 fw-medium">
                                                {{ $lastStatus->status_penolakan }} Perlu Perbaikan
                                            </span>
                                        @elseif (strtolower($lastStatus->status) === 'selesai')
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-medium">
                                                {{ $lastStatus->status }}
                                            </span>
                                        @else
                                            <span
                                                class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 fw-medium">
                                                {{ $lastStatus->status ?? 'Belum dikerjakan' }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-secondary border px-2.5 py-1.5 fw-medium">Belum
                                            dikerjakan</span>
                                    @endif
                                </td>

                                {{-- Action Buttons --}}
                                <td class="pe-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        @can('job/pajak/edit')
                                            @if ($item->jobDivisi->status !== 'Batal Akad')
                                                <x-pajak.edit-status-pajak :formOrder="$item" :key="$key" :jobDivisi="$item->jobDivisi"
                                                    :statusJobOps="$item->statusJobOps" />

                                                <x-job.file.file-job-divisi :jobDivisi="$item->jobDivisi" nama="pajak" />
                                            @else
                                                <span class="text-muted small fs-7">Tidak ada aksi</span>
                                            @endif
                                        @else
                                            <span class="text-muted small fs-7">-</span>
                                        @endcan
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
                                            <path
                                                d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z" />
                                        </svg>
                                        <span class="fw-medium">Belum ada data Job PAJAK</span>
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
                Menampilkan <strong>{{ $items->firstItem() ?? 0 }}</strong> -
                <strong>{{ $items->lastItem() ?? 0 }}</strong> dari <strong>{{ $items->total() }}</strong> data
            </div>
            <div class="m-0">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
