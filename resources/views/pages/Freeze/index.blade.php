@extends('layouts.admin')

@section('title')
    Job Freeze
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" data-sortable="false">
                    <thead>
                        <tr>
                            <x-sortable-th column="kode_job" :sort="$sort" :direction="$direction">Kode Job</x-sortable-th>
                            <x-sortable-th column="status" :sort="$sort" :direction="$direction">Status</x-sortable-th>
                            <x-sortable-th column="diajukan_oleh" :sort="$sort" :direction="$direction">Diajukan
                                Oleh</x-sortable-th>
                            <x-sortable-th column="tanggal_pengajuan" :sort="$sort" :direction="$direction">Tanggal
                                Pengajuan</x-sortable-th>
                            <x-sortable-th column="start_date" :sort="$sort" :direction="$direction">Tanggal Mulai
                                Freeze</x-sortable-th>
                            <x-sortable-th column="end_date" :sort="$sort" :direction="$direction">Tanggal Selesai
                                Freeze</x-sortable-th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>
                                    {{ $item->jobDivisi->kode }}
                                </td>
                                <td>
                                    <span style="text-transform: uppercase"
                                        class="fw-bold {{ $item->status === 'menunggu persetujuan' ? 'text-warning' : '' }} {{ $item->status === 'Disetujui' ? 'text-success' : '' }} {{ $item->status === 'Ditolak' ? 'text-danger' : '' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $item->user->name }}
                                </td>
                                <td>
                                    {{ $item->created_at }}
                                </td>
                                <td>
                                    {{ $item->start_date }}
                                </td>
                                <td>
                                    {{ $item->end_date }}
                                </td>
                                <td>
                                    @can('berkas-bermasalah/freeze/detail')
                                        <a href="{{ route('berkas-bermasalah.freeze.show', $item->id) }}"
                                            class="btn btn-primary">Detail</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
