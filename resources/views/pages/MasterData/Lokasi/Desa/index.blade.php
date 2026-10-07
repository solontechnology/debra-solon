@extends('layouts.admin')

@section('title', 'Desa')

@section('content')

    <div class="row row-cards">

        <div class="col-12">

            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">

                    <h3 class="card-title">Data Desa</h3>

                    <button type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalDesa"
                        onclick="resetFormToCreate()">

                        <i class="bi bi-plus-lg me-1"></i>
                        Tambah Desa

                    </button>

                </div>

                <div class="card-body">

                    {{-- Search --}}

                    <div class="mb-3">

                        <form action="{{ route('master-data.desa.index') }}" method="GET">

                            <div class="input-group" style="max-width: 300px;">

                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input type="text"
                                    name="q"
                                    value="{{ request('q') }}"
                                    class="form-control"
                                    placeholder="Cari desa..."
                                    autocomplete="off">

                                @if (request('q'))

                                    <a href="{{ route('master-data.desa.index') }}"
                                        class="btn btn-outline-secondary"
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

                                    <th>Nama Desa</th>

                                    <th>Kecamatan</th>

                                    <th>Kota / Kabupaten</th>

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
                                            {{ $item->kecamatan->name ?? '-' }}
                                        </td>

                                        <td>
                                            {{ $item->kecamatan->kota->name ?? '-' }}
                                        </td>

                                        <td>
                                            {{ $item->kecamatan->kota->provinsi->name ?? '-' }}
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
                                                        '{{ $item->kecamatan?->kota?->provinsi?->id }}',
                                                        '{{ $item->kecamatan?->kota?->id }}',
                                                        '{{ $item->kecamatan?->id }}'
                                                    )"
                                                    title="Edit">

                                                    <i class="bi bi-pencil"></i>

                                                </button>

                                                {{-- Hapus --}}

                                                <form action="{{ route('master-data.desa.destroy', $item->id) }}"
                                                    method="POST"
                                                    class="d-inline">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="btn btn-sm btn-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center justify-content-center rounded-2 confirm_delete"
                                                        data-message="Desa {{ $item->name }}"
                                                        style="width: 38px; height: 38px;"
                                                        title="Hapus">

                                                        <i class="bi bi-trash"></i>

                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6"
                                            class="text-center text-muted py-4">

                                            Data desa belum ada.

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


    {{-- Modal Desa --}}

    <div class="modal fade"
        id="modalDesa"
        tabindex="-1"
        aria-labelledby="modalDesaTitle"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title"
                        id="modalDesaTitle">

                        Tambah Desa

                    </h5>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">

                    </button>

                </div>

                <form id="formDesa"
                    action="{{ route('master-data.desa.store') }}"
                    method="POST">

                    @csrf

                    <div id="methodField"></div>

                    <div class="modal-body">

                        {{-- Provinsi --}}

                        <div class="mb-3">

                            <label for="provinsiId"
                                class="form-label required">

                                Provinsi

                            </label>

                            <select name="provinsi_id"
                                id="provinsiId"
                                class="form-select select2"
                                required>

                                <option value="">
                                    Pilih Provinsi
                                </option>

                                @foreach ($provinsis as $provinsi)

                                    <option value="{{ $provinsi->id }}"
                                        data-kode="{{ $provinsi->kode }}">

                                        {{ $provinsi->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Kota / Kabupaten --}}

                        <div class="mb-3">

                            <label for="kotaId"
                                class="form-label required">

                                Kota / Kabupaten

                            </label>

                            <select name="kota_id"
                                id="kotaId"
                                class="form-select select2"
                                required>

                                <option value="">
                                    Pilih Kota / Kabupaten
                                </option>

                                @foreach ($kotas as $kota)

                                    <option value="{{ $kota->id }}"
                                        data-provinsi="{{ $kota->kode_provinsi }}">

                                        {{ $kota->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Kecamatan --}}

                        <div class="mb-3">

                            <label for="kecamatanId"
                                class="form-label required">

                                Kecamatan

                            </label>

                            <select name="kecamatan_id"
                                id="kecamatanId"
                                class="form-select select2"
                                required>

                                <option value="">
                                    Pilih Kecamatan
                                </option>

                                @foreach ($kecamatans as $kecamatan)

                                    <option value="{{ $kecamatan->id }}"
                                        data-kota="{{ $kecamatan->kode_kota }}">

                                        {{ $kecamatan->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Nama Desa --}}

                        <div class="mb-3">

                            <label for="desaName"
                                class="form-label required">

                                Nama Desa

                            </label>

                            <input type="text"
                                class="form-field form-control"
                                id="desaName"
                                name="name"
                                placeholder="Masukkan nama desa"
                                required
                                autocomplete="off">

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                            class="btn btn-link link-secondary"
                            data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit"
                            class="btn btn-primary ms-auto">

                            <i class="bi bi-save me-1"></i>
                            Simpan

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

        const modalEl = document.getElementById('modalDesa');

        let modalInstance = null;

        function getModalInstance() {

            if (!modalInstance) {

                modalInstance =
                    bootstrap.Modal.getOrCreateInstance(modalEl);

            }

            return modalInstance;

        }


        // Inisialisasi Select2

        $(document).ready(function() {

            $('#provinsiId').select2({

                theme: 'bootstrap-5',

                dropdownParent: $('#modalDesa'),

                width: '100%',

                placeholder: 'Pilih Provinsi',

                allowClear: true

            });


            $('#kotaId').select2({

                theme: 'bootstrap-5',

                dropdownParent: $('#modalDesa'),

                width: '100%',

                placeholder: 'Pilih Kota / Kabupaten',

                allowClear: true

            });


            $('#kecamatanId').select2({

                theme: 'bootstrap-5',

                dropdownParent: $('#modalDesa'),

                width: '100%',

                placeholder: 'Pilih Kecamatan',

                allowClear: true

            });


            // Filter Kota berdasarkan Provinsi

            $('#provinsiId').on('change', function() {

                const provinsiId = $(this).val();

                const selectedKota = $('#kotaId').val();

                const selectedProvinsi =
                    @json($provinsis).find(
                        item => item.id == provinsiId
                    );

                $('#kotaId option').each(function() {

                    const option = $(this);

                    if (!option.val()) {

                        option.prop('disabled', false);

                        return;

                    }

                    if (
                        selectedProvinsi &&
                        option.data('provinsi') == selectedProvinsi.kode
                    ) {

                        option.prop('disabled', false);

                    } else {

                        option.prop('disabled', true);

                    }

                });


                if (
                    selectedKota &&
                    $('#kotaId option:selected').prop('disabled')
                ) {

                    $('#kotaId')
                        .val(null)
                        .trigger('change');

                }

            });


            // Filter Kecamatan berdasarkan Kota

            $('#kotaId').on('change', function() {

                const kotaId = $(this).val();

                const selectedKecamatan =
                    $('#kecamatanId').val();

                const selectedKota =
                    @json($kotas).find(
                        item => item.id == kotaId
                    );

                $('#kecamatanId option').each(function() {

                    const option = $(this);

                    if (!option.val()) {

                        option.prop('disabled', false);

                        return;

                    }

                    if (
                        selectedKota &&
                        option.data('kota') == selectedKota.id_kota
                    ) {

                        option.prop('disabled', false);

                    } else {

                        option.prop('disabled', true);

                    }

                });


                if (
                    selectedKecamatan &&
                    $('#kecamatanId option:selected').prop('disabled')
                ) {

                    $('#kecamatanId')
                        .val(null)
                        .trigger('change');

                }

            });

        });


        // Reset Form saat klik tombol Tambah

        function resetFormToCreate() {

            document.getElementById('modalDesaTitle').innerText =
                'Tambah Desa';

            document.getElementById('formDesa').action =
                "{{ route('master-data.desa.store') }}";

            document.getElementById('methodField').innerHTML = '';

            document.getElementById('desaName').value = '';


            $('#provinsiId')
                .val(null)
                .trigger('change');

            $('#kotaId')
                .val(null)
                .trigger('change');

            $('#kecamatanId')
                .val(null)
                .trigger('change');

        }


        // Buka Modal Edit

        function openEditModal(
            id,
            name,
            provinsiId,
            kotaId,
            kecamatanId
        ) {

            document.getElementById('modalDesaTitle').innerText =
                'Edit Desa';

            document.getElementById('formDesa').action =
                "{{ url('master-data/lokasi/desa') }}/" + id;

            document.getElementById('methodField').innerHTML =
                '@method('PUT')';

            document.getElementById('desaName').value =
                name;


            $('#provinsiId')
                .val(provinsiId)
                .trigger('change');


            setTimeout(function() {

                $('#kotaId')
                    .val(kotaId)
                    .trigger('change');


                setTimeout(function() {

                    $('#kecamatanId')
                        .val(kecamatanId)
                        .trigger('change');

                }, 100);

            }, 100);


            getModalInstance().show();

        }


        // Uppercase realtime

        document.getElementById('desaName')
            .addEventListener('input', function() {

                this.value =
                    this.value.toUpperCase();

            });

    </script>

@endpush