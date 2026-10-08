@extends('layouts.admin')

@section('title', 'Notaris Rekanan')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Master Notaris Rekanan</h3>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#notarisRekananModal"
                onclick="prepareNotarisRekananForm()">
                <i class="bi bi-plus-lg me-1"></i>Tambah Notaris Rekanan
            </button>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="GET" class="mb-3" action="{{ route('master-data.notaris-rekanan.index') }}">
                <div class="input-group" style="max-width: 360px;">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control"
                        placeholder="Cari notaris rekanan">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                    <thead>
                        <tr>
                            <th>Nama Notaris</th>
                            <th>Kota / Kabupaten</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->kota?->name ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#notarisRekananModal"
                                            data-id="{{ $item->id }}" data-nama="{{ $item->nama }}"
                                            data-kota="{{ $item->kota_id }}" onclick="prepareNotarisRekananForm(this)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST"
                                            action="{{ route('master-data.notaris-rekanan.destroy', $item) }}"
                                            onsubmit="return confirm('Hapus notaris rekanan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data notaris rekanan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <div class="modal fade" id="notarisRekananModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="notarisRekananForm" method="POST" action="{{ route('master-data.notaris-rekanan.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="notarisRekananMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="notarisRekananTitle">Tambah Notaris Rekanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required" for="notarisRekananNama">Nama Notaris</label>
                            <input id="notarisRekananNama" class="form-control" name="nama" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="notarisRekananKota">Kota / Kabupaten</label>
                            <select id="notarisRekananKota" name="kota_id" class="form-select" required>
                                <option value="">Pilih kota / kabupaten</option>
                                @foreach ($kotas as $kota)
                                    <option value="{{ $kota->id }}">
                                        {{ $kota->name }}{{ $kota->provinsi ? ' — ' . $kota->provinsi->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('addScript')
    <script>
        function prepareNotarisRekananForm(button) {
            const form = document.getElementById('notarisRekananForm');
            const editing = button && button.dataset.id;
            form.action = editing
                ? `{{ url('master-data/notaris-rekanan') }}/${button.dataset.id}`
                : `{{ route('master-data.notaris-rekanan.store') }}`;
            document.getElementById('notarisRekananMethod').value = editing ? 'PUT' : 'POST';
            document.getElementById('notarisRekananTitle').textContent = editing
                ? 'Edit Notaris Rekanan'
                : 'Tambah Notaris Rekanan';
            document.getElementById('notarisRekananNama').value = editing ? button.dataset.nama : '';
            document.getElementById('notarisRekananKota').value = editing ? button.dataset.kota : '';
        }
    </script>
@endpush
