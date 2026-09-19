@extends('layouts.admin')

@section('title')
    Setting Penomoran
@endsection

@section('content')

<div class="container-xl">

    <!-- Page Header -->
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-2">
                    <span class="bg-primary-subtle text-primary p-2 rounded-3 d-inline-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings-automation" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="0 0 24 24" fill="none"/>
                            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                            <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="page-title mb-0">
                            Pengaturan Penomoran
                        </h2>
                        <div class="text-secondary small">
                            Atur periode reset dan metode pembuatan nomor otomatis atau manual untuk tiap kategori dokumen.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M5 12l5 5l10 -10" /></svg>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <div class="d-flex gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M12 9v2m0 4v.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h4 class="alert-title mb-1">Terjadi kesalahan pada input data:</h4>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('setting.penomoran.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0">
            <div class="card-header bg-body-tertiary">
                <h3 class="card-title fw-bold">Kategori Penomoran</h3>
            </div>

            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach ($settings as $setting)
                        <div class="list-group-item p-3 p-md-4">
                            <div class="row align-items-center g-3">
                                
                                <!-- Category Title & Badge -->
                                <div class="col-lg-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-primary-subtle text-primary rounded-3 fw-bold">
                                            {{ strtoupper(substr($setting->kategori, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6 text-dark">
                                                {{ \App\Models\PenomoranSetting::kategori()[$setting->kategori] ?? $setting->kategori }}
                                            </div>
                                            <span class="badge bg-secondary-subtle text-secondary border font-monospace mt-1">
                                                {{ $setting->kategori }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reset Period Selection -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-medium text-secondary mb-2 d-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-time" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M11.795 21h-6.795a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v4" /><path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M15 3v4" /><path d="M7 3v4" /><path d="M3 11h16" /><path d="M18 16.496v1.504l1 1" /></svg>
                                        Periode Reset Urutan
                                    </label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="settings[{{ $setting->id }}][reset_period]" 
                                               id="reset_month_{{ $setting->id }}" 
                                               value="month" 
                                               @checked($setting->reset_period === 'month') 
                                               autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="reset_month_{{ $setting->id }}">
                                            Per Bulan
                                        </label>

                                        <input type="radio" 
                                               class="btn-check" 
                                               name="settings[{{ $setting->id }}][reset_period]" 
                                               id="reset_year_{{ $setting->id }}" 
                                               value="year" 
                                               @checked($setting->reset_period === 'year') 
                                               autocomplete="off">
                                        <label class="btn btn-outline-secondary" for="reset_year_{{ $setting->id }}">
                                            Per Tahun
                                        </label>
                                    </div>
                                </div>

                                <!-- Mode Selection -->
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label fw-medium text-secondary mb-2 d-flex align-items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-adjustments" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M6 4v4" /><path d="M6 12v8" /><path d="M10 16a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M12 4v10" /><path d="M12 18v2" /><path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M18 4v1" /><path d="M18 9v11" /></svg>
                                        Metode Penomoran
                                    </label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" 
                                               class="btn-check" 
                                               name="settings[{{ $setting->id }}][mode]" 
                                               id="mode_auto_{{ $setting->id }}" 
                                               value="automatic" 
                                               @checked($setting->mode === 'automatic') 
                                               autocomplete="off">
                                        <label class="btn btn-outline-primary" for="mode_auto_{{ $setting->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-robot" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M6 4m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z" /><path d="M12 2v2" /><path d="M9 12v9" /><path d="M15 12v9" /><path d="M5 16l4 -2" /><path d="M15 14l4 2" /><path d="M9 18h6" /><path d="M10 8v.01" /><path d="M14 8v.01" /></svg>
                                            Otomatis
                                        </label>

                                        <input type="radio" 
                                               class="btn-check" 
                                               name="settings[{{ $setting->id }}][mode]" 
                                               id="mode_manual_{{ $setting->id }}" 
                                               value="manual" 
                                               @checked($setting->mode === 'manual') 
                                               autocomplete="off">
                                        <label class="btn btn-outline-primary" for="mode_manual_{{ $setting->id }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M7 12l5 5l10 -10" /><path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4l11 -11z" /></svg>
                                            Manual
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Card Footer Submit Bar -->
            <div class="card-footer bg-body-tertiary text-end py-3">
                <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 0 1 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                    Simpan Pengaturan
                </button>
            </div>

        </div>
    </form>

</div>

@endsection