<button type="button" class="btn btn-warning position-relative" data-bs-toggle="modal"
    data-bs-target="#modalFilterPajak" title="Filter">
   <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-funnel me-1"
        viewBox="0 0 16 16">
        <path
            d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
    </svg>
    @if ($countFilter)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white">
            {{ $countFilter }}
        </span>
    @endif
</button>

<div class="modal fade" id="modalFilterPajak" tabindex="-1" aria-labelledby="modalFilterPajakLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('job.pajak.data.index') }}" method="GET">
                <input type="hidden" name="apply_filter" value="1">

                <div class="modal-header">
                    <div>
                        <h1 class="modal-title fs-5" id="modalFilterPajakLabel">Filter Pajak</h1>
                        <div class="text-secondary small">Filter tersimpan sampai lu klik Reset Filter.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pekerjaan</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="filter_parent" class="form-label">Parent</label>
                            <input type="text" id="filter_parent" class="form-control" name="parent"
                                value="{{ $filters['parent'] ?? '' }}" placeholder="Kode parent">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_proses" class="form-label">Proses</label>
                            <select id="filter_proses" name="proses" class="form-select select2_filter_pajak">
                                <option value="">Semua proses</option>
                                @foreach ($prosesOptions as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['proses'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="filter_status_akad" class="form-label">Status Akad</label>
                            <select id="filter_status_akad" name="status_akad" class="form-select select2_filter_pajak">
                                <option value="">Semua status akad</option>
                                @foreach ($statusAkadOptions as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['status_akad'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="filter_status" class="form-label">Status</label>
                            <select id="filter_status" name="status" class="form-select select2_filter_pajak">
                                <option value="">Semua status</option>
                                @foreach ($statusOptions as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['status'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pihak &amp; Objek</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="filter_nomor_objek" class="form-label">Nomor Objek</label>
                            <input type="text" id="filter_nomor_objek" class="form-control" name="nomor_objek"
                                value="{{ $filters['nomor_objek'] ?? '' }}" placeholder="No sertifikat">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_nama_debitur" class="form-label">Nama Debitur</label>
                            <input type="text" id="filter_nama_debitur" class="form-control" name="nama_debitur"
                                value="{{ $filters['nama_debitur'] ?? '' }}" placeholder="Nama debitur">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_nama_bank" class="form-label">Nama Bank</label>
                            <select id="filter_nama_bank" name="nama_bank" class="form-select select2_filter_pajak">
                                <option value="">Semua bank</option>
                                @foreach ($bankOptions as $namaBank)
                                    <option value="{{ $namaBank }}"
                                        {{ ($filters['nama_bank'] ?? '') === $namaBank ? 'selected' : '' }}>
                                        {{ $namaBank }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pembayaran</div>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="filter_kode_billing" class="form-label">Kode Billing</label>
                            <input type="text" id="filter_kode_billing" class="form-control" name="kode_billing"
                                value="{{ $filters['kode_billing'] ?? '' }}" placeholder="Kode billing">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_pembayaran_min" class="form-label">Pembayaran Minimal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" id="filter_pembayaran_min"
                                    class="form-control input-rupiah" name="pembayaran_min"
                                    value="{{ $filters['pembayaran_min'] ?? '' }}" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="filter_pembayaran_max" class="form-label">Pembayaran Maksimal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" id="filter_pembayaran_max"
                                    class="form-control input-rupiah" name="pembayaran_max"
                                    value="{{ $filters['pembayaran_max'] ?? '' }}" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex align-items-center">
                    <a href="{{ route('job.pajak.data.index', ['reset_filter' => 1]) }}"
                        class="btn btn-outline-warning me-auto">Reset Filter</a>
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> --}}
                    <button type="submit" class="btn btn-outline-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('addStyle')
    <style>
        #modalFilterPajak .modal-content > form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
        }
        #modalFilterPajak .modal-footer {
            padding: 1rem 1.25rem !important;
            gap: .5rem;
        }
        #modalFilterPajak .modal-footer > * {
            margin: 0 !important;
        }
    </style>
@endpush

@push('addScript')
    <script>
        $('#modalFilterPajak').on('shown.bs.modal', function() {
            $(this).find('.select2_filter_pajak').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $('#modalFilterPajak'),
                        theme: 'bootstrap-5'
                    });
                }
            });
        });

        // Format ribuan di input nominal, backend ambil angkanya aja
        $('#modalFilterPajak').on('input', '.input-rupiah', function() {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        });
    </script>
@endpush