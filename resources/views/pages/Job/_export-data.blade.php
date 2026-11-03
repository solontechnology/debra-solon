@php
    $exportDefinition = config("job_exports.$exportType");
    $simpleTypes = ['divisi', 'notaris', 'ppat', 'legalisasi', 'waarmerking', 'surat-keluar', 'wasiat', 'covernot', 'operasional','pajak','pnbp'];
@endphp

@if ($exportDefinition && in_array($exportType, $simpleTypes, true))
    @php
        $visibleFields = collect($exportDefinition['fields'])
            ->filter(fn($f) => !is_array($f) || empty($f['permission']) || auth()->user()->can($f['permission']))
            ->map(fn($f) => is_array($f) ? $f['label'] : $f);
    @endphp

    <details class="position-relative export-dropdown">
        <summary class="btn btn-success d-inline-flex align-items-center gap-2 list-unstyled">
            <i class="bi bi-file-earmark-excel"></i>
            <span>Export Excel</span>
        </summary>
        <form action="{{ route('job.data-export') }}" method="GET"
            class="position-absolute end-0 mt-2 p-3 bg-white border rounded shadow"
            style="z-index: 1050; width: min(520px, 90vw); max-height: 75vh; overflow-y: auto;">
            <input type="hidden" name="type" value="{{ $exportType }}">
            <input type="hidden" name="scope" value="{{ $exportScope ?? 'aktif' }}">

            <div class="alert alert-info py-2 small mb-3">
                @if (($countFilter ?? 0) > 0)
                    Mengekspor data hasil filter ({{ $countFilter }} filter aktif).
                @else
                    Mengekspor <strong>semua data</strong>, belum ada filter aktif.
                @endif
            </div>

            <fieldset>
                <legend class="fs-6">Kolom yang diekspor</legend>
                <div class="row g-2">
                    @foreach ($visibleFields as $field => $fieldLabel)
                        <div class="col-sm-6">
                            <label class="form-check small">
                                <input class="form-check-input" type="checkbox" name="fields[]"
                                    value="{{ $field }}" checked>
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

@once
    @push('addScript')
        <script>
            document.addEventListener('click', function(e) {
                document.querySelectorAll('details.export-dropdown[open]').forEach(function(d) {
                    if (!d.contains(e.target)) d.removeAttribute('open');
                });
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('details.export-dropdown[open]').forEach(function(d) {
                        d.removeAttribute('open');
                    });
                }
            });
        </script>
    @endpush
@endonce