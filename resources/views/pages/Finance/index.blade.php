@extends('layouts.admin')

@section('title')
    Finance Control {{ $type }}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            @can('finance/create')
                {{-- @include(view: 'pages.Finance._modal-add-finance') --}}
                @if ($type === 'in')
                    @include('pages.Finance._modal-add-finance-in')
                @else
                    @include('pages.Finance._modal-add-finance-out')
                @endif
            @endcan
        </div>
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped table-bordered" data-sortable="false">
                    <thead>
                        <tr>
                            <x-sortable-th column="tanggal" :sort="$sort" :direction="$direction">Tanggal</x-sortable-th>
                            <x-sortable-th column="item_proses" :sort="$sort" :direction="$direction">Item
                                Proses</x-sortable-th>
                            <x-sortable-th column="total" :sort="$sort" :direction="$direction">Total</x-sortable-th>
                            <x-sortable-th column="tipe" :sort="$sort" :direction="$direction">Tipe</x-sortable-th>
                            <x-sortable-th column="peruntukan" :sort="$sort"
                                :direction="$direction">Peruntukan</x-sortable-th>
                            <x-sortable-th column="parent" :sort="$sort" :direction="$direction">Parent</x-sortable-th>
                            <x-sortable-th column="status" :sort="$sort" :direction="$direction">Status</x-sortable-th>
                            <x-sortable-th column="keterangan" :sort="$sort"
                                :direction="$direction">Keterangan</x-sortable-th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $index => $item)
                              @if (($item->formOrder->pembatalanItemDetail->pembatalanItem->status ?? null) === 'Disetujui')
                                @continue
                            @endif

                            <tr>
                                <td>
                                    {{ $item->tanggal->format('d M Y') }}
                                </td>
                                <td>
                                    {{ $item->formOrder->nama ?? '' }}
                                </td>
                                <td>

                                    Rp. {{ number_format($item->total, '0', ',', '.') }}
                                    @if ((int) $item->total > (int) ($item->limit_ops ?? 0))
                                        <div class="text-warning">
                                            <small>
                                                Limit Ops
                                                Rp. {{ number_format($item->limit_ops, '0', ',', '.') }}
                                            </small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $item->tipe }}
                                </td>
                                <td>
                                    {{ $item->peruntukan }}
                                </td>
                                <td>
                                    {{ $item->jobDivisi->kode }}
                                </td>


                                <td>
                                    {{ $item->status }}
                                </td>
                                <td>
                                    {{ $item->keterangan }}
                                </td>
                                <td>
                                    @if ($item->can_approve)
                                        {{-- {{ dd($item->toArray()) }} --}}
                                        @include('pages.Finance._modal-approve')
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection


@push('addScript')
    <script>
        $(".form_approve").on("submit", function() {
            $(".loading__global").show();
        })
    </script>
@endpush
