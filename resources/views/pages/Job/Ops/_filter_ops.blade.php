<button type="button" class="btn btn-warning position-relative" data-bs-toggle="modal"
    data-bs-target="#modalFilterOps" title="Filter">
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

<div class="modal fade" id="modalFilterOps" tabindex="-1" aria-labelledby="modalFilterOpsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('job.ops.data.index') }}" method="GET">
                <input type="hidden" name="apply_filter" value="1">

                <div class="modal-header">
                    <div>
                        <h1 class="modal-title fs-5" id="modalFilterOpsLabel">Filter OPS</h1>
                        <div class="text-secondary small">Filter tersimpan sampai lu klik Reset Filter.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pekerjaan</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="ops_parent" class="form-label">Kode/ID Parent</label>
                            <input type="text" id="ops_parent" class="form-control" name="parent"
                                value="{{ $filters['parent'] ?? '' }}" placeholder="Kode parent">
                        </div>
                        <div class="col-md-6">
                            <label for="ops_proses" class="form-label">Proses</label>
                            <select id="ops_proses" name="proses" class="form-select select_2_ops"
                                data-placeholder="Semua proses">
                                <option value=""></option>
                                @foreach ($prosesList as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['proses'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ops_status_akad" class="form-label">Status Akad</label>
                            <select id="ops_status_akad" name="status_akad" class="form-select select_2_ops"
                                data-placeholder="Semua status akad">
                                <option value=""></option>
                                @foreach (['Pra Akad', 'Akad'] as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['status_akad'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ops_status_pengerjaan" class="form-label">Status Pengerjaan</label>
                            <select id="ops_status_pengerjaan" name="status_pengerjaan"
                                class="form-select select_2_ops" data-placeholder="Semua status pengerjaan">
                                <option value=""></option>
                                @foreach (['belum dikerjakan' => 'Belum Dikerjakan', 'Penugasan' => 'Penugasan', 'Selesai' => 'Selesai', 'dispo' => 'Dispo'] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ ($filters['status_pengerjaan'] ?? '') === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pihak &amp; Objek</div>
                    <div class="row g-3 {{ auth()->user()->can('job/ops/biaya proses') ? 'mb-4' : '' }}">
                        <div class="col-md-6">
                            <label for="ops_nomor_objek" class="form-label">Nomor Objek</label>
                            <input type="text" id="ops_nomor_objek" class="form-control" name="nomor_objek"
                                value="{{ $filters['nomor_objek'] ?? '' }}" placeholder="No sertifikat">
                        </div>
                        <div class="col-md-6">
                            <label for="ops_nama_penghadap" class="form-label">Nama Penghadap</label>
                            <input type="text" id="ops_nama_penghadap" class="form-control" name="nama_penghadap"
                                value="{{ $filters['nama_penghadap'] ?? '' }}" placeholder="Nama penghadap">
                        </div>
                        <div class="col-md-6">
                            <label for="ops_bank" class="form-label">Nama Bank</label>
                            <select id="ops_bank" name="bank" class="form-select select_2_ops"
                                data-placeholder="Semua bank">
                                <option value=""></option>
                                @foreach ($bankList as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['bank'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @can('job/ops/biaya proses')
                        <div class="text-secondary text-uppercase fw-bold small mb-2">Biaya</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="ops_biaya_min" class="form-label">Biaya Proses Minimal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" id="ops_biaya_min"
                                        class="form-control input-rupiah" name="biaya_min"
                                        value="{{ $filters['biaya_min'] ?? '' }}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="ops_biaya_max" class="form-label">Biaya Proses Maksimal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" id="ops_biaya_max"
                                        class="form-control input-rupiah" name="biaya_max"
                                        value="{{ $filters['biaya_max'] ?? '' }}" placeholder="0">
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="modal-footer d-flex align-items-center">
                    <a href="{{ route('job.ops.data.index', ['reset_filter' => 1]) }}"
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
        #modalFilterOps .modal-content > form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
        }
        #modalFilterOps .modal-footer {
            padding: 1rem 1.25rem !important;
            gap: .5rem;
        }
        #modalFilterOps .modal-footer > * {
            margin: 0 !important;
        }
    </style>
@endpush

@push('addScript')
    <script>
        $('#modalFilterOps').on('shown.bs.modal', function() {
            $(this).find('.select_2_ops').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        allowClear: true,
                        placeholder: $(this).data('placeholder'),
                        dropdownParent: $('#modalFilterOps'),
                        theme: 'bootstrap-5'
                    });
                }
            });
        });

        $('#modalFilterOps').on('input', '.input-rupiah', function() {
            const digits = this.value.replace(/\D/g, '');
            this.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        });
    </script>
@endpush