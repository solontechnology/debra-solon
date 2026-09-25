@extends('layouts.admin')

@section('title')
    Detail Laporan Pekerjaan Job Divisi
@endsection


@section('content')
    <div class="card">
        <div class="card-header">
            @include('pages.Laporan.HistoryJobDivisi.Detail._modal_sort_dataPekerjaanJobDivisi')
        </div>
        <div class="card-body">
            <div class="">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>
                                    No
                                </th>
                                <th>
                                    User Penugasan
                                </th>
                                <th>
                                    Keterangan
                                </th>
                                <th>
                                    Nama Pekerjaan
                                </th>
                                <th>
                                    Tanggal Mulai
                                </th>
                                <th>
                                    Tanggal Selesai
                                </th>
                                <th>
                                    Keterangan
                                </th>
                            </tr>
                        </thead>
                      <tbody>
                        
                        
                        @forelse ($datas['jobOps'] as $data) 
                        <tr>
                            {{ dd($data) }}
                            <td>{{ $loop->iteration }}</td>   
                            <td>
                                @if ($statusJobOps->sortByDesc("id") as $number => $statusPengerjaan)
                                    {{ $data->form_created_at }}
                                @else
                                {{ $data->created_at }}
                                @endif
                            </td>                       
                            <td>{{ $data->user->name }}</td>
                            <td>{{ $data->keterangan }}</td> 
                            <td>{{ $data->createdBy->name ?? 'Otomatis' }}</td> 
                            <td>
                                @if ($loop->first)
                                    {{ $data->form_created_at }}
                                @else
                                {{ $data->created_at }}
                                @endif
                            </td>
                            <td>{{ $data->next_created_at ?? '-' }}</td>
                                @if ($data->status_penolakan != null)
                                <td>{{ 'Data ditolak - ' . $data->status_penolakan }}</td>
                                @else
                                <td>{{ $data->status }}</td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">Data Kosong</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5">
                    {{-- {{ $items->links() }} --}}
                </div>
            </div>
        </div>
    </div>
@endsection
