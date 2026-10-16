@php
    $ppatNomorRows = $ppatItems->getCollection()->where('type', 'nomor')->pluck('item')->values();
    $ppatNewRows = $ppatItems->getCollection()->where('type', 'proses')->pluck('item')->values();
    $covernotNomorRows = $covernotItems->getCollection()->where('type', 'nomor')->pluck('item')->values();
    $covernotNewRows = $covernotItems->getCollection()->where('type', 'proses')->pluck('item')->values();
@endphp

<!-- Card 1: Nomor PPAT Expired -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header bg-body-tertiary border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span
                class="bg-danger-subtle text-danger p-2 rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-x fs-5"></i>
            </span>
            <div>
                <h5 class="card-title fw-bold mb-0 text-dark">Nomor PPAT — Mendekati atau Sudah Expired</h5>
                <span class="text-secondary small">Daftar nomor PPAT yang expired dan yang akan expired dalam 1 minggu ke
                    depan</span>
            </div>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
            <span id="ppat-data-count">{{ $ppatItems->total() }}</span> Data
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-secondary text-uppercase fs-7 fw-semibold border-bottom">
                    <tr>
                        <th scope="col" class="ps-4 py-3" style="width: 60px;">No</th>
                        <th scope="col" class="py-3">Parent</th>
                        <th scope="col" class="py-3">Proses</th>
                        <th scope="col" class="py-3">Nomor Akta</th>
                        <th scope="col" class="py-3">Nama Penghadap</th>
                        <th scope="col" class="py-3">Nomor Objek</th>
                        <th scope="col" class="py-3">Tanggal Input</th>
                        <th scope="col" class="py-3">Tanggal Expired</th>
                        <th scope="col" class="pe-4 py-3 text-center" style="width: 220px;">Status</th>
                        <th scope="col" class="py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y" id="limit-ppat-table-body">
                    @forelse($ppatNomorRows as $ppat)
                        <tr data-expired-id="{{ $ppat->id }}-{{ $ppat->tanggal_expired }}"
                            data-job-id="{{ $ppat->formOrder?->job_divisi_id }}" data-category="{{ $ppat->kategori }}"
                            data-expired-date="{{ $ppat->tanggal_expired }}">
                            <td class="ps-4 fw-medium text-secondary">
                                {{ $ppatItems->firstItem() + $loop->iteration - 1 }}</td>
                            <td>
                                <span
                                    class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                    {{ $ppat->formOrder->jobDivisi->kode ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $ppat->formOrder->nama ?? '-' }}</span>
                            </td>
                            <td>
                                <code class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">
                                    {{ $ppat->nomor }}
                                </code>
                            </td>
                            @php
                                $job = $ppat->formOrder?->jobDivisi;

                                $pihak = array_filter([
                                    'Debitur' => $job?->debitur?->pluck('nama')->filter()->join(', '),
                                    'Pembeli' => $job?->listPembeli?->pluck('nama')->filter()->join(', '),
                                    'Penjual' => $job?->listPenjual?->pluck('nama')->filter()->join(', '),
                                ]);
                            @endphp

                            <td>
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
                            <td>
                                <code class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">
                                    {{ $ppat->formOrder->objek->no_sertifikat ??
                                    $ppat->formOrder->jobDivisi->objek->pluck('no_sertifikat')->implode(', ') ?:
                                        '-' }}
                                </code>
                            </td>
                            <td class="text-secondary small">
                                <i class="bi bi-calendar-event me-1 text-muted"></i>
                                {{ $ppat->tanggal }}
                            </td>
                            <td>
                                <span class="text-danger fw-bold small">
                                    <i class="bi bi-clock-history me-1"></i>
                                    {{ \Carbon\Carbon::parse($ppat->tanggal_expired)->translatedFormat('d M Y') }}
                                </span>
                            </td>
                            @php $sudahExpired = \Carbon\Carbon::parse($ppat->tanggal_expired)->startOfDay()->lte(today()); @endphp
                            <td class="pe-4 text-center">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span
                                        class="badge {{ $sudahExpired ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' }} border d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                        <i class="bi {{ $sudahExpired ? 'bi-calendar-x' : 'bi-clock-history' }}"></i>
                                        {{ $sudahExpired ? 'Sudah Expired' : 'Mendekati Expired' }}
                                    </span>

                                </div>
                            </td>
                            {{-- resources/views/pages/dashboard/_aksi-expired.blade.php --}}
                            <td class="text-center" style="min-width: 210px;">
                                @if ($job && $job->status !== 'Batal Akad')
                                    @can('job/divisi/form-order/tambah-item')
                                        @if ($sudahExpired)
                                            {{-- Tahap 1 --}}
                                            <div class="expired-choice">
                                                <div
                                                    class="d-inline-flex align-items-center gap-3 ps-3 pe-2 py-2 rounded-pill bg-danger-subtle border border-danger-subtle">
                                                    <div class="text-start lh-sm">
                                                        <div class="text-danger fw-bold text-uppercase"
                                                            style="font-size: .65rem; letter-spacing: .08em;">
                                                            <i class="bi bi-exclamation-circle-fill me-1"></i>Expired
                                                        </div>
                                                        <div class="text-body-secondary fw-medium"
                                                            style="font-size: .8rem;">Perpanjang nomor?</div>
                                                    </div>

                                                    <div class="d-flex gap-1">
                                                        <button type="button" title="Ya, perpanjang"
                                                            class="btn btn-success rounded-circle p-0 shadow-sm expired-yes"
                                                            style="width: 32px; height: 32px;">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button type="button" title="Tidak"
                                                            class="btn btn-light border rounded-circle p-0 text-secondary expired-no"
                                                            style="width: 32px; height: 32px;">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif

                                        {{-- Tahap 2: muncul setelah klik "Ya" --}}
                                        <button type="button"
                                            class="btn btn-primary expired-yes expired-add-process d-none"
                                            data-nomor-id="{{ $ppat->id }}" data-category="{{ $ppat->kategori }}">
                                            {{-- <i class="bi bi-check-lg"></i>  --}}
                                            Tambah Proses
                                        </button>
                                    @endcan
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @if ($ppatItems->isEmpty())
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="bg-success-subtle text-success d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                                            style="width: 30px; height: 30px;">
                                            <i class="bi bi-check-circle fs-3"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Semua Aman!</h6>
                                        <p class="text-muted small mb-0">Tidak ada data PPAT yang mendekati expired
                                            dalam 1 minggu ke depan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforelse
                    @foreach ($ppatNewRows as $formOrder)
                        @php
                            $job = $formOrder->jobDivisi;
                            $pihak = array_filter([
                                'Debitur' => $job?->debitur?->pluck('nama')->filter()->join(', '),
                                'Pembeli' => $job?->listPembeli?->pluck('nama')->filter()->join(', '),
                                'Penjual' => $job?->listPenjual?->pluck('nama')->filter()->join(', '),
                            ]);
                            $key = 'dashboard_process_' . $formOrder->id;
                            $tipe = $formOrder->kategori;
                            $nama_proses = $formOrder->nama;
                        @endphp
                        <tr data-new-process-job-id="{{ $job?->id }}" data-category="{{ $formOrder->kategori }}"
                            data-created-at="{{ $formOrder->created_at?->format('Y-m-d H:i:s') }}">
                            <td class="ps-4 fw-medium text-secondary">
                                {{ $loop->iteration + $ppatNomorRows->count() + $ppatItems->firstItem() - 1 }}</td>
                            <td><span
                                    class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">{{ $job?->kode ?? '-' }}</span>
                            </td>
                            <td><span class="fw-semibold text-dark">{{ $formOrder->nama }}</span></td>
                            <td><span class="text-muted">Belum ada nomor</span></td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @forelse ($pihak as $peran => $nama)
                                        <span><small class="text-muted">{{ $peran }}:</small>
                                            {{ $nama }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td><code
                                    class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">{{ $formOrder->objek?->no_sertifikat ?? $job?->objek?->pluck('no_sertifikat')->implode(', ') ?: '-' }}</code>
                            </td>
                            <td class="text-secondary small"><i
                                    class="bi bi-calendar-event me-1 text-muted"></i>{{ $formOrder->created_at?->format('d M Y') ?? '-' }}
                            </td>
                            <td><span class="text-muted">-</span></td>
                            <td class="pe-4 text-center"><span
                                    class="badge bg-info-subtle text-info border border-info-subtle">Menunggu
                                    Nomor</span></td>
                            <td class="text-center">
                                @include('pages.Job.Akta._input-no-ppat', [
                                    'formOrder' => $formOrder,
                                    'key' => $key,
                                    'nama_proses' => $nama_proses,
                                    'tipe' => $tipe,
                                    'buttonLabel' => 'Silahkan ambil nomor',
                                ])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if ($ppatItems->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            <div class="d-flex justify-content-end m-0">
                {{ $ppatItems->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<!-- Card 2: Nomor Covernot Expired -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div
        class="card-header bg-body-tertiary border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span
                class="bg-warning-subtle text-warning p-2 rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-text fs-5"></i>
            </span>
            <div>
                <h5 class="card-title fw-bold mb-0 text-dark">Mendekati Expired — Nomor Cover note</h5>
                <span class="text-secondary small">Daftar nomor Cover note yang expired, mendekati expired, atau
                    menunggu nomor</span>
            </div>
        </div>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">
            <span id="covernot-data-count">{{ $covernotItems->total() }}</span> Data
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-tertiary text-secondary text-uppercase fs-7 fw-semibold border-bottom">
                    <tr>
                        <th scope="col" class="ps-4 py-3" style="width: 60px;">No</th>
                        <th scope="col" class="py-3">Parent</th>
                        <th scope="col" class="py-3">Proses</th>
                        <th scope="col" class="py-3">Nomor Akta</th>
                        <th scope="col" class="py-3">Nama Penghadap</th>
                        <th scope="col" class="py-3">Nomor Objek</th>
                        <th scope="col" class="py-3">Tanggal Input</th>
                        <th scope="col" class="py-3">Tanggal Expired</th>
                        <th scope="col" class="pe-4 py-3 text-center" style="width: 220px;">Status</th>
                        <th scope="col" class="py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y" id="limit-covernot-table-body">
                    @if ($covernotItems->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="bg-success-subtle text-success d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                                        style="width: 30px; height: 30px;">
                                        <i class="bi bi-check-circle fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Semua Aman!</h6>
                                    <p class="text-muted small mb-0">Tidak ada data Cover note yang mendekati expired
                                        atau menunggu nomor.</p>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach ($covernotNomorRows as $ppat)
                            {{-- {{dd($ppat->tanggal)}} --}}
                            <tr data-expired-id="{{ $ppat->id }}-{{ $ppat->tanggal_expired }}"
                                data-job-id="{{ $ppat->formOrder?->job_divisi_id }}"
                                data-category="{{ $ppat->kategori }}"
                                data-expired-date="{{ $ppat->tanggal_expired }}">
                                <td class="ps-4 fw-medium text-secondary">
                                    {{ $covernotItems->firstItem() + $loop->iteration - 1 }}</td>
                                <td>
                                    <span
                                        class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                        {{ $ppat->formOrder->jobDivisi->kode ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="fw-semibold text-dark">{{ $ppat->formOrder->nama === 'Covernot' ? 'Cover note' : '-' }}</span>
                                </td>
                                <td>
                                    <code class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">
                                        {{ $ppat->nomor }}
                                    </code>
                                </td>
                                @php
                                    $job = $ppat->formOrder?->jobDivisi;

                                    $pihak = array_filter([
                                        'Debitur' => $job?->debitur?->pluck('nama')->filter()->join(', '),
                                        'Pembeli' => $job?->listPembeli?->pluck('nama')->filter()->join(', '),
                                        'Penjual' => $job?->listPenjual?->pluck('nama')->filter()->join(', '),
                                    ]);
                                @endphp

                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @forelse ($pihak as $peran => $nama)
                                            <span>
                                                <small class="text-muted">{{ $peran }}:</small>
                                                {{ $nama }}
                                            </span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <code class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">
                                        {{ $ppat->formOrder->objek->no_sertifikat ??
                                        $ppat->formOrder->jobDivisi->objek->pluck('no_sertifikat')->implode(', ') ?:
                                            '-' }}
                                    </code>
                                </td>
                                <td class="text-secondary small">
                                    <i class="bi bi-calendar-event me-1 text-muted"></i>
                                    {{ $ppat->tanggal }}
                                </td>
                                <td>
                                    <span class="text-danger fw-bold small">
                                        <i class="bi bi-clock-history me-1"></i>
                                        {{ \Carbon\Carbon::parse($ppat->tanggal_expired)->translatedFormat('d M Y') }}
                                    </span>
                                </td>
                                @php $sudahExpired = \Carbon\Carbon::parse($ppat->tanggal_expired)->startOfDay()->lte(today()); @endphp
                                <td class="pe-4 text-center">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <span
                                            class="badge {{ $sudahExpired ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' }} border d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                            <i
                                                class="bi {{ $sudahExpired ? 'bi-calendar-x' : 'bi-clock-history' }}"></i>
                                            {{ $sudahExpired ? 'Sudah Expired' : 'Mendekati Expired' }}
                                        </span>

                                    </div>
                                </td>
                                <td class="text-center" style="min-width: 210px;">
                                    @if ($job && $job->status !== 'Batal Akad')
                                        @can('job/divisi/form-order/tambah-item')
                                            @if ($sudahExpired)
                                                {{-- Tahap 1 --}}
                                                <div class="expired-choice">
                                                    <div
                                                        class="d-inline-flex align-items-center gap-3 ps-3 pe-2 py-2 rounded-pill bg-danger-subtle border border-danger-subtle">
                                                        <div class="text-start lh-sm">
                                                            <div class="text-danger fw-bold text-uppercase"
                                                                style="font-size: .65rem; letter-spacing: .08em;">
                                                                <i class="bi bi-exclamation-circle-fill me-1"></i>Expired
                                                            </div>
                                                            <div class="text-body-secondary fw-medium"
                                                                style="font-size: .8rem;">Perpanjang nomor?</div>
                                                        </div>

                                                        <div class="d-flex gap-1">
                                                            <button type="button" title="Ya, perpanjang"
                                                                class="btn btn-success rounded-circle p-0 shadow-sm expired-yes"
                                                                style="width: 32px; height: 32px;">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                            <button type="button" title="Tidak"
                                                                class="btn btn-light border rounded-circle p-0 text-secondary expired-no"
                                                                style="width: 32px; height: 32px;">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                            <button type="button"
                                                class="btn btn-primary expired-yes expired-add-process d-none"
                                                data-nomor-id="{{ $ppat->id }}"
                                                data-category="{{ $ppat->kategori }}">
                                                {{-- <i class="bi bi-check-lg"></i>  --}}
                                                Tambah Proses
                                            </button>
                                        @endcan
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @foreach ($covernotNewRows as $formOrder)
                            @php
                                $job = $formOrder->jobDivisi;
                                $pihak = array_filter([
                                    'Debitur' => $job?->debitur?->pluck('nama')->filter()->join(', '),
                                    'Pembeli' => $job?->listPembeli?->pluck('nama')->filter()->join(', '),
                                    'Penjual' => $job?->listPenjual?->pluck('nama')->filter()->join(', '),
                                ]);
                                $key = 'dashboard_covernot_' . $formOrder->id;
                                $tipe = 'covernot';
                                $nama_proses = $formOrder->nama;
                            @endphp
                            <tr data-new-process-job-id="{{ $job?->id }}"
                                data-category="{{ $formOrder->kategori }}"
                                data-created-at="{{ $formOrder->created_at?->format('Y-m-d H:i:s') }}">
                                <td class="ps-4 fw-medium text-secondary">
                                    {{ $loop->iteration + $covernotNomorRows->count() + $covernotItems->firstItem() - 1 }}
                                </td>
                                <td><span
                                        class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">{{ $job?->kode ?? '-' }}</span>
                                </td>
                                <td><span
                                        class="fw-semibold text-dark">{{ $formOrder->nama === 'Covernot' ? 'Cover note' : $formOrder->nama }}</span>
                                </td>
                                <td><span class="text-muted">Belum ada nomor</span></td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @forelse ($pihak as $peran => $nama)
                                            <span><small class="text-muted">{{ $peran }}:</small>
                                                {{ $nama }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td><code
                                        class="fw-bold text-dark fs-6 bg-light px-2 py-1 rounded border">{{ $formOrder->objek?->no_sertifikat ?? $job?->objek?->pluck('no_sertifikat')->implode(', ') ?: '-' }}</code>
                                </td>
                                <td class="text-secondary small"><i
                                        class="bi bi-calendar-event me-1 text-muted"></i>{{ $formOrder->created_at?->format('d M Y') ?? '-' }}
                                </td>
                                <td><span class="text-muted">-</span></td>
                                <td class="pe-4 text-center"><span
                                        class="badge bg-info-subtle text-info border border-info-subtle">Menunggu
                                        Nomor</span></td>
                                <td class="text-center">
                                    @include('pages.Job.Akta._input-no-ppat', [
                                        'formOrder' => $formOrder,
                                        'key' => $key,
                                        'nama_proses' => $nama_proses,
                                        'tipe' => $tipe,
                                        'buttonLabel' => 'Silahkan ambil nomor',
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @if ($covernotItems->hasPages())
        <div class="card-footer bg-white border-top py-3 px-4">
            <div class="d-flex justify-content-end m-0">
                {{ $covernotItems->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

<div class="modal fade" id="modalKonfirmasiPerpanjangan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-arrow-repeat text-primary" style="font-size: 3rem;"></i>
                </div>
                <span class="fw-bold">
                    {{ $ppat->formOrder->jobDivisi->kode }}
                </span>

                <h5 class="fw-bold">Tambah Data Perpanjangan proses
                    <span
                        class="fw-semibold text-dark">{{ $ppat->formOrder?->nama === 'Covernot' ? 'Cover note' : $ppat->formOrder?->nama ?? '-' }}
                    </span>
                </h5>
                <p class="text-secondary mb-4">
                    Sistem akan membuat Form Order baru berdasarkan
                    data nomor yang dipilih.
                </p>

                <form action="{{ route('job.nomor.perpanjang') }}" method="POST" id="formPerpanjangan">
                    @csrf
                    <input type="hidden" name="nomor_ppat_id" id="perpanjangan_nomor_id">

                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-primary" id="btnKonfirmasiPerpanjangan">
                        <i class="bi bi-plus-circle me-1"></i>
                        Tambah Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('addScript')
    <script>
        const choiceStorageKey = 'dashboard-expired-nomor-choice-{{ auth()->id() }}';
        const storedChoices = JSON.parse(localStorage.getItem(choiceStorageKey) || '{}');

        let ppatCount = {{ $ppatItems->total() }};
        let covernotCount = {{ $covernotItems->total() }};
        const updateDashboardCounts = function() {
            const covernotCountElement = document.getElementById('covernot-data-count');
            const ppatCountElement = document.getElementById('ppat-data-count');
            if (covernotCountElement) covernotCountElement.textContent = covernotCount;
            if (ppatCountElement) ppatCountElement.textContent = ppatCount;
        };

        document.querySelectorAll('tr[data-expired-id]').forEach(function(row) {
            const choice = storedChoices[row.dataset.expiredId];
            if (choice === 'no') {
                row.remove();
                if (row.dataset.category === 'covernot') covernotCount--;
                else ppatCount--;
            } else if (choice === 'yes') {
                row.querySelector('.expired-choice')?.classList.add('d-none');
                row.querySelector('.expired-add-process')?.classList.remove('d-none');
            }
        });
        localStorage.setItem(choiceStorageKey, JSON.stringify(storedChoices));
        updateDashboardCounts();

        document.addEventListener('click', function(event) {
            if (event.target.closest('.expired-yes')) {
                const row = event.target.closest('tr');
                storedChoices[row.dataset.expiredId] = 'yes';
                row.querySelector('.expired-choice')?.classList.add('d-none');
                row.querySelector('.expired-add-process')?.classList.remove('d-none');
                localStorage.setItem(choiceStorageKey, JSON.stringify(storedChoices));
                updateDashboardCounts();
            }

            if (event.target.closest('.expired-no')) {
                const row = event.target.closest('tr');
                storedChoices[row.dataset.expiredId] = 'no';
                localStorage.setItem(choiceStorageKey, JSON.stringify(storedChoices));
                row.remove();
                if (row.dataset.category === 'covernot') covernotCount--;
                else ppatCount--;
                updateDashboardCounts();
            }
        });
        document.addEventListener('click', function(event) {
            const button = event.target.closest('.expired-yes');

            if (!button) return;

            const row = button.closest('tr');
            const nomorId = button.dataset.nomorId;

            if (!nomorId || !row) return;

            document.getElementById('perpanjangan_nomor_id').value = nomorId;

            const modal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalKonfirmasiPerpanjangan')
            );

            modal.show();
        });

        document.getElementById('formPerpanjangan')
            ?.addEventListener('submit', function() {
                const button = document.getElementById('btnKonfirmasiPerpanjangan');

                // Cegah submit berulang
                if (button.disabled) return;

                button.disabled = true;
                button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-1"
                  role="status"
                  aria-hidden="true"></span>
            Memproses...
        `;

                displayLoading(true);
            });
    </script>
@endpush
