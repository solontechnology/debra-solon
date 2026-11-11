@extends('layouts.admin')

@section('title')
    Jurnal Keuangan
@endsection

@section('content')
    @include('pages.Finance.reports._nav')

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Kas Masuk</div>
                    <div class="fs-1 fw-bold">Rp {{ number_format($summary['kas_masuk'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Kas Keluar</div>
                    <div class="fs-1 fw-bold">Rp {{ number_format($summary['kas_keluar'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Saldo Kas</div>
                    <div class="fs-1 fw-bold {{ $summary['saldo_kas'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($summary['saldo_kas'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary text-uppercase small">Jumlah Transaksi</div>
                    <div class="fs-1 fw-bold">{{ number_format($summary['jumlah_transaksi']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Jurnal Umum</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" data-sortable="false">
                    <thead>
                        <tr>
                            <x-sortable-th column="tanggal" :sort="$sort" :direction="$direction">Tanggal</x-sortable-th>
                            <x-sortable-th column="kode" :sort="$sort" :direction="$direction">Kode Job</x-sortable-th>
                            <x-sortable-th column="tipe" :sort="$sort" :direction="$direction">Tipe</x-sortable-th>
                            <x-sortable-th column="debit_account" :sort="$sort" :direction="$direction">Akun
                                Debit</x-sortable-th>
                            <x-sortable-th column="credit_account" :sort="$sort" :direction="$direction">Akun
                                Kredit</x-sortable-th>
                            <x-sortable-th column="debit" :sort="$sort" :direction="$direction">Debit</x-sortable-th>
                            <x-sortable-th column="credit" :sort="$sort" :direction="$direction">Kredit</x-sortable-th>
                            <x-sortable-th column="peruntukan" :sort="$sort"
                                :direction="$direction">Peruntukan</x-sortable-th>
                            <x-sortable-th column="keterangan" :sort="$sort"
                                :direction="$direction">Keterangan</x-sortable-th>
                            <x-sortable-th column="user" :sort="$sort" :direction="$direction">User</x-sortable-th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jurnalItems as $item)
                            <tr>
                                <td>{{ $item['tanggal'] }}</td>
                                <td>{{ $item['kode'] }}</td>
                                <td>{{ $item['tipe'] }}</td>
                                <td>{{ $item['debit_account'] }}</td>
                                <td>{{ $item['credit_account'] }}</td>
                                <td>Rp {{ number_format($item['debit'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($item['credit'], 0, ',', '.') }}</td>
                                <td>{{ $item['peruntukan'] }}</td>
                                <td>{{ $item['keterangan'] }}</td>
                                <td>{{ $item['user'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-secondary">Belum ada transaksi finance disetujui
                                    pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
