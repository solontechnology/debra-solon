@extends('layouts.admin')

@section('title')
    Arsip Warkah
@endsection

@section('content')
    <div class="card">
        <div class="card-header justify-content-between">
            <div class="fw-bold">Daftar Job Selesai</div>
            <div class="d-flex gap-3">
                @include('pages.Job.Divisi._filter_divisi', [
                    'filterRoute' => route('arsip.warkah.index'),
                ])
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" data-sortable="false">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="direction" value="{{ $direction }}">
                    <thead>
                        <tr>
                            <x-sortable-th column="kode" :sort="$sort" :direction="$direction">Kode</x-sortable-th>
                            <x-sortable-th column="status" :sort="$sort" :direction="$direction">Status</x-sortable-th>
                            <x-sortable-th column="nama_penghadap" :sort="$sort" :direction="$direction">Nama
                                Penghadap</x-sortable-th>
                            <x-sortable-th column="bank" :sort="$sort" :direction="$direction">Bank</x-sortable-th>
                            <x-sortable-th column="objek" :sort="$sort" :direction="$direction">Objek</x-sortable-th>
                            <x-sortable-th column="jenis_akad" :sort="$sort" :direction="$direction">Jenis
                                Akad</x-sortable-th>
                            <x-sortable-th column="piutang" :sort="$sort" :direction="$direction">Piutang</x-sortable-th>
                            <x-sortable-th column="tanggal_akad_arsip" :sort="$sort" :direction="$direction">Tanggal
                                Akad</x-sortable-th>
                            <x-sortable-th column="est_internal" :sort="$sort" :direction="$direction">Estimasi Selesai
                                Internal</x-sortable-th>
                            <x-sortable-th column="est_eksternal" :sort="$sort" :direction="$direction">Estimasi Selesai
                                Eksternal</x-sortable-th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $totalBiaya = (float) $item->formOrder->sum('harga_proses');
                                $cashIn = (float) $item->finance
                                    ->where('tipe', 'in')
                                    ->filter(function ($finance) {
                                        $status = strtolower((string) $finance->status);
                                        return in_array($status, ['disetujui', 'approved']);
                                    })
                                    ->sum('total');
                                $piutang = $totalBiaya - $cashIn;
                            @endphp
                            <tr>
                                <th>
                                    <a href="{{ route('job.divisi.show', $item->id) }}" class="text-decoration-none">
                                        {{ $item->kode }}
                                    </a>
                                </th>
                                <td style="text-transform: uppercase">
                                    <span class="badge bg-primary text-white p-2">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td style="text-transform: uppercase">
                                    {{ implode(', ', $item->debitur->pluck('nama')->toArray()) }}
                                </td>
                                <td>
                                    @foreach ($item->listBank as $bank)
                                        {{ $bank->nama_bank }}
                                    @endforeach
                                </td>
                                <td>
                                    {{ $item->objek->pluck('no_sertifikat')->implode(', ') }}
                                </td>
                                <td>
                                    {{ $item->jenisAkad->nama ?? '-' }}
                                </td>
                                <td class="{{ $piutang > 0 ? 'text-warning fw-bold' : 'text-success fw-bold' }}">
                                    Rp {{ number_format($piutang, 0, ',', '.') }}
                                </td>
                                <td>
                                    {{ $item->tanggal_akad ? $item->tanggal_akad : $item->tanggal_rencana_akad }}
                                </td>
                                <td>
                                    {{ $item->tanggal_estimasi_selesai }}
                                </td>
                                <td>
                                    {{ $item->tanggal_estimasi_selesai_eksternal }}
                                </td>
                                <td>
                                    <a class="btn btn-info" href="{{ route('job.divisi.show', $item->id) }}">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">Tidak ada data warkah</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
