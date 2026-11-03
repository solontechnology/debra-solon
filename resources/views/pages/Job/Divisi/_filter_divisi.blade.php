<button type="button" class="btn btn-warning position-relative" data-bs-toggle="modal" data-bs-target="#modalFilterDivisi"
    title="Filter">
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

<div class="modal fade" id="modalFilterDivisi" tabindex="-1" aria-labelledby="modalFilterDivisiLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ $filterRoute ?? route('job.divisi.index') }}" method="GET">
                <input type="hidden" name="apply_filter" value="1">

                <div class="modal-header">
                    <div>
                        <h1 class="modal-title fs-5" id="modalFilterDivisiLabel">Filter Job Divisi</h1>
                        <div class="text-secondary small">Filter tersimpan sampai lu klik Reset Filter.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- Informasi Job --}}
                    <div class="text-secondary text-uppercase fw-bold small mb-2">Informasi Job</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="filter_kode" class="form-label">Kode</label>
                            <input type="text" id="filter_kode" class="form-control" name="kode"
                                value="{{ $filters['kode'] ?? '' }}" placeholder="Kode job">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_status" class="form-label">Status</label>
                            <select id="filter_status" name="status" class="form-select select2_filter">
                                <option value="">Semua status</option>
                                @foreach ($statusOptions as $opt)
                                    <option value="{{ $opt }}"
                                        {{ ($filters['status'] ?? '') === $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="filter_jenis_akad" class="form-label">Jenis Akad</label>
                            <select id="filter_jenis_akad" name="jenis_akad" class="form-select select2_filter">
                                <option value="">Semua jenis akad</option>
                                @foreach ($jenisAkadOptions as $opt)
                                    <option value="{{ $opt->id }}"
                                        {{ (string) ($filters['jenis_akad'] ?? '') === (string) $opt->id ? 'selected' : '' }}>
                                        {{ $opt->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="filter_bank" class="form-label">Bank</label>
                            <select id="filter_bank" name="bank" class="form-select select2_filter">
                                <option value="">Semua bank</option>
                                @foreach ($banks as $namaBank)
                                    <option value="{{ $namaBank }}"
                                        {{ (string) ($filters['bank'] ?? '') === (string) $namaBank ? 'selected' : '' }}>
                                        {{ $namaBank }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Pihak & Objek --}}
                    <div class="text-secondary text-uppercase fw-bold small mb-2">Pihak &amp; Objek</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="filter_nama_penghadap" class="form-label">Nama Penghadap</label>
                            <input type="text" id="filter_nama_penghadap" class="form-control" name="nama_penghadap"
                                value="{{ $filters['nama_penghadap'] ?? '' }}" placeholder="Nama penghadap">
                        </div>
                        <div class="col-md-6">
                            <label for="filter_objek" class="form-label">Objek</label>
                            <input type="text" id="filter_objek" class="form-control" name="objek"
                                value="{{ $filters['objek'] ?? '' }}" placeholder="Pemilik, no sertifikat, alamat">
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    {{-- Tanggal --}}
                    <div class="text-secondary text-uppercase fw-bold small mb-2">Tanggal</div>
                    <div class="mb-4">
                        @foreach ([['tanggal_akad', 'Tanggal Akad'], ['est_internal', 'Est. Internal'], ['est_eksternal', 'Est. Eksternal']] as [$p, $lbl])
                            @php
                                $mode = $filters[$p . '_mode'] ?? null ?: 'hari';
                                $type = ['hari' => 'date', 'bulan' => 'month', 'tahun' => 'number'][$mode];
                            @endphp
                            <div class="row g-3 align-items-end date-range {{ $loop->last ? '' : 'mb-3' }}">
                                <div class="col-md-4">
                                    <label for="filter_{{ $p }}_mode"
                                        class="form-label">{{ $lbl }}</label>
                                    <select id="filter_{{ $p }}_mode" name="{{ $p }}_mode"
                                        class="form-select range-mode">
                                        <option value="hari" {{ $mode === 'hari' ? 'selected' : '' }}>Per Hari
                                        </option>
                                        <option value="bulan" {{ $mode === 'bulan' ? 'selected' : '' }}>Per Bulan
                                        </option>
                                        <option value="tahun" {{ $mode === 'tahun' ? 'selected' : '' }}>Per Tahun
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small mb-1">Dari</label>
                                    <input type="{{ $type }}" name="{{ $p }}_dari"
                                        class="form-control range-input" value="{{ $filters[$p . '_dari'] ?? '' }}"
                                        @if ($mode === 'tahun') min="2000" max="2100" placeholder="2026" @endif>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small mb-1">Sampai</label>
                                    <input type="{{ $type }}" name="{{ $p }}_sampai"
                                        class="form-control range-input" value="{{ $filters[$p . '_sampai'] ?? '' }}"
                                        @if ($mode === 'tahun') min="2000" max="2100" placeholder="2026" @endif>
                                </div>
                            </div>
                        @endforeach
                        <div class="form-text mt-2">Isi salah satu (Dari atau Sampai) untuk satu periode saja.</div>
                    </div>

                    {{-- Petugas --}}
                    <div class="text-secondary text-uppercase fw-bold small mb-2">Petugas</div>
                    <div class="row g-3">
                        @foreach ([['penanggung_jawab', 'Penanggung Jawab'], ['dibuat_oleh', 'Dibuat Oleh']] as [$field, $label])
                            <div class="col-md-6">
                                <label for="filter_{{ $field }}"
                                    class="form-label">{{ $label }}</label>
                                <select id="filter_{{ $field }}" name="{{ $field }}"
                                    class="form-select select2_filter">
                                    <option value="">Semua</option>
                                    @foreach ($user as $u)
                                        <option value="{{ $u->id }}"
                                            {{ (string) ($filters[$field] ?? '') === (string) $u->id ? 'selected' : '' }}>
                                            {{ $u->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer d-flex align-items-center">
                    <a href="{{ ($filterRoute ?? route('job.divisi.index')) . '?reset_filter=1' }}"
                        class="btn btn-outline-warning me-auto">
                        Reset Filter
                    </a>
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> --}}
                    <button type="submit" class="btn btn-outline-primary">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('addStyle')
    <style>
        /* form jadi jembatan flex supaya modal-body bisa scroll dan footer tetap di bawah */
        #modalFilterDivisi .modal-content>form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            max-height: 100%;
        }

        #modalFilterDivisi .modal-footer {
            padding: 1rem 1.25rem !important;
            gap: .5rem;
        }

        #modalFilterDivisi .modal-footer>* {
            margin: 0 !important;
        }
    </style>
@endpush
@push('addScript')
    <script>
        $('#modalFilterDivisi').on('shown.bs.modal', function() {
            $(this).find('.select2_filter').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        dropdownParent: $('#modalFilterDivisi'),
                        theme: 'bootstrap-5'
                    });
                }
            });
        });
        $('#modalFilterDivisi').on('change', '.range-mode', function() {
            const mode = $(this).val();
            const types = {
                hari: 'date',
                bulan: 'month',
                tahun: 'number'
            };

            $(this).closest('.date-range').find('.range-input').each(function() {
                this.value = '';
                this.type = types[mode];
                if (mode === 'tahun') {
                    this.min = 2000;
                    this.max = 2100;
                    this.placeholder = '2026';
                } else {
                    this.removeAttribute('min');
                    this.removeAttribute('max');
                    this.removeAttribute('placeholder');
                }
            });
        });
    </script>
@endpush
