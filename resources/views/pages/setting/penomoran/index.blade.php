@extends('layouts.admin')

@section('title')
    Setting Penomoran
@endsection

@section('content')

<div class="container-xl">

    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Pengaturan Penomoran
                </h2>

                <div class="text-secondary">
                    Atur periode reset dan metode penomoran setiap kategori.
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
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

    <form
        action="{{ route('setting.penomoran.update') }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div class="card">

            <div class="card-header">
                <h3 class="card-title">
                    Pengaturan Penomoran
                </h3>
            </div>

            <div class="card-body">

                @foreach ($settings as $setting)

                    <div class="row align-items-center py-3
                                @if (!$loop->last) border-bottom @endif">

                        <div class="col-md-4">

                            <div class="fw-bold">
                                {{ \App\Models\PenomoranSetting::kategori()[$setting->kategori] ?? $setting->kategori }}
                            </div>

                            <div class="text-secondary small">
                                {{ $setting->kategori }}
                            </div>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Reset Nomor
                            </label>

                            <select
                                name="settings[{{ $setting->id }}][reset_period]"
                                class="form-select"
                            >

                                <option
                                    value="month"
                                    @selected($setting->reset_period === 'month')
                                >
                                    Per Bulan
                                </option>

                                <option
                                    value="year"
                                    @selected($setting->reset_period === 'year')
                                >
                                    Per Tahun
                                </option>

                            </select>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Metode Penomoran
                            </label>

                            <select
                                name="settings[{{ $setting->id }}][mode]"
                                class="form-select"
                            >

                                <option
                                    value="automatic"
                                    @selected($setting->mode === 'automatic')
                                >
                                    Otomatis
                                </option>

                                <option
                                    value="manual"
                                    @selected($setting->mode === 'manual')
                                >
                                    Manual
                                </option>

                            </select>

                        </div>

                    </div>

                @endforeach

            </div>

            <div class="card-footer text-end">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Pengaturan
                </button>

            </div>

        </div>

    </form>

</div>

@endsection