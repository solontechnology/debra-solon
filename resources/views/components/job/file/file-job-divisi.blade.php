@props([
    'jobDivisi',
    'nama',
])

@php
    $label = match ($nama) {
        'ppat' => 'PPAT',
        'notaris' => 'Notaris',
        'covernot' => 'Covernot',
        'surat-keluar' => 'Surat Keluar',
        'legalisasi' => 'Legalisasi',
        'waarmerking' => 'Waarmerking',
        'wasiat' => 'Wasiat',
        'pajak' => 'Pajak',
        default => ucfirst(str_replace('-', ' ', $nama)),
    };

    $file = $jobDivisi->fileJob->firstWhere('nama', $nama);

    $modalId = 'modalFileJobDivisi' . $jobDivisi->id;
@endphp

{{-- Tombol File --}}
<button
    type="button"
    class="btn btn-outline-warning d-inline-flex align-items-center justify-content-center"
    style="width: 38px; height: 38px;"
    data-bs-toggle="modal"
    data-bs-target="#{{ $modalId }}"
    title="Berkas {{ $label }}"
>
    <i class="bi bi-paperclip"></i>
</button>

{{-- Modal --}}
<div
    class="modal fade"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $modalId }}Label"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    Berkas {{ $label }}
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>

            <div class="modal-body">

                {{-- File saat ini --}}
                @if ($file)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-2">

                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-text fs-2 text-primary"></i>

                                <div>
                                    <div class="fw-semibold">
                                        {{ $file->path ? basename($file->path) : $label }}
                                    </div>

                                    <div class="text-secondary small">
                                        Upload oleh:
                                        {{ $file->user?->name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-1">

                                {{-- Lihat file --}}
                                <a
                                    href="{{ $file->source }}"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Lihat file"
                                >
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Hapus --}}
                                <form
                                    action="{{ route('file.destroy', $file->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus file ini?')"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus file"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center text-secondary py-3">
                        <i class="bi bi-file-earmark-x fs-1"></i>
                        <div class="mt-2">
                            Belum ada berkas {{ $label }}.
                        </div>
                    </div>
                @endif

                {{-- Upload --}}
                <form
                    action="{{ route('uploadFile') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    @if ($file)
                        onsubmit="return confirm('File {{ $label }} yang lama akan diganti. Lanjutkan?')"
                    @endif
                >
                    @csrf

                    <input
                        type="hidden"
                        name="job_divisi_id"
                        value="{{ $jobDivisi->id }}"
                    >

                    <input
                        type="hidden"
                        name="nama"
                        value="{{ $nama }}"
                    >

                    <div class="mb-3">
                        <label class="form-label">
                            {{ $file ? 'Ganti Berkas' : 'Upload Berkas' }}
                        </label>

                        <input
                            type="file"
                            name="file"
                            class="form-control"
                            required
                        >

                        <div class="form-text">
                            Maksimal ukuran file 7 MB.
                            @if ($file)
                                File lama akan diganti dengan file baru.
                            @endif
                        </div>
                    </div>

                    <div class="text-end">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-upload me-1"></i>
                            {{ $file ? 'Ganti File' : 'Upload File' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>