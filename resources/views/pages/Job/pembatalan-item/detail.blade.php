@extends('layouts.admin')

@section('title')
    Detail Pembatalan Item
@endsection

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job.pembatalan-items.index') }}" class="text-decoration-none">Pembatalan Item</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Detail Data</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Header & Navigasi Tab --}}
        <div class="card-header bg-white pt-3 px-4 pb-0 border-bottom">

            {{-- Tab Bar --}}
            <div class="pt-2 border-top">
                <ul class="nav nav-tabs border-0 gap-2" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-ringkasan" class="nav-link active fw-medium d-flex align-items-center gap-2 py-2 px-3" data-bs-toggle="tab" aria-selected="true" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 7m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                                <path d="M8 7v-2a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2" />
                                <path d="M12 12l0 .01" />
                                <path d="M3 13a20 20 0 0 0 18 0" />
                            </svg>
                            <span>Ringkasan Pembatalan</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#tabs-data-pendukung" class="nav-link fw-medium d-flex align-items-center gap-2 py-2 px-3" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M3 13v-8a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8" />
                                <path d="M3 10h18" />
                                <path d="M10 3v11" />
                                <path d="M2 22l5 -5" />
                                <path d="M7 21.5v-4.5h-4.5" />
                            </svg>
                            <span>Data Pendukung</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="tab-content">
                {{-- TAB 1: RINGKASAN --}}
                <div class="tab-pane active show" id="tabs-ringkasan" role="tabpanel">

                    {{-- Card Ringkasan Informasi Header --}}
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-medium mb-1">Parent Job</label>
                                <div class="fw-bold text-dark fs-6">{{ $jobDivisi->kode ?? '-' }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-medium mb-1">Proses / Akad</label>
                                <div class="fw-semibold text-dark">{{ $jobDivisi->jenisAkad->nama ?? '-' }}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted small fw-medium mb-1">Status Pengajuan</label>
                                <div>
                                    @php
                                        $status = strtolower($pembatalanItem->status ?? '');
                                        $badgeClass = match($status) {
                                            'disetujui', 'approved' => 'bg-success bg-opacity-10 text-success border-success',
                                            'ditolak', 'rejected' => 'bg-danger bg-opacity-10 text-danger border-danger',
                                            default => 'bg-warning bg-opacity-10 text-warning border-warning'
                                        };
                                    @endphp
                                    <span class="badge border px-2.5 py-1.5 fw-medium {{ $badgeClass }}">
                                        {{ strtoupper($pembatalanItem->status ?? 'MENUNGGU') }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted small fw-medium mb-1">Tanggal Pengajuan</label>
                                <div class="fw-medium text-dark">
                                    {{ isset($pembatalanItem->created_at) ? \Carbon\Carbon::parse($pembatalanItem->created_at)->format('d M Y H:i') : '-' }}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-muted small fw-medium mb-1">Diajukan Oleh</label>
                                <div class="fw-medium text-dark">{{ $pembatalanItem->user->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Detail Pekerjaan --}}
                    <div class="mb-4">
                        <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-dash-circle text-danger"></i>
                            <span>Daftar Item Pekerjaan Dibatalkan</span>
                        </h6>

                        <div class="table-responsive rounded-3 border">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50" class="text-center">No</th>
                                        <th>Nama Pekerjaan</th>
                                        <th width="200">Kategori</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pembatalanItem->detail as $index => $item)
                                        <tr>
                                            <td class="text-center text-muted fw-medium">{{ $index + 1 }}</td>
                                            <td class="fw-medium text-dark">
                                                {{ $item->pekerjaan->nama ?? '-' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border fw-normal text-uppercase">
                                                    {{ str_replace('_', ' ', $item->pekerjaan->kategori ?? '-') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                                Tidak ada item pekerjaan dalam pembatalan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div class="row">
                        <div class="col-lg-8">
                            <label class="form-label fw-medium text-dark">Keterangan Pembatalan</label>
                            <div class="p-3 bg-light rounded-3 border text-secondary">
                                {{ $pembatalanItem->keterangan ?: 'Tidak ada keterangan tambahan.' }}
                            </div>
                        </div>
                    </div>

                </div>

                {{-- TAB 2: DATA PENDUKUNG --}}
                <div class="tab-pane" id="tabs-data-pendukung" role="tabpanel">
                    @include('pages.Freeze._card_data_pendukung')
                </div>
            </div>

            {{-- Tombol Aksi Approval --}}
            @if (($pembatalanItem->status ?? '') === 'menunggu persetujuan')
                @if ($canApprove)
                    <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-end gap-2">
                        @include('pages.Job.pembatalan-item._modal_tolak')

                        <form action="{{ route('job.pembatalan-items.update', $pembatalanItem->id) }}" method="post" class="m-0">
                            @csrf
                            @method('PUT')
                            <button type="submit" name="approved" value="1" class="btn__submit btn btn-success d-inline-flex align-items-center gap-2 px-4 fw-medium shadow-sm">
                                <i class="bi bi-check-circle"></i>
                                <span>Setujui Pembatalan</span>
                            </button>
                        </form>
                    </div>
                @endif
            @endif

        </div>
    </div>
@endsection

@push('addScript')
    <script>
        $(document).ready(function() {
            $(".btn__submit").on("click", function() {
                if (typeof $(".loading__global").show === "function") {
                    $(".loading__global").show();
                }
            });
        });
    </script>
@endpush