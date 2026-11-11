<?php

namespace App\Http\Controllers\Laporan\PekerjaanStaff\Detail;

use App\Http\Controllers\Controller;
use App\Models\Debitur;
use App\Models\JobDeveloper;
use App\Models\JobDivisi;
use App\Models\JobDivisiBadanHukum;
use App\Models\JobDivisiBroker;
use App\Models\JobDivisiFinance;
use App\Models\JobDivisiFormOrder;
use App\Models\JobDivisiObjek;
use App\Models\JobDivisiPendirianLembaga;
use App\Models\Pembeli;
use App\Models\Penjual;
use App\Models\Pnbp;
use App\Models\StatusJobOps;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel;

class detailLaporanPekerjaanStaffController extends Controller
{
    public function index(Request $request, $encryptedId)
    {
        $data = $this->getBaseQuery($request, $encryptedId);
        $user = $data['user'];
        $jobOps = $data['items'];

        return view('pages.Laporan.PekerjaanStaff.Detail.detailLaporanPekerjaanStaff', [
            'datas' => ['user' => $user, 'jobOps' => $jobOps],
        ]);
    }

    public function exportDetailLaporanPekerjaanToExcel(Request $request, $encryptedId)
    {
        $data = $this->getBaseQuery($request, $encryptedId);
        $user = $data['user'];
        $items = $data['items']->getCollection();

        foreach ($items as $index => $item) {
            if ($index === 0) {
                $item->created_at = $item->form_created_at;
            }

            $item->next_created_at = isset($items[$index + 1]) ? $items[$index + 1]->created_at : null;
            $item->user_name = $user->name;
            $item->keterangan = $item->status_penolakan ? 'Data ditolak - ' . $item->status_penolakan : ($item->status ?? '-');
        }

        $fileName = 'Laporan_Pekerjaan_' . str_replace(' ', '_', $user->name) . '.xlsx';

        return Excel::download(new \App\Http\Controllers\Export\DetailPekerjaanStaffExport($items), $fileName);
    }

    private function getBaseQuery(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decryptString($encryptedId);
        } catch (DecryptException $e) {
            abort(404);
        }

        $user = User::findOrFail($id);
        $items = $this->collectActivityRows($request, $id, $user->name);

