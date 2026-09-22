@extends('layouts.admin')

@section('title')
    History Job Divisi
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">
                    <i class="bi bi-clock-history me-2 text-primary"></i>
                    History Job Divisi
                </h5>
                <small class="text-muted">
                    Daftar seluruh pekerjaan yang telah selesai.
                </small>
            </div>
        </div>
        <div class="card-header">
            @include('pages.laporan.HistoryJobDivisi.Home._modal_select_history_job_divisi')
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead>
                        <tr>
                            <th width="15%">ID Job</th>
                            <th>Paket Pekerjaan</th>
                            <th>Status</th>
                            <th>Yang Menjalankan Akad</th>
                            <th>Penanggung Jawab Berkas</th>
                            <th>Tanggal Akad</th>
                            <th>Tanggal selesai</th>
                            <th width="8%" class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="border-top-0">

                        @forelse($items as $item)
                            {{-- Check if status is Selesai or Batal Akad --}}
                            @if (strtolower($item->status ?? '') === 'selesai' || strtolower($item->status ?? '') === 'batal akad')
                                <tr>

                                    {{-- 1. ID JOB --}}
                                    <td class="ps-4 fw-semibold">
                                        {{-- $item->id is passed securely here to the route --}}
                                        <a href="{{ route('laporan.list-pekerjaan-job-divisi.detail', $item->id) }}" class="text-primary text-decoration-none">
                                            {{ $item->kode }}
                                        </a>
                                    </td>

                                    {{-- 2. PAKET PEKERJAAN --}}
                                    <td>
                                        <span class="badge bg-success-lt text-success">
                                            {{ $item->jenisAkad->nama ?? '-' }}
                                        </span>
                                    </td>

                                    {{-- 3. STATUS --}}
                                    <td>
                                        @php
                                            $statusClass = [
                                                'Pra Akad' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                                'Akad' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                                'Pending' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                                'Batal Akad' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                                'Selesai' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                            ];
                                            
                                            // Fallback in case $item->status is null
                                            $currentStatus = $item->status ?? 'Unknown';
                                        @endphp
                                        <span class="badge rounded-pill {{ $statusClass[$currentStatus] ?? 'bg-secondary' }} px-3 py-2 fw-medium text-uppercase" style="font-size: 0.75rem;">
                                            {{ $item->is_pending ? 'Pending' : $currentStatus }}
                                        </span>
                                    </td>

                                    {{-- 4. YANG MENJALANKAN AKAD --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-check text-primary me-2"></i>
                                            {{ $item->perwakilanAkad->name ?? '-' }}
                                        </div>
                                    </td>

                                    {{-- 5. PENANGGUNG JAWAB BERKAS --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-workspace text-success me-2"></i>
                                            {{ $item->penanggungJawab->name ?? '-' }}
                                        </div>
                                    </td>

                                    {{-- 6. TANGGAL AKAD --}}
                                    <td>
                                        <i class="bi bi-calendar-event text-muted me-1"></i>
                                        {{ $item->tanggal_akad?->format('d M Y') ?? '-' }}
                                    </td>

                                    {{-- 7. TANGGAL SELESAI --}}
                                    <td>
                                        <i class="bi bi-calendar-check text-success me-1"></i>
                                        {{ $item->updated_at?->format('d M Y') ?? '-' }}
                                    </td>

                                    {{-- 8. AKSI --}}
                                    <td class="pe-4 text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            {{-- Tombol Detail with $item->id --}}
                                            <a href="{{ route('laporan.list-pekerjaan-job-divisi.detail', $item->id) }}"
                                                class="btn btn-outline-info d-inline-flex align-items-center gap-1 px-3 py-1.5 shadow-sm fw-medium"
                                                title="Lihat Detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                    fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            @endif

                        @empty

                            {{-- EMPTY STATE --}}
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                            fill="currentColor" class="bi bi-inbox text-secondary mb-2 opacity-50"
                                            viewBox="0 0 16 16">
                                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z" />
                                        </svg>
                                        <span class="fw-medium">Belum ada data Job Divisi</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>

            <div class="mt-4">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>

        </div>
    </div>
@endsection