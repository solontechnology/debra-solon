<?php

namespace App\Services\Akta;

use App\Models\Bank;
use App\Models\JobDivisiFormOrder;
use App\Models\StatusJobOps;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AktaService
{
    private const FILTER_KEYS = [
        'parent',
        'proses',
        'nomor_akta',
        'status',
        'penugasan',
        'penugasan_qc',
        'nomor_objek',
        'nama_penghadap',
        'bank',
        'status_akad',
    ];

    public function __construct(private WorkflowAktaService $workflowAktaService) {}

    public function getIndexData(Request $request, ?string $tipe = null): array
    {
        $tipe = $tipe ?: 'notaris';
        $filters = $this->resolveFilters($request, $tipe);

        return [
            "items" => $this->getItems($filters, $tipe),
            "user_ops" => $this->getUserOps(),
            "tipe" => $tipe,
            "filters" => $filters,
            "countFilter" => collect($filters)->filter(fn($v) => filled($v))->count(),
            "prosesOptions" => $this->getProsesOptions($tipe),
            "statusOptions" => $this->getStatusOptions($tipe),
            "bankOptions" => Bank::orderBy('nama')->get(),
            "statusAkadOptions" => ['Pra Akad', 'Akad', 'Batal Akad', 'Selesai'],
        ];
    }

    /** Simpan/hapus/baca filter dari session (per tipe). */
    // private function resolveFilters(Request $request, string $tipe): array
    // {
    //     $sessionKey = 'akta_filter_' . $tipe;

    //     if ($request->boolean('reset_filter')) {
    //         session()->forget($sessionKey);
    //     } elseif ($request->has('apply_filter')) {
    //         session()->put($sessionKey, $request->only(self::FILTER_KEYS));
    //     }

    //     $saved = session($sessionKey, []);

    //     return collect(self::FILTER_KEYS)
    //         ->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])
    //         ->all();
    // }
    private function resolveFilters(Request $request, string $tipe, bool $persist = true): array
    {
        $sessionKey = 'akta_filter_' . $tipe;

        if ($persist) {
            if ($request->boolean('reset_filter')) {
                session()->forget($sessionKey);
            } elseif ($request->has('apply_filter')) {
                session()->put($sessionKey, $request->only(self::FILTER_KEYS));
            }
        }

        $saved = session($sessionKey, []);

        return collect(self::FILTER_KEYS)
            ->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])
            ->all();
    }
    public function exportQuery(string $tipe): Builder
    {
        $filters = $this->resolveFilters(request(), $tipe, false);

        return $this->baseQuery($filters, $tipe)->with([
            'jobDivisi.jenisAkad',
            'jobDivisi.listBank',
            'jobDivisi.debitur',
            'jobDivisi.objek',
            'statusJobOps.user',
            'statusJobOps.createdBy',
            'nomorPpat',
        ]);
    }

    private function getWorkflow(string $tipe): array
    {
        return $this->workflowAktaService->forCategory($tipe);
    }

    private function getStatusOptions(string $tipe)
    {
        $workflow = $this->getWorkflow($tipe);

        $existingStatusOptions = StatusJobOps::query()
            ->join('job_divisi_form_orders', 'status_job_ops.job_divisi_form_order_id', '=', 'job_divisi_form_orders.id')
            ->where('job_divisi_form_orders.kategori', $tipe)
            ->whereNotNull('status_job_ops.status')
            ->select('status_job_ops.status')
            ->distinct()
            ->orderBy('status_job_ops.status')
            ->pluck('status_job_ops.status');

        return collect($workflow)
            ->pluck('name')
            ->merge($existingStatusOptions)
            ->prepend('Belum diproses')
            ->push('Perlu Perbaikan')
            ->unique()
            ->values();
    }

    private function getProsesOptions(string $tipe)
    {
        return JobDivisiFormOrder::query()
            ->whereNotIn("status", ["rejected", "Dibatalkan", "pending"])
            ->where('kategori', $tipe)
            ->whereHas("jobDivisi")
            ->select('nama')
            ->distinct()
            ->orderBy('nama')
            ->pluck('nama');
    }

    private function baseQuery(array $filters, string $tipe)
    {
        $latestStatus = 'status_job_ops.id = (
            select max(s2.id) from status_job_ops s2
            where s2.job_divisi_form_order_id = job_divisi_form_orders.id
        )';

        return JobDivisiFormOrder::query()
            ->whereNotIn("status", ["rejected", "Dibatalkan", "pending"])
            ->where('kategori', $tipe)
            ->whereHas("jobDivisi")
            ->orderBy("id", "desc")
            ->when($filters['parent'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('kode', 'LIKE', "%$v%")))
            ->when($filters['proses'], fn($q, $v) => $q->where('nama', $v))
            ->when($filters['nomor_akta'], fn($q, $v) => $q->whereHas('nomorPpat', fn($n) => $n->where('nomor', 'LIKE', "%$v%")))
            ->when($filters['status'], fn($q, $v) => $this->applyStatusFilter($q, $v))
            ->when($filters['penugasan'], fn($q, $v) => $q->whereHas('statusJobOps', fn($s) => $s->where('user_id', $v)->whereRaw($latestStatus)))
            ->when($filters['penugasan_qc'], fn($q, $v) => $q->whereHas('statusJobOps', fn($s) => $s->where('next_user', $v)->whereRaw($latestStatus)))
            ->when($filters['nomor_objek'], fn($q, $v) => $q->whereHas('jobDivisi.objek', fn($o) => $o->where('no_sertifikat', 'LIKE', "%$v%")))
            ->when($filters['nama_penghadap'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where(function ($j) use ($v) {
                $j->whereHas('debitur', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPembeli', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPenjual', fn($d) => $d->where('nama', 'LIKE', "%$v%"));
            })))
            ->when($filters['bank'], fn($q, $v) => $q->whereHas('jobDivisi.listBank', fn($b) => $b->where('job_banks.bank_id', $v)))
            ->when($filters['status_akad'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('status', $v)));
    }

    private function getItems(array $filters, string $tipe)
    {
        return $this->baseQuery($filters, $tipe)
            ->with([
                'jobDivisi.jenisAkad',
                'jobDivisi.listBank',
                'jobDivisi.debitur',
                'jobDivisi.objek',
                'statusJobOps.createdBy',
                'statusJobOps.user',
                'statusJobOps.nextUser',
                'statusJobOps.workflowStage.users',
                'statusJobOps.workflowStage.roles',
                'statusJobOps.workflowStage.assignerUsers',
                'statusJobOps.workflowStage.assignerRoles',
                'statusJobOps.approvedBy',
                'nomorPpat',
            ])
            ->paginate(10)
            ->withQueryString()
            ->appends(['apply_filter' => null, 'reset_filter' => null]);
    }

    public function getItemsForExport(Request $request, string $tipe)
    {
        // Export ikut filter yang lagi aktif di session
        $filters = $this->resolveFilters($request, $tipe);

        return $this->baseQuery($filters, $tipe)
            ->with([
                'jobDivisi.jenisAkad',
                'jobDivisi.listBank',
                'jobDivisi.debitur',
                'jobDivisi.objek',
                'statusJobOps.createdBy',
                'statusJobOps.user',
                'statusJobOps.nextUser',
                'nomorPpat',
            ])
            ->get();
    }

    private function applyStatusFilter($query, string $status)
    {
        $latest = 'status_job_ops.id = (
            select max(l.id) from status_job_ops as l
            where l.job_divisi_form_order_id = job_divisi_form_orders.id
        )';

        if ($status === 'Belum diproses') {
            return $query->doesntHave('statusJobOps');
        }

        if ($status === 'Perlu Perbaikan') {
            return $query->whereHas('statusJobOps', fn($q) => $q->whereNotNull('status_penolakan')->whereRaw($latest));
        }

        return $query->whereHas('statusJobOps', fn($q) => $q->where('status', $status)->whereNull('status_penolakan')->whereRaw($latest));
    }

    private function getUserOps()
    {
        return User::orderBy('name', 'asc')->get()->map(fn($user) => [
            'value' => (string) $user->id,
            'label' => $user->name,
        ]);
    }
}
