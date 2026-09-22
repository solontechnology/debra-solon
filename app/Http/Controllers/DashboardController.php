<?php

namespace App\Http\Controllers;

use App\Models\ApprovalFreez;
use App\Models\JobDivisi;
use App\Models\JobDivisiFormOrder;
use App\Models\NomorPpat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        $groupBy = $request->get('group_by', 'day');

        if (!in_array($groupBy, ['day', 'week', 'month'])) {
            $groupBy = 'day';
        }

        $completionGroupBy = $request->get(
            'completion_group_by',
            'perusahaan'
        );

        if (!in_array($completionGroupBy, ['perusahaan', 'pegawai'])) {
            $completionGroupBy = 'perusahaan';
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER BULAN
        |--------------------------------------------------------------------------
        |
        | Kalau filter_month kosong:
        | → ambil seluruh data / akumulasi
        |
        | Kalau filter_month diisi:
        | → hanya ambil data pada bulan tersebut
        |
        */

        $filterMonth = $request->get('filter_month');

        $startDate = null;
        $endDate = null;

        if ($filterMonth) {
            $startDate = Carbon::createFromFormat('Y-m', $filterMonth)
                ->startOfMonth()
                ->startOfDay();

            $endDate = Carbon::createFromFormat('Y-m', $filterMonth)
                ->endOfMonth()
                ->endOfDay();
        }

        /*
        |--------------------------------------------------------------------------
        | BERKAS MASUK
        |--------------------------------------------------------------------------
        */

        $berkasMasukQuery = JobDivisi::query();

        if ($startDate && $endDate) {
            $berkasMasukQuery->whereBetween('created_at', [
                $startDate,
                $endDate,
            ]);
        }

        $berkasMasukCount = $berkasMasukQuery->count();

        /*
        |--------------------------------------------------------------------------
        | DATA JOB DIVISI
        |--------------------------------------------------------------------------
        */

        $jobDivisiQuery = JobDivisi::query()
            ->with([
                'formOrder',
                'developer.developer',
                'userOps',
                'pembuat',
                'freeze',
            ]);

        if ($startDate && $endDate) {
            $jobDivisiQuery->whereBetween('created_at', [
                $startDate,
                $endDate,
            ]);
        }

        $jobDivisi = $jobDivisiQuery->get();

        /*
        |--------------------------------------------------------------------------
        | FORM ORDER
        |--------------------------------------------------------------------------
        */

        $formOrder = JobDivisiFormOrder::query()
            ->with([
                'jobDivisi' => fn($query) => $query->withTrashed(),
                'statusJobOps',
                'pnbp',
            ])
            ->whereIn(
                'job_divisi_id',
                $jobDivisi->pluck('id')->toArray()
            )
            ->get();



        // =====================================================
        // NOMOR PPAT / COVERNOT
        // =====================================================


        // =====================================================
        // NOMOR PPAT / COVERNOT
        // =====================================================

        $nomorPpats = NomorPpat::query()
            ->with([
                'formOrder.jobDivisi',
            ])
            ->whereNotNull('tanggal')
            ->whereNotNull('tanggal_expired')
            ->whereRaw("
                DATE_ADD(
                    tanggal,
                    INTERVAL DATEDIFF(tanggal_expired, tanggal) / 2 DAY
                ) <= ?
            ", [
            now()->toDateString(),
            ])
            ->orderBy('tanggal_expired', 'asc')
            ->get();

        // Pisahkan PPAT dan Covernot
        $nomorPpatExpired = $nomorPpats
            ->where('kategori', 'ppat');

        $nomorCovernotExpired = $nomorPpats
            ->where('kategori', 'covernot');



        /*
        |--------------------------------------------------------------------------
        | HITUNG STATUS BERKAS
        |--------------------------------------------------------------------------
        */

        $berkasBerjalan = 0;
        $berkasSelesai = 0;
        $berkasPending = 0;

        foreach ($jobDivisi as $job) {
            $status = $job->status;

            if ($status === 'Pending') {
                $berkasPending++;
            } elseif ($status === 'Selesai') {
                $berkasSelesai++;
            } elseif (in_array($status, ['Pra Akad', 'Akad'])) {
                $berkasBerjalan++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BERKAS FREEZE
        |--------------------------------------------------------------------------
        */

        $berkasFreezeQuery = ApprovalFreez::query()
            ->where('status', 'Disetujui')
            ->where(function ($query) {
                $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            });

        /*
        | Kalau sedang menggunakan filter bulan,
        | freeze dibatasi berdasarkan job yang masuk periode tersebut.
        */

        if ($startDate && $endDate) {
            $berkasFreezeQuery->whereIn(
                'job_divisi',
                $jobDivisi->pluck('id')->toArray()
            );
        }

        $berkasFreeze = $berkasFreezeQuery->count();

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $summary = [
            'berkas_masuk' => $berkasMasukCount,

            'berkas_berjalan' => $berkasBerjalan,

            'berkas_selesai' => $berkasSelesai,

            'berkas_pending' => $berkasPending,

            'berkas_freeze' => $berkasFreeze,

            'pekerjaan' => $formOrder->count(),

            'pendapatan' => $formOrder->sum(
                fn($item) => (float) ($item->harga_proses ?? 0)
            ),

            'modal' => $formOrder->sum(
                fn($item) => (float) ($item->harga_modal ?? 0)
            ),

            'profit' => $formOrder->sum(
                fn($item) =>
                (float) ($item->harga_proses ?? 0)
                    - (float) ($item->harga_modal ?? 0)
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | GRAFIK PENYELESAIAN
        |--------------------------------------------------------------------------
        */

        $completionRanking = $this->buildCompletionRanking(
            $jobDivisi,
            $completionGroupBy
        );

        /*
        |--------------------------------------------------------------------------
        | GRAFIK PER MENU
        |--------------------------------------------------------------------------
        */

        $menuCompletionChart = $this->buildMenuCompletionChart(
            $formOrder
        );

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view('dashboard', [
            'jobDivisi' => $jobDivisi,

            'formOrder' => $formOrder,

            'formOrderGroup' => $formOrder->groupBy('kategori'),

            'groupBy' => $groupBy,

            'completionGroupBy' => $completionGroupBy,

            'summary' => $summary,

            'filter_month' => $filterMonth,

            'completionChart' => $completionRanking,

            'menuCompletionChart' => $menuCompletionChart,

            'nomorPpats' => $nomorPpats,
            'nomorPpatExpired' => $nomorPpatExpired,
            'nomorCovernotExpired' => $nomorCovernotExpired,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RANKING PENYELESAIAN
    |--------------------------------------------------------------------------
    */

    private function buildCompletionRanking(
        Collection $jobDivisi,
        string $completionGroupBy
    ): array {
        $ranking = [];

        foreach ($jobDivisi as $job) {

            /*
            | Berdasarkan pegawai
            */

            if ($completionGroupBy === 'pegawai') {

                $label =
                    $job->userOps->name
                    ?? $job->pembuat->name
                    ?? 'Tanpa Pegawai';

                $this->incrementCompletionRanking(
                    $ranking,
                    $label,
                    $job
                );

                continue;
            }

            /*
            | Berdasarkan perusahaan
            */

            if ($job->developer->isEmpty()) {

                $this->incrementCompletionRanking(
                    $ranking,
                    'Tanpa Perusahaan',
                    $job
                );

                continue;
            }

            foreach ($job->developer as $developer) {

                $label =
                    $developer->developer->nama_pt
                    ?? $developer->developer->nama_perumahan
                    ?? 'Tanpa Perusahaan';

                $this->incrementCompletionRanking(
                    $ranking,
                    $label,
                    $job
                );
            }
        }

        /*
        | Ambil 10 data teratas berdasarkan total
        */

        $ranking = collect($ranking)
            ->sortByDesc(function ($item) {
                return
                    $item['selesai']
                    + $item['belum_selesai']
                    + $item['pending']
                    + $item['freeze'];
            })
            ->take(10)
            ->values();

        return [
            'labels' => $ranking->pluck('label')->all(),

            'selesai' => $ranking->pluck('selesai')->all(),

            'belum_selesai' => $ranking->pluck('belum_selesai')->all(),

            'pending' => $ranking->pluck('pending')->all(),

            'freeze' => $ranking->pluck('freeze')->all(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS PER MENU
    |--------------------------------------------------------------------------
    */

    private function buildMenuCompletionChart(
        Collection $formOrder
    ): array {

        $menuMap = [
            'notaris' => 'Notaris',
            'ppat' => 'PPAT',
            'operasional' => 'Operasional',
            'pajak' => 'Pajak',
            'pnbp_voucher' => 'PNBP',
        ];

        $chart = collect($menuMap)
            ->mapWithKeys(function ($label, $key) {

                return [
                    $key => [
                        'label' => $label,
                        'selesai' => 0,
                        'belum_selesai' => 0,
                        'pending' => 0,
                        'freeze' => 0,
                    ],
                ];
            })
            ->all();

        foreach ($formOrder as $item) {

            if (!isset($menuMap[$item->kategori])) {
                continue;
            }

            $status = $this->resolveMenuStatus($item);

            $chart[$item->kategori][$status]++;
        }

        return [
            'labels' => array_values(
                array_column($chart, 'label')
            ),

            'selesai' => array_values(
                array_column($chart, 'selesai')
            ),

            'belum_selesai' => array_values(
                array_column($chart, 'belum_selesai')
            ),

            'pending' => array_values(
                array_column($chart, 'pending')
            ),

            'freeze' => array_values(
                array_column($chart, 'freeze')
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TAMBAH DATA KE RANKING
    |--------------------------------------------------------------------------
    */

    private function incrementCompletionRanking(
        array &$ranking,
        string $label,
        JobDivisi $job
    ): void {

        if (!isset($ranking[$label])) {

            $ranking[$label] = [
                'label' => $label,
                'selesai' => 0,
                'belum_selesai' => 0,
                'pending' => 0,
                'freeze' => 0,
            ];
        }

        $status = $this->resolveCompletionStatus($job);

        $ranking[$label][$status]++;
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE STATUS JOB
    |--------------------------------------------------------------------------
    */

    private function resolveCompletionStatus(
        JobDivisi $job
    ): string {

        $freeze = $job->freeze;

        $isFreezeActive =
            $job->trashed()
            || (
                $freeze
                && $freeze->status === 'Disetujui'
                && empty($freeze->end_date)
            );

        if ($isFreezeActive) {
            return 'freeze';
        }

        if ((int) ($job->is_pending ?? 0) === 1) {
            return 'pending';
        }

        if ($job->status === 'Selesai') {
            return 'selesai';
        }

        return 'belum_selesai';
    }

    /*
    |--------------------------------------------------------------------------
    | RESOLVE STATUS MENU
    |--------------------------------------------------------------------------
    */

    private function resolveMenuStatus(
        JobDivisiFormOrder $item
    ): string {

        $job = $item->jobDivisi;

        /*
        | Freeze
        */

        if (
            $job
            && $this->resolveCompletionStatus($job) === 'freeze'
        ) {
            return 'freeze';
        }

        /*
        | Pending
        */

        if (
            $job
            && $this->resolveCompletionStatus($job) === 'pending'
        ) {
            return 'pending';
        }

        /*
        | PNBP
        */

        if ($item->kategori === 'pnbp_voucher') {

            return ($item->pnbp?->status ?? null) === 'Disetujui'
                ? 'selesai'
                : 'belum_selesai';
        }

        /*
        | Status Job Ops terakhir
        */

        $lastStatus = $item->statusJobOps
            ->last()
            ->status ?? null;

        return $lastStatus === 'Selesai'
            ? 'selesai'
            : 'belum_selesai';
    }
}
