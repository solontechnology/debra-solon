<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JobDivisiFinance;
use App\Models\JobDivisiFormOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FinanceReportController extends Controller
{
    private const SORT_COLUMNS = [
        'tanggal'        => 'date',
        'kode'           => 'text',
        'tipe'           => 'text',
        'debit_account'  => 'text',
        'credit_account' => 'text',
        'debit'          => 'number',
        'credit'         => 'number',
        'peruntukan'     => 'text',
        'keterangan'     => 'text',
        'user'           => 'text',
    ];

    public function jurnal(Request $request)
    {
        $report = $this->buildReport($request);

        // whitelist
        $sortInput = $request->query('sort');
        $sort = is_string($sortInput) && isset(self::SORT_COLUMNS[$sortInput]) ? $sortInput : null;
        $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
            ? $request->query('direction')
            : 'asc';

        if ($sort) {
            $type = self::SORT_COLUMNS[$sort];

            $value = function ($row) use ($sort, $type) {
                $v = $row[$sort] ?? null;
                if ($v instanceof \Carbon\Carbon || $v instanceof \DateTimeInterface) {
                    return $v->getTimestamp();
                }
                if ($type === 'date') {
                    $ts = ($v && $v !== '-') ? strtotime((string) $v) : false;
                    return $ts === false ? null : $ts;
                }
                if ($type === 'number') {
                    return is_numeric($v) ? (float) $v : null;
                }
                $v = trim((string) $v);
                return ($v === '' || $v === '-') ? null : $v;
            };

            $report['jurnalItems'] = collect($report['jurnalItems'])
                ->values()
                ->map(fn($row, $i) => ['row' => $row, 'i' => $i, 'v' => $value($row)])
                ->sort(function ($a, $b) use ($type, $direction) {
                    $aEmpty = $a['v'] === null;
                    $bEmpty = $b['v'] === null;

                    if ($aEmpty !== $bEmpty) {
                        $emptyFirst = $direction === 'empty';
                        return $aEmpty ? ($emptyFirst ? -1 : 1) : ($emptyFirst ? 1 : -1);
                    }
                    if ($aEmpty) {
                        return $a['i'] <=> $b['i'];
                    }

                    $cmp = $type === 'text'
                        ? strnatcasecmp($a['v'], $b['v'])
                        : $a['v'] <=> $b['v'];

                    if ($direction === 'desc') {
                        $cmp = -$cmp;
                    }

                    return $cmp ?: $a['i'] <=> $b['i'];
                })
                ->pluck('row')
                ->values();
        }

        return view('pages.Finance.reports.jurnal', $report + compact('sort', 'direction'));
    }

    public function neraca(Request $request)
    {
        $report = $this->buildReport($request);

        return view('pages.Finance.reports.neraca', $report);
    }

    private const LABA_KOTOR_SORT_COLUMNS = [
        'tanggal'    => 'date',
        'kode'       => 'text',
        'item'       => 'text',
        'status'     => 'text',
        'pendapatan' => 'number',
        'modal'      => 'number',
        'laba_kotor' => 'number',
    ];

    /** Sort Collection/array of array di PHP. Kosong = null, '' atau '-'. */
    protected function sortRows($rows, array $columns, ?string $sort, string $direction)
    {
        $rows = collect($rows)->values();

        if (!$sort || !isset($columns[$sort])) {
            return $rows;
        }

        $type = $columns[$sort];

        $value = function ($row) use ($sort, $type) {
            $v = $row[$sort] ?? null;
            if ($v instanceof \DateTimeInterface) {
                return $v->getTimestamp();
            }
            if ($type === 'date') {
                $ts = ($v && $v !== '-') ? strtotime((string) $v) : false;
                return $ts === false ? null : $ts;
            }
            if ($type === 'number') {
                return is_numeric($v) ? (float) $v : null;
            }
            $v = trim((string) $v);
            return ($v === '' || $v === '-') ? null : $v;
        };

        return $rows
            ->map(fn($row, $i) => ['row' => $row, 'i' => $i, 'v' => $value($row)])
            ->sort(function ($a, $b) use ($type, $direction) {
                $aEmpty = $a['v'] === null;
                $bEmpty = $b['v'] === null;

                if ($aEmpty !== $bEmpty) {
                    // asc/desc: kosong di bawah, empty: kosong di atas
                    $emptyFirst = $direction === 'empty';
                    return $aEmpty ? ($emptyFirst ? -1 : 1) : ($emptyFirst ? 1 : -1);
                }
                if ($aEmpty) {
                    return $a['i'] <=> $b['i'];
                }

                $cmp = $type === 'text'
                    ? strnatcasecmp($a['v'], $b['v'])
                    : $a['v'] <=> $b['v'];

                if ($direction === 'desc') {
                    $cmp = -$cmp;
                }

                // tie-breaker: urutan asli, biar stabil
                return $cmp ?: $a['i'] <=> $b['i'];
            })
            ->pluck('row')
            ->values();
    }
    public function labaKotor(Request $request)
    {
        $report = $this->buildReport($request);

        // whitelist
        $sortInput = $request->query('sort');
        $sort = is_string($sortInput) && isset(self::LABA_KOTOR_SORT_COLUMNS[$sortInput]) ? $sortInput : null;
        $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
            ? $request->query('direction')
            : 'asc';

        $report['grossProfitItems'] = $this->sortRows(
            $report['grossProfitItems'],
            self::LABA_KOTOR_SORT_COLUMNS,
            $sort,
            $direction
        );

        return view('pages.Finance.reports.laba-kotor', $report + compact('sort', 'direction'));
    }

    public function labaBersih(Request $request)
    {
        $report = $this->buildReport($request);

        return view('pages.Finance.reports.laba-bersih', $report);
    }

    protected function buildReport(Request $request): array
    {
        $month = $request->get('month', now()->format('Y-m'));

        try {
            $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable $th) {
            $period = now()->startOfMonth();
            $month = $period->format('Y-m');
        }

        $startDate = $period->copy()->startOfMonth();
        $endDate = $period->copy()->endOfMonth();

        $approvedTransactions = JobDivisiFinance::query()
            ->with(['jobDivisi', 'formOrder', 'user'])
            ->whereHas('jobDivisi')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereRaw('LOWER(status) = ?', ['disetujui'])
                    ->orWhereRaw('LOWER(status) = ?', ['approved']);
            })
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $approvedFormOrders = JobDivisiFormOrder::query()
            ->with(['jobDivisi', 'finance'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('jobDivisi')
            ->get()
            ->filter(function (JobDivisiFormOrder $item) {
                return (float) $item->harga_proses > 0 || (float) $item->harga_modal > 0;
            })
            ->values();

        $jurnalItems = $approvedTransactions->map(function (JobDivisiFinance $item) {
            $accountDebit = $item->tipe === 'in' ? 'Kas & Bank' : $this->resolveExpenseAccount($item);
            $accountCredit = $item->tipe === 'in' ? 'Pendapatan Jasa' : 'Kas & Bank';

            return [
                'tanggal' => $item->tanggal ? Carbon::parse($item->tanggal)->format('d M Y H:i') : '-',
                'kode' => $item->jobDivisi->kode ?? '-',
                'debit_account' => $accountDebit,
                'credit_account' => $accountCredit,
                'debit' => $item->tipe === 'in' ? (float) $item->total : 0,
                'credit' => $item->tipe === 'out' ? (float) $item->total : 0,
                'nominal' => (float) $item->total,
                'peruntukan' => $item->peruntukan ?? '-',
                'keterangan' => $item->keterangan ?? '-',
                'tipe' => strtoupper((string) $item->tipe),
                'user' => $item->user->name ?? '-',
            ];
        })->values();

        $revenueIn = (float) $approvedTransactions->where('tipe', 'in')->sum('total');
        $expenseOut = (float) $approvedTransactions->where('tipe', 'out')->sum('total');
        $saldoKas = $revenueIn - $expenseOut;

        $grossProfitItems = $approvedFormOrders->map(function (JobDivisiFormOrder $item) {

            $pendapatan = (float) $item->harga_proses;
            $modal = (float) $item->harga_modal;
            $labaKotor = $pendapatan - $modal;

            return [
                'tanggal' => $item->created_at ? Carbon::parse($item->created_at)->format('d M Y H:i') : '-',
                'kode' => $item->jobDivisi->kode ?? '-',
                'item' => $item->nama ?? '-',
                'pendapatan' => $pendapatan,
                'modal' => $modal,
                'laba_kotor' => $labaKotor,
                'status' => $item->status ?? '-',
            ];
        })->values();

        $topProfit = $grossProfitItems
            ->sortByDesc('laba_kotor')
            ->take(5)
            ->values();

        $recentTransactions = $approvedTransactions
            ->sortByDesc('tanggal')
            ->take(8)
            ->values();

        $totalPendapatan = (float) $grossProfitItems->sum('pendapatan');
        $totalModal = (float) $grossProfitItems->sum('modal');
        $labaKotor = (float) $grossProfitItems->sum('laba_kotor');
        $bebanOperasional = $expenseOut;
        $labaBersih = $labaKotor - $bebanOperasional;
        $estimasiPiutang = max($totalPendapatan - $revenueIn, 0);

        $expenseBreakdown = $approvedTransactions
            ->where('tipe', 'out')
            ->groupBy(function (JobDivisiFinance $item) {
                return $item->peruntukan ?: 'Lainnya';
            })
            ->map(fn(Collection $group) => (float) $group->sum('total'))
            ->sortDesc();

        $incomeBreakdown = $approvedTransactions
            ->where('tipe', 'in')
            ->groupBy(function (JobDivisiFinance $item) {
                return $item->peruntukan ?: 'Pendapatan Lainnya';
            })
            ->map(fn(Collection $group) => (float) $group->sum('total'))
            ->sortDesc();

        $totalAktiva = $saldoKas + $estimasiPiutang;

        $totalEkuitas = $totalModal + $labaBersih;


        $assets = collect([
            [
                'nama' => 'Kas & Bank',
                'nominal' => $saldoKas,
                'icon' => 'bi-bank',
                'color' => 'primary',
            ],
            [
                'nama' => 'Piutang',
                'nominal' => $estimasiPiutang,
                'icon' => 'bi-wallet2',
                'color' => 'warning',
            ],
        ]);

        $equity = collect([
            [
                'nama' => 'Modal',
                'nominal' => $totalModal,
                'icon' => 'bi-cash-stack',
                'color' => 'info',
            ],
            [
                'nama' => 'Laba Bersih',
                'nominal' => $labaBersih,
                'icon' => 'bi-graph-up-arrow',
                'color' => $labaBersih >= 0 ? 'success' : 'danger',
            ],
        ]);
        $monthlyCashflow = [];

        for ($i = 1; $i <= 12; $i++) {

            $monthlyCashflow[] = [

                'bulan' => Carbon::create()
                    ->month($i)
                    ->translatedFormat('M'),

                'masuk' => (float) JobDivisiFinance::query()

                    ->whereYear('tanggal', $period->year)

                    ->whereMonth('tanggal', $i)

                    ->where('tipe', 'in')

                    ->where(function ($q) {
                        $q->whereRaw('LOWER(status)=?', ['approved'])
                            ->orWhereRaw('LOWER(status)=?', ['disetujui']);
                    })

                    ->sum('total'),

                'keluar' => (float) JobDivisiFinance::query()

                    ->whereYear('tanggal', $period->year)

                    ->whereMonth('tanggal', $i)

                    ->where('tipe', 'out')

                    ->where(function ($q) {
                        $q->whereRaw('LOWER(status)=?', ['approved'])
                            ->orWhereRaw('LOWER(status)=?', ['disetujui']);
                    })

                    ->sum('total'),

            ];
        }
        $topProfit = $grossProfitItems
            ->sortByDesc('laba_kotor')
            ->take(5)
            ->values();

        $expenseChartLabels = $expenseBreakdown
            ->keys()
            ->values();

        $expenseChartSeries = $expenseBreakdown
            ->values();

        $incomeChartLabels = $incomeBreakdown
            ->keys()
            ->values();

        $incomeChartSeries = $incomeBreakdown
            ->values();

        $cashflowLabels = collect($monthlyCashflow)
            ->pluck('bulan');

        $cashflowMasuk = collect($monthlyCashflow)
            ->pluck('masuk');

        $cashflowKeluar = collect($monthlyCashflow)
            ->pluck('keluar');


        return [
            'activeFinanceMenu' => 'reports',
            'month' => $month,
            'periodLabel' => $period->translatedFormat('F Y'),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => [
                'kas_masuk' => $revenueIn,
                'kas_keluar' => $expenseOut,
                'saldo_kas' => $saldoKas,
                'pendapatan' => $totalPendapatan,
                'modal' => $totalModal,
                'piutang' => $estimasiPiutang,
                'laba_kotor' => $labaKotor,
                'beban_operasional' => $bebanOperasional,
                'laba_bersih' => $labaBersih,
                'jumlah_transaksi' => $approvedTransactions->count(),
                'jumlah_item' => $grossProfitItems->count(),
            ],
            'jurnalItems' => $jurnalItems,
            'grossProfitItems' => $grossProfitItems,
            'expenseBreakdown' => $expenseBreakdown,
            'incomeBreakdown' => $incomeBreakdown,
            'assets' => $assets,
            'equity' => $equity,
            'monthlyCashflow' => $monthlyCashflow,
            'totalAktiva' => $totalAktiva,
            'totalEkuitas' => $totalEkuitas,
            'topProfit' => $topProfit,
            'expenseChartLabels' => $expenseChartLabels,

            'expenseChartSeries' => $expenseChartSeries,

            'incomeChartLabels' => $incomeChartLabels,

            'incomeChartSeries' => $incomeChartSeries,

            'cashflowLabels' => $cashflowLabels,

            'cashflowMasuk' => $cashflowMasuk,

            'cashflowKeluar' => $cashflowKeluar,
            'recentTransactions' => $recentTransactions,
        ];
    }

    protected function resolveExpenseAccount(JobDivisiFinance $item): string
    {
        $peruntukan = strtolower((string) $item->peruntukan);

        if (str_contains($peruntukan, 'pajak')) {
            return 'Beban Pajak';
        }

        if (str_contains($peruntukan, 'pnbp')) {
            return 'Beban PNBP';
        }

        if (str_contains($peruntukan, 'notaris') || str_contains($peruntukan, 'ppat')) {
            return 'Beban Jasa Notaris';
        }

        return 'Beban Operasional';
    }
}
