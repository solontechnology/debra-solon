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
            <form method="GET" action="{{ url()->current() }}" class="border-bottom px-4 py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label">Filter tanggal nomor berdasarkan</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="">Tanpa filter tanggal</option>
                            <option value="range" @selected(($filters['filter_type'] ?? '') === 'range')>Rentang tanggal</option>
                            <option value="month" @selected(($filters['filter_type'] ?? '') === 'month')>Bulan dan tahun</option>
                        </select>
                    </div>
                    <div class="col-md-2 date-range-filter">
                        <label for="start_date" class="form-label">Dari tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $filters['start_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2 date-range-filter">
                        <label for="end_date" class="form-label">Sampai tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $filters['end_date'] ?? '' }}">
                    </div>
                    <div class="col-md-2 month-filter">
                        <label for="month" class="form-label">Bulan</label>
                        <select name="month" id="month" class="form-select">
                            <option value="">Pilih bulan</option>
                            @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" @selected((string) ($filters['month'] ?? '') === (string) $monthNumber)>
                                    {{ $monthName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 month-filter">
                        <label for="year" class="form-label">Tahun</label>
                        <input type="number" name="year" id="year" class="form-control" min="1900" max="2200"
                            value="{{ $filters['year'] ?? '' }}" placeholder="YYYY">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
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
                                Notaris Rekanan
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
                                Pemilik / Pemakai Nomor
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
                                    @if ($item->formOrder?->jobDivisi)
                                        <a href="{{ route('job.divisi.show', $item->formOrder->jobDivisi->id) }}"
                                            class="text-decoration-none">
                                            <span class="text-primary">{{ $item->formOrder->jobDivisi->kode }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted">Notaris Luar</span>
                                    @endif
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
                                <td>{{ $item->notarisRekanan?->nama ?? '-' }}</td>

                                {{-- Tanggal --}}
                                <td class="text-muted small">
                                    {{ $item->tanggal ? 'Tgl. ' . $item->tanggal : '-' }}
                                </td>

                                {{-- Nama Debitur --}}
                                @php
                                    $job = $item->formOrder?->jobDivisi;

                                    $pihak = array_filter([
                                        'Debitur' => $job?->debitur?->pluck('nama')->filter()->join(', '),
                                        'Pembeli' => $job?->listPembeli?->pluck('nama')->filter()->join(', '),
                                        'Penjual' => $job?->listPenjual?->pluck('nama')->filter()->join(', '),
                                    ]);

                                    // fallback: kalau semua kosong, pakai nama dari notaris pengambil
                                    if (empty($pihak) && $item->nama_debitur_notaris_pengambil) {
                                        $pihak = ['Debitur' => $item->nama_debitur_notaris_pengambil];
                                    }
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
                                <td class="text-uppercase text-wrap" style="max-width: 200px;">
                                    {{ $item->formOrder?->jobDivisi?->objek?->pluck('no_sertifikat')->implode(', ') ?: '-' }}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span><small class="text-muted">Pemilik:</small> {{ $item->pemilik_nomor }}</span>
                                        <span><small class="text-muted">Dipakai oleh:</small> {{ $item->pemakai_nomor }}</span>
                                    </div>
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
                                <td colspan="10" class="text-center py-5 text-muted">
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

@push('addScript')
    <script>
        (() => {
            const filterType = document.getElementById('filter_type');
            const rangeFields = document.querySelectorAll('.date-range-filter input');
            const monthFields = document.querySelectorAll('.month-filter select, .month-filter input');

            function updateDateFilterFields() {
                const isRangeFilter = filterType.value === 'range';
                const isMonthFilter = filterType.value === 'month';
                document.querySelectorAll('.date-range-filter').forEach((field) => {
                    field.classList.toggle('d-none', !isRangeFilter);
                });
                document.querySelectorAll('.month-filter').forEach((field) => {
                    field.classList.toggle('d-none', !isMonthFilter);
                });
                rangeFields.forEach((field) => field.disabled = !isRangeFilter);
                monthFields.forEach((field) => field.disabled = !isMonthFilter);
            }

            filterType.addEventListener('change', updateDateFilterFields);
            updateDateFilterFields();
        })();
    </script>
@endpush
