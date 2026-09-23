<!-- Card 1: Nomor PPAT Expired -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header bg-body-tertiary border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span
                class="bg-danger-subtle text-danger p-2 rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-x fs-5"></i>
            </span>
            <div>
                <h5 class="card-title fw-bold mb-0 text-dark">Mendekati Expired — Nomor PPAT</h5>
                <span class="text-secondary small">Daftar nomor PPAT yang akan kadaluarsa dalam 1 minggu ke depan</span>
            </div>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
            {{ $nomorPpats->where('kategori', '!=', 'covernot')->count() }} Data
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
                        <th scope="col" class="py-3">Tanggal Input</th>
                        <th scope="col" class="py-3">Tanggal Expired</th>
                        <th scope="col" class="pe-4 py-3 text-center" style="width: 160px;">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($nomorPpats->where('kategori', '!=', 'covernot') as $ppat)
                        <tr>
                            <td class="ps-4 fw-medium text-secondary">{{ $loop->iteration }}</td>
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
                            <td class="pe-4 text-center">
                                @if (\Carbon\Carbon::parse($ppat->tanggal_expired)->startOfDay()->lt(now()->startOfDay()))
                                    <span
                                        class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Silahkan Ambil Nomor Baru
                                    </span>
                                @else
                                    <span
                                        class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                        <i class="bi bi-clock-history"></i>
                                        Mendekati Expired
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="bg-success-subtle text-success d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                                        style="width: 30px; height: 30px;">
                                        <i class="bi bi-check-circle fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Semua Aman!</h6>
                                    <p class="text-muted small mb-0">Tidak ada data PPAT yang mendekati expired dalam 1
                                        minggu ke depan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Card 2: Nomor Covernot Expired -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <div class="card-header bg-body-tertiary border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span
                class="bg-warning-subtle text-warning p-2 rounded-3 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-text fs-5"></i>
            </span>
            <div>
                <h5 class="card-title fw-bold mb-0 text-dark">Mendekati Expired — Nomor Covernot</h5>
                <span class="text-secondary small">Daftar nomor Covernot yang akan kadaluarsa dalam 1 minggu ke
                    depan</span>
            </div>
        </div>
        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">
            {{ $nomorPpats->where('kategori', 'covernot')->count() }} Data
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
                        <th scope="col" class="py-3">Nomor</th>
                        <th scope="col" class="py-3">Tanggal Input</th>
                        <th scope="col" class="py-3">Tanggal Expired</th>
                        <th scope="col" class="pe-4 py-3 text-center" style="width: 160px;">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($nomorPpats->where('kategori', 'covernot') as $ppat)
                        {{-- {{dd($ppat->tanggal)}} --}}
                        <tr>
                            <td class="ps-4 fw-medium text-secondary">{{ $loop->iteration }}</td>
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
                            <td class="pe-4 text-center">
                                @if (\Carbon\Carbon::parse($ppat->tanggal_expired)->startOfDay()->lt(now()->startOfDay()))
                                    <span
                                        class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Silahkan Ambil Nomor Baru
                                    </span>
                                @else
                                    <span
                                        class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5 fw-medium">
                                        <i class="bi bi-clock-history"></i>
                                        Mendekati Expired
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="bg-success-subtle text-success d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                                        style="width: 30px; height: 30px;">
                                        <i class="bi bi-check-circle fs-3"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Semua Aman!</h6>
                                    <p class="text-muted small mb-0">Tidak ada data Covernot yang mendekati expired
                                        dalam 1 minggu ke depan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
