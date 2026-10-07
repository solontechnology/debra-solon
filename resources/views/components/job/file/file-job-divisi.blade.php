@props(['jobDivisi', 'nama'])

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
<button type="button" class="btn btn-outline-warning d-inline-flex align-items-center justify-content-center"
    style="width: 38px; height: 38px;" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
    title="Berkas {{ $label }}">
    <i class="bi bi-paperclip"></i>
</button>

{{-- Modal --}}
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    Berkas {{ $label }}
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                {{-- File saat ini --}}
                @if ($file)
                    <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm">
                        <div class="d-flex align-items-center justify-content-between gap-3">

                            {{-- Sisi Kiri: Ikon & Detail File --}}
                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                <div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-2 bg-primary bg-opacity-10 p-2"
                                    style="width: 42px; height: 42px;">
                                    <i class="bi bi-file-earmark-text fs-4 text-primary lh-1"></i>
                                </div>

                                <div class="text-truncate">
                                    <div class="fw-semibold text-dark text-truncate"
                                        title="{{ $file->path ? basename($file->path) : $label }}">
                                        {{ $file->path ? basename($file->path) : $label }}
                                    </div>

                                    <div class="text-secondary small d-flex align-items-center gap-1">
                                        <span>Upload oleh:</span>
                                        <span class="fw-medium text-dark">{{ $file->user?->name ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Sisi Kanan: Tombol Aksi --}}
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">

                                {{-- Lihat file --}}
                                <a href="{{ $file->source }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center p-0"
                                    style="width: 32px; height: 32px;" title="Lihat file">
                                    <i class="bi bi-eye fs-6 lh-1"></i>
                                </a>

                                {{-- Hapus --}}
                                <form action="{{ route('file.destroy', $file->id) }}" method="POST"
                                    class="form-delete-file m-0 p-0">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center p-0"
                                        style="width: 32px; height: 32px;" title="Hapus file">
                                        <i class="bi bi-trash fs-6 lh-1"></i>
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
                <form action="{{ route('uploadFile') }}" method="POST" enctype="multipart/form-data"
                    class="form-upload-file" data-label="{{ $label }}" data-has-file="{{ $file ? '1' : '0' }}">
                    @csrf

                    <input type="hidden" name="job_divisi_id" value="{{ $jobDivisi->id }}">

                    <input type="hidden" name="nama" value="{{ $nama }}">

                    <div class="mb-3">
                        {{-- <label class="form-label">
                            {{ $file ? 'Ganti Berkas' : 'Upload Berkas' }}
                        </label> --}}

                        <input type="file" name="file" class="form-control" required>

                        <div class="form-text text-start">
                            Maksimal ukuran file 7 MB.

                            @if ($file)
                                File lama akan diganti dengan file baru.
                            @endif
                        </div>
                    </div>

                    <div class="text-end">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-1"></i>
                            {{ $file ? 'Ganti File' : 'Upload File' }}
                        </button>

                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Hapus file
            document.addEventListener('submit', function(e) {

                const form = e.target.closest('.form-delete-file');

                if (!form) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Hapus file?',
                    text: 'File yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {

                    if (result.isConfirmed) {
                        form.submit();
                    }

                });
            });


            // Upload / ganti file
            document.addEventListener('submit', function(e) {

                const form = e.target.closest('.form-upload-file');

                if (!form) {
                    return;
                }

                const hasFile = form.dataset.hasFile === '1';

                if (!hasFile) {
                    return;
                }

                e.preventDefault();

                const label = form.dataset.label;

                Swal.fire({
                    title: 'Ganti file?',
                    text: `File ${label} yang lama akan diganti dengan file baru.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, ganti',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                }).then((result) => {

                    if (result.isConfirmed) {
                        form.submit();
                    }

                });
            });

        });
    </script>
@endonce
