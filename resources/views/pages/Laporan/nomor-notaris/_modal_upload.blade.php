{{-- Tombol --}}
<button type="button" class="btn btn-outline-warning d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"
    data-bs-toggle="modal" data-bs-target="#modalUpload{{ $item->id }}">
    <i class="bi bi-paperclip"></i>
    {{-- <span>{{ $item->file_notaris_pengambil ? 'Ganti File' : 'Upload' }}</span> --}}
</button>

{{-- Modal --}}
<div class="modal fade" id="modalUpload{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('laporan.nomor-notaris.upload-file') }}" method="POST"
            enctype="multipart/form-data" class="modal-content text-start text-wrap">
            @csrf
            <input type="hidden" name="id" value="{{ $item->id }}">

            <div class="modal-header">
                <h6 class="modal-title fw-bold">Upload File Nomor {{ $item->nomor }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if ($item->file_notaris_pengambil)
                    <div class="mb-3 small">
                        File saat ini:
                        <a href="{{ asset('storage/' . $item->file_notaris_pengambil) }}" target="_blank">Lihat file</a>
                        <div class="text-muted">Upload baru bakal nimpa file lama.</div>
                    </div>
                @endif

                <input type="file" name="file_notaris_pengambil"
                    class="form-control @if (old('id') == $item->id) @error('file_notaris_pengambil') is-invalid @enderror @endif"
                    accept=".pdf,.jpg,.jpeg,.png" required>

                @if (old('id') == $item->id)
                    @error('file_notaris_pengambil')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                @endif
                <div class="form-text">PDF / JPG / PNG, maksimal 5 MB.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

{{-- Buka lagi modal kalau validasi gagal, biar error-nya kelihatan --}}
@if (old('id') == $item->id && $errors->has('file_notaris_pengambil'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalUpload{{ $item->id }}')).show();
        });
    </script>
@endif