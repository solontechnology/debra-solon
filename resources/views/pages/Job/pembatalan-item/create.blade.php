@extends('layouts.admin')

@section('title')
    Pembatalan Item
@endsection

@push('page-title')
    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);"
        aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Job</a></li>
            <li class="breadcrumb-item"><a href="{{ route('job.pembatalan-items.index') }}" class="text-decoration-none">Pembatalan Item</a></li>
            <li class="breadcrumb-item active fw-semibold text-uppercase" aria-current="page">Tambah Data</li>
        </ol>
    </nav>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-3">
        {{-- Card Header --}}
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold m-0 text-dark">Form Pembatalan Item</h5>
                <p class="text-muted small m-0">Pilih item pekerjaan yang ingin dibatalkan dari Job Parent</p>
            </div>
            <a href="{{ route('job.pembatalan-items.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali</span>
            </a>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('job.pembatalan-items.store') }}" method="post" id="formPembatalan">
                @csrf

                {{-- Information Section --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark required">Job Parent</label>
                        <input type="hidden" name="job_divisi_id" value="{{ $jobDivisi->id }}">
                        <input type="text" class="form-control bg-light fw-semibold" disabled value="{{ $jobDivisi->kode }}">
                    </div>
                </div>

                <hr class="text-muted opacity-25 my-4">

                {{-- Table Section --}}
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-list-check text-primary"></i>
                        <span>Pilih Item yang Dibatalkan</span>
                    </h6>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </th>
                                    <th width="80" class="text-center">ID</th>
                                    <th>Proses / Nama Pekerjaan</th>
                                    <th>Kategori</th>
                                    <th width="120" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($formOrder as $item)
                                    <tr>
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center m-0">
                                                <input class="form-check-input checkItem" type="checkbox"
                                                    value="{{ $item->id }}" name="item[{{ $item->id }}]"
                                                    id="checkDefault{{ $item->id }}">
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold text-secondary">
                                            #{{ $item->id }}
                                        </td>
                                        <td>
                                            <label for="checkDefault{{ $item->id }}" class="fw-medium text-dark mb-0 style-pointer">
                                                {{ $item->nama ?? '-' }}
                                            </label>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border fw-normal">
                                                {{ $item->kategori ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 fw-medium">
                                                Aktif
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Tidak ada item pekerjaan yang dapat dibatalkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Keterangan Section --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label for="keterangan" class="form-label fw-medium text-dark required">Keterangan Pembatalan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Masukkan alasan pembatalan item..." required></textarea>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-3 border-top d-flex align-items-center justify-content-end gap-2">
                    <a href="{{ route('job.pembatalan-items.index') }}" class="btn btn-light border px-4 fw-medium">Batal</a>
                    <button type="button" class="btn btn-primary btn__simpan d-inline-flex align-items-center gap-2 px-4 fw-medium shadow-sm">
                        <i class="bi bi-check-lg"></i>
                        <span>Simpan Pembatalan</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('addScript')
    <script>
        $(document).ready(function() {
            // Master Checkbox Toggle
            $("#checkAll").on('change', function() {
                const isChecked = $(this).is(":checked");
                $(".checkItem").prop("checked", isChecked);
            });

            // Auto-update master checkbox when items are clicked individually
            $(document).on('change', '.checkItem', function() {
                const totalItems = $(".checkItem").length;
                const checkedItems = $(".checkItem:checked").length;
                
                $("#checkAll").prop("checked", totalItems > 0 && totalItems === checkedItems);
            });

            // Form Submit with simple validation
            $(".btn__simpan").on("click", function() {
                const checkedCount = $(".checkItem:checked").length;
                const keterangan = $("#keterangan").val().trim();

                if (checkedCount === 0) {
                    alert("Pilih setidaknya satu item yang ingin dibatalkan!");
                    return;
                }

                if (keterangan === "") {
                    alert("Keterangan pembatalan wajib diisi!");
                    $("#keterangan").focus();
                    return;
                }

                if (typeof $(".loading__global").show === "function") {
                    $(".loading__global").show();
                }

                $(this).closest("form").submit();
            });
        });
    </script>
@endpush