@php $actionUrl = $filterRoute ?? url()->current(); @endphp

<button type="button" class="btn btn-warning position-relative" data-bs-toggle="modal"
    data-bs-target="#modalFilterAkta" title="Filter">
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

<div class="modal fade" id="modalFilterAkta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ $actionUrl }}" method="GET">
                <input type="hidden" name="apply_filter" value="1">

                <div class="modal-header">
                    <div>
                        <h1 class="modal-title fs-5">Filter {{ ucfirst($tipe) }}</h1>
                        <div class="text-secondary small">Filter tersimpan sampai lu klik Reset Filter.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pekerjaan</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="f_parent" class="form-label">Parent</label>
                            <input type="text" id="f_parent" name="parent" class="form-control"
                                value="{{ $filters['parent'] ?? '' }}" placeholder="Kode job">
                        </div>
                        <div class="col-md-6">
                            <label for="f_proses" class="form-label">Proses</label>
                            <select id="f_proses" name="proses" class="form-select select2_filter_akta">
                                <option value="">Semua proses</option>
                                @foreach ($prosesOptions as $opt)
                                    <option value="{{ $opt }}" {{ ($filters['proses'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="f_nomor_akta" class="form-label">Nomor Akta</label>
                            <input type="text" id="f_nomor_akta" name="nomor_akta" class="form-control"
                                value="{{ $filters['nomor_akta'] ?? '' }}" placeholder="Nomor akta">
                        </div>
                        <div class="col-md-6">
                            <label for="f_status" class="form-label">Status</label>
                            <select id="f_status" name="status" class="form-select select2_filter_akta">
                                <option value="">Semua status</option>
                                @foreach ($statusOptions as $opt)
                                    <option value="{{ $opt }}" {{ ($filters['status'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Petugasan</div>
                    <div class="row g-3 mb-4">
                        @foreach ([['penugasan', 'Petugas'], ['penugasan_qc', 'QC']] as [$field, $label])
                            <div class="col-md-6">
                                <label for="f_{{ $field }}" class="form-label">{{ $label }}</label>
                                <select id="f_{{ $field }}" name="{{ $field }}" class="form-select select2_filter_akta">
                                    <option value="">Semua</option>
                                    @foreach ($user_ops as $u)
                                        <option value="{{ $u['value'] }}"
                                            {{ (string) ($filters[$field] ?? '') === (string) $u['value'] ? 'selected' : '' }}>
                                            {{ $u['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pihak, Objek &amp; Akad</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="f_nomor_objek" class="form-label">Nomor Objek</label>
                            <input type="text" id="f_nomor_objek" name="nomor_objek" class="form-control"
                                value="{{ $filters['nomor_objek'] ?? '' }}" placeholder="No sertifikat">
                        </div>
                        <div class="col-md-6">
                            <label for="f_nama_penghadap" class="form-label">Nama Penghadap</label>
                            <input type="text" id="f_nama_penghadap" name="nama_penghadap" class="form-control"
                                value="{{ $filters['nama_penghadap'] ?? '' }}" placeholder="Nama penghadap">
                        </div>
                        <div class="col-md-6">
                            <label for="f_bank" class="form-label">Nama Bank</label>
                            <select id="f_bank" name="bank" class="form-select select2_filter_akta">
                                <option value="">Semua bank</option>
                                @foreach ($bankOptions as $b)
                                    <option value="{{ $b->id }}"
                                        {{ (string) ($filters['bank'] ?? '') === (string) $b->id ? 'selected' : '' }}>
                                        {{ $b->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="f_status_akad" class="form-label">Status Akad</label>
                            <select id="f_status_akad" name="status_akad" class="form-select select2_filter_akta">
                                <option value="">Semua status akad</option>
                                @foreach ($statusAkadOptions as $opt)
                                    <option value="{{ $opt }}" {{ ($filters['status_akad'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex align-items-center">
                    <a href="{{ $actionUrl . '?reset_filter=1' }}" class="btn btn-outline-warning me-auto">Reset Filter</a>
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> --}}
                    <button type="submit" class="btn btn-outline-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('addStyle')
    <style>
        #modalFilterAkta .modal-content > form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
        }
        #modalFilterAkta .modal-footer {
            padding: 1rem 1.25rem !important;
            gap: .5rem;
        }
        #modalFilterAkta .modal-footer > * {
            margin: 0 !important;
        }
    </style>
@endpush

@push('addScript')
    <script>
        $('#modalFilterAkta').on('shown.bs.modal', function() {
            $(this).find('.select2_filter_akta').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $('#modalFilterAkta'),
                        theme: 'bootstrap-5'
                    });
                }
            });
        });
    </script>
@endpush