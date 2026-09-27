@extends('layouts.admin')

@section('title', 'Provinsi')

@section('content')
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Data Provinsi</h3>
                    <!-- Tombol Tambah pakai data-bs-toggle -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProvinsi"
                        onclick="resetFormToCreate()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Provinsi
                    </button>
                </div>

                <div class="card-body">
                    <div class="mb-3">
                        <form action="{{ route('master-data.provinsi.index') }}" method="GET">
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                    placeholder="Cari provinsi..." autocomplete="off">

                                @if (request('q'))
                                    <a href="{{ route('master-data.provinsi.index') }}" class="btn btn-outline-secondary"
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
                                    <th>Nama Provinsi</th>
                                    <th class="w-1 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $index => $item)
                                    <tr>
                                        <td>{{ $items->firstItem() + $index }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <!-- Tombol Edit: Dipanggil langsung via JS onclick agar respon instan -->
                                                <button type="button"
                                                    class="btn btn-sm btn-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center justify-content-center rounded-2"
                                                    style="width: 38px; height: 38px;"
                                                    onclick="openEditModal({{ $item->id }}, '{{ addslashes($item->name) }}')"
                                                    title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>

                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('master-data.provinsi.destroy', $item->id) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center justify-content-center rounded-2 confirm_delete"
                                                        data-message="Provinsi {{ $item->name }}"
                                                        style="width: 38px; height: 38px;" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Data provinsi belum ada.</td>
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

    <!-- Modal Provinsi -->
    <div class="modal fade" id="modalProvinsi" tabindex="-1" aria-labelledby="modalProvinsiTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalProvinsiTitle">Tambah Provinsi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formProvinsi" action="{{ route('master-data.provinsi.store') }}" method="POST">
                    @csrf
                    <div id="methodField"></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="provinsiName" class="form-label required">Nama Provinsi</label>
                            <input type="text" class="form-field form-control" id="provinsiName" name="name"
                                placeholder="Masukkan nama provinsi" required autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
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
    <script>
        const modalEl = document.getElementById('modalProvinsi');
        let modalInstance = null;

        function getModalInstance() {
            if (!modalInstance) {
                modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
            return modalInstance;
        }

        // Reset Form saat klik tombol Tambah
        function resetFormToCreate() {
            document.getElementById('modalProvinsiTitle').innerText = 'Tambah Provinsi';
            document.getElementById('formProvinsi').action = "{{ route('master-data.provinsi.store') }}";
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('provinsiName').value = '';
        }

        // Buka Modal Edit langsung tanpa membaca event attribute yang lambat
        function openEditModal(id, name) {
            document.getElementById('modalProvinsiTitle').innerText = 'Edit Provinsi';
            document.getElementById('formProvinsi').action = "{{ url('master-data/lokasi/provinsi') }}/" + id;
            document.getElementById('methodField').innerHTML = '@method('PUT')';
            document.getElementById('provinsiName').value = name;

            getModalInstance().show();
        }

        // Ubah text input ke Uppercase secara real-time
        document.getElementById('provinsiName').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
@endpush
