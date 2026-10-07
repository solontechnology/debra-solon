<!-- Button Trigger Modal -->
<button type="button"
    class="btn btn-outline-info d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm fw-medium rounded-3"
    data-bs-toggle="modal" data-bs-target="#modalPPat{{ $key }}">
    <i class="bi bi-file-earmark-text fs-6"></i>
    <span>Nomor {{ $tipe==='covernot' ? 'Cover note' : strtoupper($tipe) }}</span>
</button>

<!-- Modal -->
<div class="modal fade" id="modalPPat{{ $key }}" tabindex="-1" aria-labelledby="modalPPat{{ $key }}Label"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            {{-- Header Modal --}}
            <div class="modal-header bg-body-tertiary border-bottom py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-1" id="modalPPat{{ $key }}Label">
                        Form Penginputan Nomor {{ $tipe==='covernot' ? 'Cover note' : strtoupper($tipe) }}
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-semibold text-uppercase font-monospace"
                            style="font-size: 0.70rem;">
                            Proses: {{ $nama_proses ==='Covernot' ? 'Cover note' : strtoupper($nama_proses) }}
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            {{-- Body Modal --}}
            <div class="modal-body p-4">
                <form action="{{ route('job.notaris.simpanNomorPPAT') }}" id="form_ppat{{ $key }}"
                    method="post">
                    @csrf
                    <input type="hidden" value="{{ $formOrder->id }}" name="form_id">
                    <input type="hidden" value="{{ $formOrder->kategori }}" name="kategori">

                    @php
                        $penomoranSetting = \App\Models\PenomoranSetting::where(
                            'kategori',
                            $formOrder->kategori,
                        )->first();
                    @endphp

                    {{-- Section 1: Input / Display Nomor --}}
                    @if ($formOrder->nomorPpat)
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small mb-1">
                                Nomor {{ $tipe === 'covernot' ? 'Cover note' : strtoupper($tipe) }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i class="bi bi-hash"></i></span>
                                <input type="text" class="form-control bg-light text-dark fw-semibold"
                                    value="{{ $formOrder->nomorPpat->nomor ?? '' }}" name="nomor" readonly>
                            </div>
                            <div class="form-text mt-1 text-muted small d-flex align-items-center gap-1">
                                <i class="bi bi-clock-history"></i>
                                Dibuat pada:
                                {{ \Carbon\Carbon::parse($formOrder->nomorPpat->tanggal)->format('d M Y') }}
                            </div>
                            @if ($formOrder->nomorPpat->notarisRekanan)
                                <div class="form-text mt-1">
                                    Notaris rekanan: <strong>{{ $formOrder->nomorPpat->notarisRekanan->nama }}</strong>
                                </div>
                            @endif
                        </div>
                    @elseif ($penomoranSetting?->mode === 'manual')
                        <div class="mb-3 form_manual_nomor">
                            <label class="form-label fw-semibold text-secondary small mb-1 required">
                                Nomor {{ $tipe === 'covernot' ? 'Cover note' : strtoupper($tipe) }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i
                                        class="bi bi-pencil"></i></span>
                                <input type="text" autocomplete="off" class="form-control" name="nomor"
                                    placeholder="Masukkan nomor {{ $tipe === 'covernot' ? 'Cover note' : strtoupper($tipe) }} rekanan">
                            </div>
                            <div class="form-text mt-1 text-muted small">
                                <i class="bi bi-info-circle me-1"></i> Penomoran untuk kategori ini diatur secara
                                <strong>Manual</strong>.
                                Nomor ini milik {{ \App\Models\Setting::namaNotaris() }}.
                            </div>
                        </div>
                    @else
                        {{-- Mode Otomatis --}}
                        <div class="system_number_info alert alert-info d-flex align-items-center gap-2 mb-3 p-2 rounded-3 text-break overflow-hidden"
                            role="alert">
                            <i class="bi bi-magic fs-6 text-info flex-shrink-0"></i>
                            <div class="lh-sm text-break" style="font-size: 0.725rem;">
                                Nomor {{$tipe==='covernot' ? 'Cover note' : strtoupper($tipe) }} akan <strong>di-generate otomatis</strong> saat disimpan.
                            </div>
                        </div>
                    @endif

                    {{-- Section 2: Input Tanggal --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1 required">
                                Tanggal {{ $tipe==='covernot' ? 'Cover note' : strtoupper($tipe) }}
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i
                                        class="bi bi-calendar-event"></i></span>
                                <input type="date" autocomplete="off" value="{{ now()->format('Y-m-d') }}"
                                    class="form-control" name="tanggal_nomor">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">
                                Tanggal Expired
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i
                                        class="bi bi-calendar-x"></i></span>
                                <input type="date" autocomplete="off"
                                    value="{{ $formOrder->nomorPpat->tanggal_expired ?? '' }}" class="form-control"
                                    name="tanggal_expired">
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Switch Notaris Rekanan --}}
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="form-check form-switch mb-0">
                            @php
                                $rekanan = $formOrder->nomorPpat->rekanan ?? 0;
                            @endphp
                            <input class="form-check-input" name="rekanan" value="1" {{ $rekanan ? 'checked' : '' }}
                                type="checkbox" role="switch" id="switchCheckNotarisRekanan{{ $key }}">
                            <label class="form-check-label fw-semibold text-dark ms-2"
                                for="switchCheckNotarisRekanan{{ $key }}">
                                Notaris Rekanan
                            </label>
                        </div>
                    </div>

                    {{-- Section 4: Input Nomor Rekanan (Dynamic) --}}
                    @if (!$formOrder->nomorPpat)
                        <div class="mb-3 form_rekanan" style="display: none;">
                            <label class="form-label fw-semibold text-secondary small mb-1 required">
                                Notaris Rekanan
                            </label>
                            <select class="form-select select2-notaris-rekanan" name="notaris_rekanan_id">
                                <option value="">Pilih notaris rekanan</option>
                                @foreach ($notarisRekananOptions as $notarisRekanan)
                                    <option value="{{ $notarisRekanan->id }}">
                                        {{ $notarisRekanan->nama }}{{ $notarisRekanan->kota ? ' — ' . $notarisRekanan->kota->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 form_rekanan" style="display: none;">
                            <label class="form-label fw-semibold text-secondary small mb-1 required">
                                Nomor {{ $tipe === 'covernot' ? 'Cover note' : strtoupper($tipe) }} Rekanan
                            </label>
                            <div class="form-text mb-2">
                                Nomor ini milik notaris rekanan dan dipakai pada job kantor Anda. Pengambilan otomatis dari database rekanan belum terhubung; masukkan nomor yang diberikan rekanan.
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i
                                        class="bi bi-journal-text"></i></span>
                                <input type="text" autocomplete="off" class="form-control" name="nomor_rekanan"
                                    placeholder="Masukkan nomor {{ $tipe === 'covernot' ? 'Cover note' : strtoupper($tipe) }}">
                            </div>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Footer Modal --}}
            <div class="modal-footer bg-body-tertiary border-top py-3 px-4">
                <button type="button" class="btn btn-outline-secondary px-4 fw-medium rounded-2"
                    data-bs-dismiss="modal">
                    Batal
                </button>
                @if (!$formOrder->nomorPpat)
                    <button type="button"
                        class="btn btn-primary px-4 fw-medium shadow-sm d-inline-flex align-items-center gap-2 rounded-2 btn__simpant_ppt{{ $key }}">
                        <i class="bi bi-check-lg"></i>
                        <span>Simpan Data</span>
                    </button>
                @endif
            </div>

        </div>
    </div>
</div>

@push('addScript')
    <script>
        $(document).ready(function() {
            // Event submit form
            $(".btn__simpant_ppt{{ $key }}").on("click", function() {
                $(".loading__global").show();
                $("#form_ppat{{ $key }}").submit();
            });

            @if (!$formOrder->nomorPpat)
                // PENTING: Diberikan scope ID #form_ppat{{ $key }} agar tidak mengganggu modal lain
                var $formContext{{ $key }} = $("#form_ppat{{ $key }}");
                var $formRekanan{{ $key }} = $formContext{{ $key }}.find(".form_rekanan");
                var $switchRekanan{{ $key }} = $("#switchCheckNotarisRekanan{{ $key }}");
                var $partnerSelect{{ $key }} = $formContext{{ $key }}.find("[name='notaris_rekanan_id']");
                var $partnerNumber{{ $key }} = $formContext{{ $key }}.find("[name='nomor_rekanan']");
                var $systemNumber{{ $key }} = $formContext{{ $key }}.find("[name='nomor']");
                var $manualSystemNumber{{ $key }} = $formContext{{ $key }}.find(".form_manual_nomor");
                var $systemNumberInfo{{ $key }} = $formContext{{ $key }}.find(".system_number_info");

                $formContext{{ $key }}.find(".select2-notaris-rekanan").select2({
                    theme: "bootstrap-5",
                    width: "100%",
                    dropdownParent: $("#modalPPat{{ $key }}"),
                    placeholder: "Pilih notaris rekanan"
                });

                var syncPartnerRequired{{ $key }} = function() {
                    var isPartnerNumber = $switchRekanan{{ $key }}.is(":checked");
                    $partnerSelect{{ $key }}.prop("required", isPartnerNumber);
                    $partnerNumber{{ $key }}.prop("required", isPartnerNumber);
                    $manualSystemNumber{{ $key }}.toggle(!isPartnerNumber);
                    $systemNumberInfo{{ $key }}.toggle(!isPartnerNumber);
                    if ($systemNumber{{ $key }}.length) {
                        $systemNumber{{ $key }}.prop("required", !isPartnerNumber);
                    }
                };

                // Inisialisasi awal jika switch ter-check dari backend
                if ($switchRekanan{{ $key }}.is(":checked")) {
                    $formRekanan{{ $key }}.show();
                } else {
                    $formRekanan{{ $key }}.hide();
                }
                syncPartnerRequired{{ $key }}();

                // Toggle animation
                $switchRekanan{{ $key }}.on("change", function() {
                    if ($(this).is(":checked")) {
                        $formRekanan{{ $key }}.slideDown('fast');
                    } else {
                        $formRekanan{{ $key }}.slideUp('fast');
                    }
                    syncPartnerRequired{{ $key }}();
                });
            @endif
        });
    </script>
@endpush
