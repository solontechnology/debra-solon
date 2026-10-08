@extends('layouts.admin')

@section('title', 'Fitur Tenant')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title mb-1">Fitur Tenant</h3>
                <div class="text-secondary small">
                    Pengaturan ini berlaku hanya untuk tenant pada domain dan database yang sedang dibuka.
                </div>
                <div class="text-secondary small">
                    Flag menu tersedia per submenu. Menu induk akan tetap tampil selama masih ada submenu aktif.
                </div>
            </div>
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

            <div class="d-flex flex-column gap-3">
                @foreach ($features as $featureKey => $feature)
                    @if ($featureKey === 'menu_dashboard')
                        <div class="pt-3 border-top">
                            <h4 class="h4 mb-1">Menu dan Submenu</h4>
                            <div class="text-secondary small">Nonaktifkan submenu untuk menyembunyikan menu sekaligus menolak akses langsung ke rutenya.</div>
                        </div>
                    @endif
                    <form action="{{ route('setting.features.update', $featureKey) }}" method="POST"
                        class="border rounded p-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        @csrf
                        @method('PUT')
                        <div>
                            <h4 class="h5 mb-1">{{ $feature['label'] }}</h4>
                            <div class="text-secondary">{{ $feature['description'] }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <select name="enabled" class="form-select" aria-label="Status fitur">
                                <option value="1" @selected($feature['enabled'])>Aktif</option>
                                <option value="0" @selected(! $feature['enabled'])>Nonaktif</option>
                            </select>
                            <button type="submit" class="btn btn-primary text-nowrap">Simpan</button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endsection
