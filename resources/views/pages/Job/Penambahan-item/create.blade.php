@extends('layouts.admin')

@section('title')
    Tambah Penambahan Item Job Divisi
@endsection

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job.penambahan-item.index') }}"
                    class="text-decoration-none">Penambahan Item</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Tambah Data</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        {{-- Card Header --}}
        <div
            class="card-header bg-primary bg-opacity-10 py-3 px-4 d-flex justify-content-between align-items-center border-bottom border-primary border-opacity-25">
            <div class="d-flex align-items-center gap-3">
                <span class="bg-primary text-white rounded-3 p-2 lh-1 fs-5">
                    <i class="bi bi-clipboard2-plus"></i>
                </span>
                <div>
                    <h5 class="fw-bold m-0 text-dark">Form Tambah Penambahan Item</h5>
                    <p class="text-muted small m-0">Lengkapi formulir di bawah ini untuk menambahkan item pekerjaan baru</p>
                </div>
            </div>
            <a href="{{ route('job.penambahan-item.index') }}"
                class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('job.penambahan-item.store') }}" method="POST" id="formPenambahanItem">
                @csrf

                {{-- Section Informasi Utama --}}
                {{-- <p>Jumlah job: {{ $jobDivisi->count() }}</p> --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="parent" class="form-label fw-medium text-dark required">Pilih Parent Job</label>
                        <select name="parent" id="parent" class="form-select select2"
                            data-placeholder="Pilih Parent Job">
                            <option value=""></option>
                            @foreach ($jobDivisi as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->kode }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="keterangan" class="form-label fw-medium text-dark">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="1"
                            placeholder="Masukkan Keterangan (Opsional)">{{ old('keterangan') }}</textarea>
                    </div>
                </div>

                <hr class="text-muted opacity-25 my-4">

                {{-- Header Section Dynamic Items --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                        <i class="bi bi-list-task text-primary fs-5"></i>
                        <span>Daftar Pekerjaan</span>
                    </h6>
                </div>

                {{-- Container Dynamic Cards --}}
                <div class="list__pekerjaan d-flex flex-column gap-3">
                    <div class="card card_row_0 border border-primary border-opacity-25 bg-white rounded-3 shadow-sm">
                        <div class="card-body p-3">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-medium small text-secondary required mb-1">Pilih
                                        Pekerjaan</label>
                                    <select name="pekerjaan[]" class="form-select select__pekerjaan"
                                        data-placeholder="Pilih Pekerjaan">
                                        <option value=""></option>
                                        @foreach ($pekerjaan as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-medium small text-secondary required mb-1">Harga
                                        Jual</label>
                                    <div class="input-group">
                                        <span
                                            class="input-group-text bg-primary bg-opacity-10 text-primary fw-semibold">Rp</span>
                                        <input type="text" class="form-control money" name="harga_jual[]"
                                            placeholder="0">
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-medium small text-secondary mb-1">Status Invoice</label>
                                    <div class="form-control d-flex align-items-center">
                                        <div class="form-check form-switch mb-0 ps-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input m-0 float-none" type="checkbox" role="switch"
                                                id="switchInvoice_0" checked name="masuk_invoice[]" value="1">
                                            <label class="form-check-label small fw-medium text-dark"
                                                for="switchInvoice_0">Masuk Invoice</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger w-100"
                                        onclick="removePekerjaan('card_row_0')" title="Hapus Baris">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tombol Tambah Baris --}}
                <div class="mt-3">
                    <button type="button"
                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 py-2 px-3 fw-medium add_more text-black">
                        <i class="bi bi-plus-circle"></i>
                        <span class>Tambah Pekerjaan</span>
                    </button>
                </div>

                {{-- Footer Action Buttons --}}
                <div class="mt-5 pt-3 border-top d-flex align-items-center justify-content-end gap-2">
                    <a href="{{ route('job.penambahan-item.index') }}"
                        class="btn btn-light border d-inline-flex align-items-center px-4 fw-medium">Batal</a>
                    <button type="button"
                        class="btn btn-success btn__simpan d-inline-flex align-items-center gap-2 px-4 fw-medium shadow-sm">
                        <i class="bi bi-check-lg"></i>
                        <span>Simpan Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Template HTML tersembunyi untuk dropdown pekerjaan (mencegah error blade compiler di JS) --}}
    <template id="pekerjaanOptions">
        <option value=""></option>
        @foreach ($pekerjaan as $item)
            <option value="{{ $item->id }}">{{ $item->nama }}</option>
        @endforeach
    </template>
@endsection

@push('addScript')
    <script>
        $(document).ready(function() {
            initSelect2();
            initMoneyMask();

            $(".btn__simpan").on("click", function() {
                if (typeof $(".loading__global").show === "function") {
                    $(".loading__global").show();
                }
                $(this).closest("form").submit();
            });
        });

      function initSelect2(context = document) {
    $(context).find('.select2, .select__pekerjaan').each(function() {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
}

        function initMoneyMask(context = document) {
            if ($.fn.mask) {
                $(context).find('.money').mask('#.##0', {
                    reverse: true
                });
            }
        }

        const removePekerjaan = (el) => {
            if ($(".list__pekerjaan .card").length <= 1) {
                alert('Minimal harus ada 1 item pekerjaan!');
                return;
            }
            $(`.${el}`).remove();
        }

        let rowIndex = 1;
        $(".add_more").on("click", function() {
            rowIndex++;
            const rowClass = `card_row_${rowIndex}`;
            const switchId = `switchInvoice_${rowIndex}`;

            // Mengambil opsi dari elemen <template> yang disiapkan oleh Blade di server-side
            const optionsHtml = $('#pekerjaanOptions').html();

            const template = `
                <div class="card ${rowClass} border border-primary border-opacity-25 bg-white rounded-3 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-medium small text-secondary required mb-1">Pilih Pekerjaan</label>
                                <select name="pekerjaan[]" class="form-select select__pekerjaan" data-placeholder="Pilih Pekerjaan">
                                    ${optionsHtml}
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-medium small text-secondary required mb-1">Harga Jual</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary bg-opacity-10 text-primary fw-semibold">Rp</span>
                                    <input type="text" class="form-control money" name="harga_jual[]" placeholder="0">
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-medium small text-secondary mb-1">Status Invoice</label>
                                <div class="form-control d-flex align-items-center">
                                    <div class="form-check form-switch mb-0 ps-0 d-flex align-items-center gap-2">
                                        <input class="form-check-input m-0 float-none" type="checkbox" role="switch" id="${switchId}" checked name="masuk_invoice[]" value="1">
                                        <label class="form-check-label small fw-medium text-dark" for="${switchId}">Masuk Invoice</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="removePekerjaan('${rowClass}')" title="Hapus Baris">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const $newRow = $(template);
            $(".list__pekerjaan").append($newRow);

            // Init plugin cuma di row baru
            initSelect2($newRow);
            initMoneyMask($newRow);
        });
    </script>
@endpush