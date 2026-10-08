@extends('layouts.admin')

@section('title', 'Laporan Pemakaian Nomor')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1">Laporan Pemakaian Nomor</h3>
                <div class="text-secondary small">
                    Job memakai nomor milik notaris rekanan untuk digunakan {{ \App\Models\Setting::namaNotaris() }}; Pelaporan mencatat nomor milik {{ \App\Models\Setting::namaNotaris() }}
                    yang dipakai notaris rekanan. Kedua arah pemakaian ditampilkan terpisah untuk dibandingkan.
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('laporan.pemakaian-nomor') }}" class="mb-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="notaris-rekanan-filter" class="form-label">Notaris Rekanan</label>
                        <select id="notaris-rekanan-filter" name="notaris_rekanan_id" class="form-select">
                            <option value="">Semua Notaris Rekanan</option>
                            @foreach ($notarisRekanan as $rekanan)
                                <option value="{{ $rekanan->id }}" @selected($selectedRekananId === $rekanan->id)>
                                    {{ $rekanan->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kategori-filter" class="form-label">Kategori</label>
                        <select id="kategori-filter" name="kategori" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $key => $label)
                                <option value="{{ $key }}" @selected($kategori === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_type" class="form-label">Filter tanggal nomor berdasarkan</label>
                        <select name="filter_type" id="filter_type" class="form-select">
                            <option value="">Tanpa filter tanggal</option>
                            <option value="range" @selected(($filters['filter_type'] ?? '') === 'range')>Rentang tanggal</option>
                            <option value="month" @selected(($filters['filter_type'] ?? '') === 'month')>Bulan dan tahun</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-3 date-range-filter">
                        <label for="start_date" class="form-label">Dari tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $filters['start_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3 date-range-filter">
                        <label for="end_date" class="form-label">Sampai tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $filters['end_date'] ?? '' }}">
                    </div>
                    <div class="col-md-3 month-filter">
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
                    <div class="col-md-3 month-filter">
                        <label for="year" class="form-label">Tahun</label>
                        <input type="number" name="year" id="year" class="form-control" min="1900" max="2200"
                            value="{{ $filters['year'] ?? '' }}" placeholder="YYYY">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="{{ route('laporan.pemakaian-nomor') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Notaris Rekanan</th>
                            <th>Kategori</th>
                            <th>Nomor</th>
                            <th>Job: nomor milik rekanan</th>
                            <th>Pelaporan: nomor milik {{ \App\Models\Setting::namaNotaris() }}</th>
                            <th>Jumlah di Job</th>
                            <th>Jumlah di Pelaporan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comparison as $row)
                            <tr>
                                <td>{{ $row['notaris_rekanan'] }}</td>
                                <td>{{ $categories[$row['kategori']] ?? $row['kategori'] }}</td>
                                <td class="fw-semibold">{{ $row['nomor'] }}</td>
                                <td>{{ $row['tanggal_job'] ?: '-' }}</td>
                                <td>{{ $row['tanggal_report'] ?: '-' }}</td>
                                <td>{{ $row['job_count'] }}</td>
                                <td>{{ $row['report_count'] }}</td>
                                <td>
                                    <span class="badge {{ str_starts_with($row['status'], 'Nomor sama') ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    Belum ada nomor rekanan untuk dibandingkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="small text-secondary">
                Baris yang cocok menunjukkan nilai nomor yang sama tercatat di kedua arah, tetapi kepemilikan
                nomornya berbeda. Ini bukan konfirmasi bahwa kedua catatan adalah nomor yang sama secara kepemilikan.
                Nomor yang hanya tercatat di satu arah ditandai terpisah.
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
