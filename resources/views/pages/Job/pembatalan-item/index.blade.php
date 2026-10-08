@extends('layouts.admin')

@section('title')
    Pembatalan Item Job Divisi
@endsection

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Pembatalan Item</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Card Header dengan padding lega dan border bersih --}}
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold m-0 text-dark">Pembatalan Item Job Divisi</h5>
                <p class="text-muted small m-0">Kelola dan pantau seluruh data pembatalan item job divisi</p>
            </div>

            <div class="d-flex align-items-center gap-2">
                @include('pages.Job._export-data', ['exportType' => 'pembatalan-item'])
                {{-- Form Pencarian Data --}}
                <form action="{{ url()->current() }}" method="GET" class="m-0">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Cari Data...">
                        <button class="btn btn-outline-secondary d-inline-flex align-items-center" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                {{-- Modal Pembatalan Item Component --}}
                <div class="d-flex align-items-center">
                    @include('pages.Job.pembatalan-item._modal_pembatalan_item')
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-3 ps-4 text-secondary text-uppercase font-monospace small fw-bold">Parent</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Kode</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Proses</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Deitur</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Jenis Akad</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Bank</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Status</th>
                            <th class="py-3 text-secondary text-uppercase font-monospace small fw-bold">Tanggal</th>
                            <th class="py-3 pe-4 text-center text-secondary text-uppercase font-monospace small fw-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($items as $item)
                            <tr>
                                {{-- Parent --}}
                                <td class="ps-4 fw-semibold">
                                    <a href="{{ route('job.divisi.show', $item->jobDivisi->id) }}" class="text-decoration-none">
                                        <span class="text-primary">{{ $item->jobDivisi->kode ?? '-' }}</span>
                                    </a>
                                </td>

                                {{-- Kode --}}
                                <td class="fw-medium text-dark">
                                    {{ $item->kode ?? '-' }}
                                </td>
                                <td class="fw-medium text-dark">
                                    {{ $item->detail->first()->formOrder->nama ?? '-' }}
                                </td>
                                <td class="fw-medium text-dark">
                                    @forelse ($item->jobDivisi->debitur as $namaDebitur)
                                        <span>{{ $namaDebitur->nama }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td class="fw-medium text-dark">
                                    {{ $item->jobDivisi->jenisAkad->nama ?? '-' }}
                                </td>
                                <td class="fw-medium text-dark">
                                      @forelse ($item->jobDivisi->listBank as $bank)
                                            <span class="badge bg-light text-dark border">{{ $bank->nama_bank }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                </td>

                                {{-- Status --}}
                                <td>
                                    @php
                                        $statusClass = [
                                            'Pra Akad' => 'bg-info bg-opacity-10 text-info border border-info border-opacity-25',
                                            'Akad' => 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                                            'Pending' => 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25',
                                            'Batal Akad' => 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25',
                                            'Selesai' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                        ];
                                        $status = $item->status ?? '';
                                    @endphp

                                    <span
                                        class="badge rounded-pill {{ $statusClass[$status] ?? 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25' }} px-3 py-2 fw-medium text-uppercase"
                                        style="font-size: 0.75rem;">
                                        {{ $status ?: '-' }}
                                    </span>
                                </td>

                                {{-- Tanggal --}}
                                <td class="text-muted small">
                                    {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d-m-Y H:i') : '-' }}
                                </td>

                                {{-- Aksi --}}
                                <td class="pe-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <a href="{{ route('job.pembatalan-items.show', $item->id) }}"
                                            class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                            <i class="bi bi-eye"></i>
                                            <span>Detail</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                                            fill="currentColor" class="bi bi-inbox text-secondary mb-2 opacity-50"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z" />
                                        </svg>
                                        <span class="fw-medium">Belum ada data Pembatalan Item Job Divisi</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer Pagination --}}
        @if (method_exists($items, 'links'))
            <div class="card-footer bg-white border-top py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="text-muted small">
                    Menampilkan <strong>{{ $items->firstItem() ?? 0 }}</strong> -
                    <strong>{{ $items->lastItem() ?? 0 }}</strong> dari <strong>{{ $items->total() }}</strong> data
                </div>
                <div class="m-0">
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
@endsection