@extends('layouts.admin')

@section('title')
    Laporan Penomoran
@endsection

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Laporan</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Penomoran</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Card Header --}}
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold m-0 text-dark">Laporan Penomoran</h5>
                <p class="text-muted small m-0">Kelola dan pantau rekapitulasi data penomoran notaris</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Modal Tambah Nomor --}}
                <div class="d-flex align-items-center">
                    @include('pages.Laporan.nomor-notaris._modal_add_nomor')
                </div>

                {{-- Button Export Excel --}}
                <a href="{{ route('laporan.nomor-notaris.export', $kategori) }}"
                    class="btn btn-success d-inline-flex align-items-center justify-content-center gap-2 shadow-sm px-3 py-2 fw-medium">
                    <i class="bi bi-file-earmark-excel fs-6 lh-1"></i>
                    <span>Export Excel</span>
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-4 text-secondary text-uppercase font-monospace small fw-bold">
                                Parent
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Nomor
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Proses
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Tanggal
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Nama Debitur
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Nomor Objek
                            </th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">
                                Pengguna
                            </th>
                            <th class="py-3 text-center text-secondary text-uppercase font-monospace small fw-bold">
                                Upload Doc
                            </th>
                            <th class="py-3 pe-4 text-center text-secondary text-uppercase font-monospace small fw-bold">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($items as $item)
                            <tr>
                                {{-- Parent --}}
                                <td class="ps-4 fw-semibold">
                                    <span class="text-primary">
                                        {{ $item->formOrder->jobDivisi->kode ?? 'Notaris Luar' }}
                                    </span>
                                </td>

                                {{-- Nomor --}}
                                <td class="fw-semibold">
                                    {{ $item->nomor }}
                                </td>

                                {{-- Proses --}}
                                <td class="fw-medium">
                                    @if ($item->form_order_id)
                                        {{ $item->pekerjaan->nama ?? '-' }}
                                    @else
                                        {{ $item->formOrder->nama ?? '-' }}
                                    @endif
                                </td>

                                {{-- Tanggal --}}
                                <td class="text-muted small">
                                    {{ $item->tanggal ? 'Tgl. ' . $item->tanggal : '-' }}
                                </td>

                                {{-- Nama Debitur --}}
                                <td class="text-uppercase text-wrap" style="max-width: 200px;">
                                    @if ($item->formOrder?->jobDivisi?->debitur?->isNotEmpty())
                                        {{ $item->formOrder->jobDivisi->debitur->pluck('nama')->join(', ') }}
                                    @elseif ($item->nama_debitur_notaris_pengambil)
                                        {{ $item->nama_debitur_notaris_pengambil }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-uppercase text-wrap" style="max-width: 200px;">
                                    {{ $item->formOrder?->jobDivisi?->objek?->pluck('no_sertifikat')->implode(', ') ?: '-' }}
                                </td>
                                <td>
                                    @if ($item->notaris_pengambil)
                                        <span class="text-secondary fw-medium">{{ $item->notaris_pengambil }}</span>
                                    @else
                                        <span
                                            class="badge {{ $item->rekanan ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' }}">
                                            {{ $item->rekanan ? 'Rekanan' : 'Internal' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Upload Doc --}}
                                <td class="text-center">
                                    <button
                                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm px-2.5 py-1.5"
                                        title="Upload Document">
                                        <i class="bi bi-cloud-upload fs-6"></i>
                                        <span class="small">Upload</span>
                                    </button>
                                </td>

                                {{-- Action Buttons --}}
                                <td class="pe-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        @include('pages.Laporan.nomor-notaris._modal_edit')

                                        @if ($item->rekanan === 1)
                                            <button class="btn btn-success btn-sm px-3 fw-medium">
                                                Terima
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                            fill="currentColor" class="bi bi-inbox text-secondary mb-2 opacity-50"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z" />
                                        </svg>
                                        <span class="fw-medium">Belum ada data Laporan Penomoran</span>
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
