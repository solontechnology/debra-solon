@extends('layouts.admin')

@section('title')
    Laba Kotor
@endsection

@section('content')
    @include('pages.Finance.reports._nav')

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Pendapatan</div>
                    <div class="fs-1 fw-bold">Rp {{ number_format($summary['pendapatan'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Modal</div>
                    <div class="fs-1 fw-bold">Rp {{ number_format($summary['modal'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Laba Kotor</div>
                    <div class="fs-1 fw-bold {{ $summary['laba_kotor'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($summary['laba_kotor'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detail Laba Kotor per Item</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <x-sortable-th column="tanggal" :sort="$sort" :direction="$direction">Tanggal</x-sortable-th>
                            <x-sortable-th column="kode" :sort="$sort" :direction="$direction">Kode Job</x-sortable-th>
                            <x-sortable-th column="item" :sort="$sort" :direction="$direction">Item Proses</x-sortable-th>
                            <x-sortable-th column="status" :sort="$sort" :direction="$direction">Status</x-sortable-th>
                            <x-sortable-th column="pendapatan" :sort="$sort"
                                :direction="$direction">Pendapatan</x-sortable-th>
                            <x-sortable-th column="modal" :sort="$sort" :direction="$direction">Modal</x-sortable-th>
                            <x-sortable-th column="laba_kotor" :sort="$sort" :direction="$direction">Laba
                                Kotor</x-sortable-th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grossProfitItems as $item)
                            <tr>
                                <td>{{ $item['tanggal'] }}</td>
                                <td>{{ $item['kode'] }}</td>
                                <td>{{ $item['item'] }}</td>
                                <td>{{ $item['status'] }}</td>
                                <td>Rp {{ number_format($item['pendapatan'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item['modal'], 0, ',', '.') }}</td>
                                <td class="{{ $item['laba_kotor'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($item['laba_kotor'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary">Belum ada item pekerjaan untuk periode
                                    ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
