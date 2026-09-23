<form action="{{ route('job.divisi.update', $jobDivisi->id) }}" method="post" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
        {{-- Card Header Colorful Gradient --}}
        <div class="card-header bg-gradient bg-primary text-white py-3 px-4 d-flex align-items-center justify-content-between"
            style="background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-white bg-opacity-25 p-2.5 rounded-3 text-white d-flex align-items-center justify-content-center"
                    style="width: 45px; height: 45px;">
                    <i class="bi bi-pencil-square fs-4"></i>
                </div>
                <div>
                    <h5 class="card-title fw-bold mb-0 text-white">Edit Ringkasan Job Divisi</h5>
                    <p class="text-white-50 small mb-0">Kelola detail akad, penugasan tim, dan jadwal SLA</p>
                </div>
            </div>
            @if ($jobDivisi->status)
                <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill shadow-sm"
                    style="font-size: 0.825rem;">
                    <i class="bi bi-activity me-1 text-primary"></i> Status: {{ $jobDivisi->status }}
                </span>
            @endif
        </div>

        {{-- Card Body --}}
        <div class="card-body p-4 bg-light bg-opacity-25">

            {{-- Section 1: Informasi Penugasan (Aksen Biru / Primary) --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3.5 border-start border-4 border-primary rounded-start">
                    <h6
                        class="fw-bold text-primary text-uppercase small tracking-wide mb-3 d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-2 p-1 d-inline-flex"><i
                                class="bi bi-person-badge-fill"></i></span>
                        Informasi Penugasan & Tim
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small required">
                                Grup Pekerjaan
                            </label>
                            <div class="input-group shadow-sm rounded-3">
                                <span class="input-group-text bg-primary bg-opacity-10 text-primary border-0"><i
                                        class="bi bi-briefcase-fill"></i></span>
                                <input type="text" class="form-control bg-light border-0 fw-medium text-dark"
                                    name="ringkasan[group_proses]" value="{{ $jobDivisi->jenisAkad->nama }}" disabled>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">
                                Penanggung Jawab
                            </label>
                            <select name="ringkasan[penanggung_jawab]" class="form-select select2"
                                data-placeholder="Pilih Penanggung Jawab">
                                <option value=""></option>
                                @foreach ($user as $item)
                                    <option value="{{ $item->id }}"
                                        {{ (int) $jobDivisi->user_ops === (int) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">
                                Perwakilan Akad <span class="text-muted fw-normal">(Maks. 2)</span>
                            </label>
                            <select name="ringkasan[perwakilan_akad][]" multiple
                                class="form-select select2 select_perwakilan" data-placeholder="Pilih Perwakilan Akad">
                                <option value=""></option>
                                @foreach ($user as $item)
                                    <option value="{{ $item->id }}"
                                        {{ (int) $jobDivisi->user_perwakilan_akad === (int) $item->id ? 'selected' : '' }}
                                        {{ (int) $jobDivisi->user_perwakilan_akad_2 === (int) $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Detail Akad & Servis (Aksen Ungu / Purple) --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3.5 border-start border-4 border-indigo rounded-start"
                    style="border-color: #6f42c1 !important;">
                    <h6 class="fw-bold text-uppercase small tracking-wide mb-3 d-flex align-items-center gap-2"
                        style="color: #6f42c1;">
                        <span class="rounded-2 p-1 d-inline-flex" style="background-color: #f3ebff; color: #6f42c1;"><i
                                class="bi bi-geo-alt-fill"></i></span>
                        Detail Akad & Tipe Layanan
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small required">
                                Tanggal Rencana Akad
                            </label>
                            <div class="input-group shadow-sm rounded-3">
                                <span class="input-group-text bg-white border-end-0" style="color: #6f42c1;"><i
                                        class="bi bi-calendar-event-fill"></i></span>
                                <input type="datetime-local" class="form-control border-start-0"
                                    name="ringkasan[tanggal_rencana_akad]"
                                    value="{{ $jobDivisi->tanggal_rencana_akad ? \Carbon\Carbon::parse($jobDivisi->tanggal_rencana_akad)->format('Y-m-d\TH:i') : '' }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small required d-block">
                                Tipe Servis
                            </label>
                            <div class="btn-group w-100 shadow-sm rounded-3 p-1 bg-white border" role="group"
                                aria-label="Tipe Servis">
                                {{-- Option Regular --}}
                                <input type="radio" class="btn-check" name="ringkasan[tipe_servis]"
                                    id="servis_regular" value="regular"
                                    {{ $jobDivisi->tipe_servis !== 'prioritas' ? 'checked' : '' }}>
                                <label
                                    class="btn btn-outline-success border-0 rounded-2 py-2 fw-medium d-flex align-items-center justify-content-center gap-2"
                                    for="servis_regular">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Regular</span>
                                </label>

                                {{-- Option Prioritas --}}
                                <input type="radio" class="btn-check" name="ringkasan[tipe_servis]"
                                    id="servis_prioritas" value="prioritas"
                                    {{ $jobDivisi->tipe_servis === 'prioritas' ? 'checked' : '' }}>
                                <label
                                    class="btn btn-outline-danger border-0 rounded-2 py-2 fw-medium d-flex align-items-center justify-content-center gap-2"
                                    for="servis_prioritas">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    <span>Prioritas</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">
                                Tempat Akad
                            </label>
                            <div class="input-group shadow-sm rounded-3">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i
                                        class="bi bi-building-fill text-secondary"></i></span>
                                <input type="text" class="form-control border-start-0"
                                    value="{{ $jobDivisi->tempat_akad }}" name="ringkasan[tempat_akad]"
                                    placeholder="Lokasi pelaksanaan akad">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Timeline & SLA (Aksen Cyan / Info) --}}
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3.5 border-start border-4 border-info rounded-start">
                    <h6
                        class="fw-bold text-info text-uppercase small tracking-wide mb-3 d-flex align-items-center gap-2">
                        <span class="bg-info bg-opacity-10 text-info rounded-2 p-1 d-inline-flex"><i
                                class="bi bi-clock-history"></i></span>
                        Jadwal Pengiriman & Estimasi SLA
                    </h6>
                    <div class="row g-3">
                        {{-- @if ($jobDivisi->tanggal_akad)
                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-dark small">
                                    Tanggal Akad
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light text-muted border-0"><i class="bi bi-calendar-check text-success"></i></span>
                                    <input type="date" class="form-control bg-light border-0 fw-medium" value="{{ \Carbon\Carbon::parse($jobDivisi->tanggal_akad)->format('Y-m-d') }}" readonly>
                                </div>
                            </div>
                        @endif --}}

                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-dark small">
                                Tanggal Kirim Berkas
                            </label>
                            <input type="date" class="form-control shadow-sm border-info border-opacity-25"
                                name="ringkasan[tanggal_kirim_berkas]"
                                value="{{ $jobDivisi->tanggal_kirim_berkas ? \Carbon\Carbon::parse($jobDivisi->tanggal_kirim_berkas)->format('Y-m-d') : '' }}">
                        </div>

                        @if ($jobDivisi->tanggal_estimasi_selesai)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark small">
                                    Tanggal SLA Internal
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light text-muted border-0"><i
                                            class="bi bi-shield-check text-warning"></i></span>
                                    <input type="date" class="form-control bg-light border-0 fw-medium"
                                        value="{{ \Carbon\Carbon::parse($jobDivisi->tanggal_estimasi_selesai)->format('Y-m-d') }}"
                                        readonly>
                                </div>
                            </div>
                        @endif

                        @if ($jobDivisi->tanggal_estimasi_selesai_eksternal)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-dark small">
                                    Tanggal SLA Eksternal
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-light text-muted border-0"><i
                                            class="bi bi-shield-exclamation text-danger"></i></span>
                                    <input type="date" class="form-control bg-light border-0 fw-medium"
                                        value="{{ \Carbon\Carbon::parse($jobDivisi->tanggal_estimasi_selesai_eksternal)->format('Y-m-d') }}"
                                        readonly>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Section 4: Catatan (Aksen Kuning / Warning) --}}
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3.5 border-start border-4 border-warning rounded-start">
                    <h6 class="fw-bold text-warning text-uppercase small tracking-wide mb-3 d-flex align-items-center gap-2"
                        style="color: #d97706 !important;">
                        <span class="bg-warning bg-opacity-10 rounded-2 p-1 d-inline-flex" style="color: #d97706;"><i
                                class="bi bi-journal-text"></i></span>
                        Catatan Tambahan (Ringkasan)
                    </h6>
                    <textarea name="ringkasan[catatan]" class="form-control shadow-sm border-warning border-opacity-25" rows="3"
                        placeholder="Tuliskan catatan khusus atau instruksi tambahan di sini...">{{ $jobDivisi->keterangan }}</textarea>
                </div>
            </div>

        </div>

        {{-- Card Footer --}}
        @if ($jobDivisi->status !== 'Batal Akad' && $jobDivisi->status !== 'Selesai' && auth()->user()->can('job/divisi/edit'))
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light text-secondary px-4 fw-medium border"
                    onclick="window.history.back()">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary px-4 fw-medium shadow d-flex align-items-center gap-2"
                    style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        @endif
    </div>
</form>

@push('addScript')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            $(".select_perwakilan").select2({
                theme: 'bootstrap-5',
                width: '100%',
                maximumSelectionLength: 2,
                placeholder: "Pilih Perwakilan Akad"
            });
        });
    </script>
@endpush