        $page = (int) $request->input('page', 1);
        $perPage = 100;
        $paged = new LengthAwarePaginator(
            $items->slice(($page - 1) * $perPage, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return ['items' => $paged, 'user' => $user];
    }

    private function collectActivityRows(Request $request, int $userId, string $userName)
    {
        $dateColumn = $request->input('date_type') ?: 'created_at';
        $startDate = $request->filled('start_date') ? $request->start_date : null;
        $endDate = $request->filled('end_date') ? $request->end_date : null;

        $items = collect();

        $items = $items->merge($this->statusJobRows($userId, $userName, $dateColumn, $startDate, $endDate));
        $items = $items->merge($this->jobDivisiRows($userId, $userName, $dateColumn, $startDate, $endDate));
        $items = $items->merge($this->formOrderRows($userId, $userName, $dateColumn, $startDate, $endDate));
        $items = $items->merge($this->supportingDataRows($userId, $userName, $dateColumn, $startDate, $endDate));
        $items = $items->merge($this->pnbpRows($userId, $userName, $dateColumn, $startDate, $endDate));
        $items = $items->merge($this->financeRows($userId, $userName, $dateColumn, $startDate, $endDate));

        $items = $items->sortByDesc(fn ($item) => $item->created_at)->values();

        foreach ($items as $index => $item) {
            $item->next_created_at = isset($items[$index + 1]) ? $items[$index + 1]->created_at : null;
        }

        return $items;
    }

    private function applyDateFilters($query, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        if ($startDate) {
            $query->whereDate($dateColumn, '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate($dateColumn, '<=', $endDate);
        }

        return $query;
    }

    private function statusJobRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $dateField = $dateColumn === 'updated_at' ? 'status_job_ops.updated_at' : 'status_job_ops.created_at';

        return StatusJobOps::query()
            ->leftJoin('job_divisi_form_orders', 'job_divisi_form_orders.id', '=', 'status_job_ops.job_divisi_form_order_id')
            ->leftJoin('job_divisis', 'job_divisis.id', '=', 'job_divisi_form_orders.job_divisi_id')
            ->where('status_job_ops.created_by', $userId)
            ->select(
                'status_job_ops.*',
                'job_divisi_form_orders.nama as nama',
                'job_divisis.kode as kode',
                'job_divisi_form_orders.created_at as form_created_at'
            )
            ->when($startDate, fn ($query) => $this->applyDateFilters($query, $dateField, $startDate, null))
            ->when($endDate, fn ($query) => $this->applyDateFilters($query, $dateField, null, $endDate))
            ->get()
            ->map(function ($row) use ($userName) {
                $item = (object) [
                    'user_name' => $userName,
                    'kode' => $row->kode ?? '-',
                    'nama' => $row->nama ?? '-',
                    'status' => $row->status_penolakan ? 'Data ditolak - ' . $row->status_penolakan : ($row->status ?? '-'),
                    'status_penolakan' => $row->status_penolakan,
                    'created_at' => $row->created_at,
                    'form_created_at' => $row->form_created_at ?? $row->created_at,
                ];

                return $item;
            });
    }

    private function jobDivisiRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $dateField = $dateColumn === 'updated_at' ? 'updated_at' : 'created_at';

        return JobDivisi::query()
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhere('updated_by', $userId);
            })
            ->when($startDate, fn ($query) => $this->applyDateFilters($query, $dateField, $startDate, null))
            ->when($endDate, fn ($query) => $this->applyDateFilters($query, $dateField, null, $endDate))
            ->get()
            ->map(function ($row) use ($userId, $userName, $dateColumn) {
                $action = ($row->created_by == $userId && $row->updated_by == $userId)
                    ? 'Membuat & mengupdate Job Divisi'
                    : (($row->created_by == $userId) ? 'Membuat Job Divisi' : 'Mengupdate Job Divisi');

                return (object) [
                    'user_name' => $userName,
                    'kode' => $row->kode ?? '-',
                    'nama' => $row->kode ?? '-',
                    'status' => $action,
                    'status_penolakan' => null,
                    'created_at' => ($dateColumn === 'updated_at') ? $row->updated_at : $row->created_at,
                    'form_created_at' => $row->created_at,
                ];
            });
    }

    private function formOrderRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $dateField = $dateColumn === 'updated_at' ? 'updated_at' : 'created_at';

        return JobDivisiFormOrder::query()
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhere('updated_by', $userId);
            })
            ->with('jobDivisi')
            ->when($startDate, fn ($query) => $this->applyDateFilters($query, $dateField, $startDate, null))
            ->when($endDate, fn ($query) => $this->applyDateFilters($query, $dateField, null, $endDate))
            ->get()
            ->map(function ($row) use ($userId, $userName, $dateColumn) {
                $action = ($row->created_by == $userId && $row->updated_by == $userId)
                    ? 'Membuat & mengupdate Form Order'
                    : (($row->created_by == $userId) ? 'Membuat Form Order' : 'Mengupdate Form Order');

                return (object) [
                    'user_name' => $userName,
                    'kode' => $row->jobDivisi->kode ?? '-',
                    'nama' => $row->nama ?? '-',
                    'status' => $action,
                    'status_penolakan' => null,
                    'created_at' => ($dateColumn === 'updated_at') ? $row->updated_at : $row->created_at,
                    'form_created_at' => $row->created_at,
                ];
            });
    }

    private function supportingDataRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $tables = [
            ['model' => new JobDivisiObjek(), 'label' => 'Data Objek', 'field' => 'job_divisi_id'],
            ['model' => new JobDivisiBadanHukum(), 'label' => 'Data Badan Hukum', 'field' => 'job_divisi_id'],
            ['model' => new JobDivisiBroker(), 'label' => 'Data Broker', 'field' => 'job_divisi_id'],
            ['model' => new JobDeveloper(), 'label' => 'Data Developer', 'field' => 'job_divisi_id'],
            ['model' => new Debitur(), 'label' => 'Data Debitur', 'field' => 'job_divisi_id'],
            ['model' => new Penjual(), 'label' => 'Data Penjual', 'field' => 'job_divisi_id'],
            ['model' => new Pembeli(), 'label' => 'Data Pembeli', 'field' => 'job_divisi_id'],
            ['model' => new JobDivisiPendirianLembaga(), 'label' => 'Data Pendirian Lembaga', 'field' => 'job_divisi_id'],
        ];

        $results = collect();

        foreach ($tables as $table) {
            $query = $table['model']->newQuery();
            $dateField = $dateColumn === 'updated_at' ? 'updated_at' : 'created_at';

            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhere('updated_by', $userId);
            });

            $this->applyDateFilters($query, $dateField, $startDate, $endDate);

            $records = $query->with('jobDivisi')->get();

            $results = $results->merge(
                $records->map(function ($row) use ($table, $userName, $dateColumn, $userId) {
                    $action = ($row->created_by == $userId && $row->updated_by == $userId)
                        ? 'Membuat & mengupdate ' . $table['label']
                        : (($row->created_by == $userId) ? 'Membuat ' . $table['label'] : 'Mengupdate ' . $table['label']);

                    return (object) [
                        'user_name' => $userName,
                        'kode' => $row->jobDivisi->kode ?? '-',
                        'nama' => $row->jobDivisi->kode ?? '-',
                        'status' => $action,
                        'status_penolakan' => null,
                        'created_at' => ($dateColumn === 'updated_at') ? $row->updated_at : $row->created_at,
                        'form_created_at' => $row->created_at,
                    ];
                })
            );
        }

        return $results;
    }

    private function pnbpRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $dateField = $dateColumn === 'updated_at' ? 'updated_at' : 'created_at';

        return Pnbp::query()
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhere('approved_by', $userId)
                    ->orWhere('user_id', $userId);
            })
            ->with('formOrder.jobDivisi')
            ->when($startDate, fn ($query) => $this->applyDateFilters($query, $dateField, $startDate, null))
            ->when($endDate, fn ($query) => $this->applyDateFilters($query, $dateField, null, $endDate))
            ->get()
            ->map(function ($row) use ($userId, $userName, $dateColumn) {
                $action = ($row->created_by == $userId)
                    ? 'Membuat PNBP'
                    : (($row->approved_by == $userId) ? 'Menangani Pembayaran PNBP' : 'Mengupdate data PNBP');

                return (object) [
                    'user_name' => $userName,
                    'kode' => $row->formOrder?->jobDivisi?->kode ?? '-',
                    'nama' => $row->formOrder?->nama ?? '-',
                    'status' => $action,
                    'status_penolakan' => null,
                    'created_at' => ($dateColumn === 'updated_at') ? $row->updated_at : $row->created_at,
                    'form_created_at' => $row->created_at,
                ];
            });
    }

    private function financeRows(int $userId, string $userName, string $dateColumn, ?string $startDate, ?string $endDate)
    {
        $dateField = $dateColumn === 'updated_at' ? 'updated_at' : 'created_at';

        return JobDivisiFinance::query()
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhere('user_id', $userId);
            })
            ->with('formOrder.jobDivisi')
            ->when($startDate, fn ($query) => $this->applyDateFilters($query, $dateField, $startDate, null))
            ->when($endDate, fn ($query) => $this->applyDateFilters($query, $dateField, null, $endDate))
            ->get()
            ->map(function ($row) use ($userId, $userName, $dateColumn) {
                $action = $row->peruntukan === 'pajak'
                    ? 'Proses Pajak'
                    : (($row->peruntukan === 'operasional') ? 'Proses Operasional' : 'Transaksi Finance');

                return (object) [
                    'user_name' => $userName,
                    'kode' => $row->formOrder?->jobDivisi?->kode ?? '-',
                    'nama' => $row->formOrder?->nama ?? '-',
                    'status' => $action,
                    'status_penolakan' => null,
                    'created_at' => ($dateColumn === 'updated_at') ? $row->updated_at : $row->created_at,
                    'form_created_at' => $row->created_at,
                ];
            });
    }
}