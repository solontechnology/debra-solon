@php($exportDefinition = config("job_exports.$exportType"))

@if ($exportDefinition)
    <details class="position-relative">
        <summary class="btn btn-success d-inline-flex align-items-center gap-2 list-unstyled">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Export Excel</span>
        </summary>
        <form action="{{ route('job.data-export') }}" method="GET"
            class="position-absolute end-0 mt-2 p-3 bg-white border rounded shadow"
            style="z-index: 1050; width: min(680px, 90vw); max-height: 75vh; overflow-y: auto;">
            <input type="hidden" name="type" value="{{ $exportType }}">
            <div class="row g-2">
                @foreach ([
                    'q' => 'Pencarian umum',
                    'parent' => 'Kode parent',
                    'proses' => 'Proses',
                    'nomor_objek' => 'Nomor objek',
                    'nama_debitur' => 'Nama debitur',
                    'nama_bank' => 'Nama bank',
                    'status' => 'Status',
                    'status_akad' => 'Status akad',
                    'status_pengerjaan' => 'Status pengerjaan',
                    'nomor_akta' => 'Nomor akta',
                    'penugasan' => 'Nama petugas',
                    'penugasan_qc' => 'Nama petugas QC',
                ] as $filter => $label)
                    <div class="col-md-6">
                        <label class="form-label small mb-1" for="export-{{ $exportType }}-{{ $filter }}">{{ $label }}</label>
                        <input id="export-{{ $exportType }}-{{ $filter }}" class="form-control form-control-sm"
                            name="{{ $filter }}" value="{{ request($filter) }}">
                    </div>
                @endforeach
                <div class="col-md-6">
                    <label class="form-label small mb-1" for="export-{{ $exportType }}-tanggal_mulai">Tanggal mulai</label>
                    <input id="export-{{ $exportType }}-tanggal_mulai" type="date" class="form-control form-control-sm"
                        name="tanggal_mulai" value="{{ request('tanggal_mulai') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1" for="export-{{ $exportType }}-tanggal_selesai">Tanggal selesai</label>
                    <input id="export-{{ $exportType }}-tanggal_selesai" type="date" class="form-control form-control-sm"
                        name="tanggal_selesai" value="{{ request('tanggal_selesai') }}">
                </div>
            </div>
            <hr class="my-3">
            <fieldset>
                <legend class="fs-6">Kolom yang diekspor</legend>
                <div class="row g-2">
                    @foreach ($exportDefinition['fields'] as $field => $fieldLabel)
                        <div class="col-sm-6 col-lg-4">
                            <label class="form-check small">
                                <input class="form-check-input" type="checkbox" name="fields[]" value="{{ $field }}" checked>
                                <span class="form-check-label">{{ $fieldLabel }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </fieldset>
            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-success" type="submit">Unduh Excel</button>
            </div>
        </form>
    </details>
@endif
