@extends('layouts.admin')

@section('title', 'Kota / Kabupaten')

@section('content')

    <div class="row row-cards">
        <div class="col-12">
            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Data Kota / Kabupaten</h3>

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKota"
                        onclick="resetFormToCreate()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kota / Kabupaten
                    </button>
                </div>

                <div class="card-body">

                    {{-- Search --}}
                    <div class="mb-3">
                        <form action="{{ route('master-data.kota.index') }}" method="GET">
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                    placeholder="Cari kota/kabupaten..." autocomplete="off">

                                @if (request('q'))
                                    <a href="{{ route('master-data.kota.index') }}" class="btn btn-outline-secondary"
                                        title="Reset pencarian">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-striped">

                            <thead>
                                <tr>
                                    <th class="w-1">No</th>
                                    <th>Nama Kota / Kabupaten</th>
                                    <th>Provinsi</th>
                                    <th class="w-1 text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($items as $index => $item)
                                    <tr>

                                        <td>
                                            {{ $items->firstItem() + $index }}
                                        </td>

                                        <td>
                                            {{ $item->name }}
                                        </td>

                                        <td>
                                            {{ $item->provinsi->name ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">

                                                {{-- Edit --}}
                                                <button type="button"
                                                    class="btn btn-sm btn-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center justify-content-center rounded-2"
                                                    style="width: 38px; height: 38px;"
                                                    onclick="openEditModal(
                                                        {{ $item->id }},
                                                        '{{ addslashes($item->name) }}',
                                                        '{{ $item->provinsi?->id }}'
                                                    )"
                                                    title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                {{-- Hapus --}}
                                                <form action="{{ route('master-data.kota.destroy', $item->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="btn btn-sm btn-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center justify-content-center rounded-2 confirm_delete"
                                                        data-message="Kota/Kabupaten {{ $item->name }}"
                                                        style="width: 38px; height: 38px;" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>

                                    </tr>
                                @empty

                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Data kota/kabupaten belum ada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>
                @if ($items->hasPages())
                    <div class="card-footer bg-white border-top py-3 px-4">
                        <div class="d-flex justify-content-end m-0">
                            {{ $items->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modal Kota --}}
    <div class="modal fade" id="modalKota" tabindex="-1" aria-labelledby="modalKotaTitle" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalKotaTitle">
                        Tambah Kota / Kabupaten
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>

                <form id="formKota" action="{{ route('master-data.kota.store') }}" method="POST">

                    @csrf

                    <div id="methodField"></div>

                    <div class="modal-body">

                        {{-- Provinsi --}}
                        <div class="mb-3">
                            <label for="provinsiId" class="form-label required">
                                Provinsi
                            </label>

                            <select name="provinsi_id" id="provinsiId" class="form-select select2" required>
                                <option value="">
                                    Pilih Provinsi
                                </option>

                                @foreach ($provinsis as $provinsi)
                                    <option value="{{ $provinsi->id }}">
                                        {{ $provinsi->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        {{-- Nama Kota --}}
                        <div class="mb-3">
                            <label for="kotaName" class="form-label required">
                                Nama Kota / Kabupaten
                            </label>

                            <input type="text" class="form-field form-control" id="kotaName" name="name"
                                placeholder="Masukkan nama kota / kabupaten" required autocomplete="off">
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary ms-auto">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>

@endsection

@push('addScript')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>

    <script>
        const modalEl = document.getElementById('modalKota');
        let modalInstance = null;

        function getModalInstance() {
            if (!modalInstance) {
                modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }

            return modalInstance;
        }

        // Inisialisasi Select2
        $(document).ready(function() {
            $('#provinsiId').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalKota'),
                width: '100%',
                placeholder: 'Pilih Provinsi',
                allowClear: true
            });
        });

        // Reset Form saat klik tombol Tambah
        function resetFormToCreate() {

            document.getElementById('modalKotaTitle').innerText =
                'Tambah Kota / Kabupaten';

            document.getElementById('formKota').action =
                "{{ route('master-data.kota.store') }}";

            document.getElementById('methodField').innerHTML = '';

            document.getElementById('kotaName').value = '';

            // Reset Select2
            $('#provinsiId').val(null).trigger('change');
        }

        // Buka Modal Edit
        function openEditModal(id, name, provinsiId) {

            document.getElementById('modalKotaTitle').innerText =
                'Edit Kota / Kabupaten';

            document.getElementById('formKota').action =
                "{{ url('master-data/lokasi/kota') }}/" + id;

            document.getElementById('methodField').innerHTML =
                '@method('PUT')';

            document.getElementById('kotaName').value = name;

            // Set Provinsi pada Select2
            $('#provinsiId').val(provinsiId).trigger('change');

            getModalInstance().show();
        }

        // Uppercase realtime
        document.getElementById('kotaName').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
@endpush
