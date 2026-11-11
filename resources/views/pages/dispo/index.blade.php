@extends('layouts.admin')

@section('title')
    Dispo
@endsection


@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" data-sortable="false">
                    <thead>
                        <tr>
                            <x-sortable-th column="kode_parent" :sort="$sort" :direction="$direction">Kode
                                Parent</x-sortable-th>
                            <x-sortable-th column="pekerjaan" :sort="$sort" :direction="$direction">Pekerjaan</x-sortable-th>
                            <x-sortable-th column="start_date" :sort="$sort" :direction="$direction">Tanggal
                                Mulai</x-sortable-th>
                            <x-sortable-th column="end_date" :sort="$sort" :direction="$direction">Tanggal
                                Selesai</x-sortable-th>
                            <x-sortable-th column="dibuat_oleh" :sort="$sort" :direction="$direction">Dibuat
                                Oleh</x-sortable-th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $key => $item)
                            <tr>
                                <td>
                                    {{ $item->jobDivisiFormOrder->jobDivisi->kode }}
                                </td>
                                <td>
                                    {{ $item->jobDivisiFormOrder->nama }}
                                </td>
                                <td>
                                    {{ $item->start_date }}
                                </td>
                                <td>
                                    {{ $item->end_date }}
                                </td>
                                <td>
                                    {{ $item->dibuat->name }}
                                </td>
                                <td>
                                    @include('pages.dispo._modal-detail-item-dispo', [
                                        'key' => $key,
                                        'item' => $item,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
