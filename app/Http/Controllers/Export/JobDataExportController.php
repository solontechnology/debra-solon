<?php

namespace App\Http\Controllers\Export;

use App\Exports\SelectedJobDataExport;
use App\Http\Controllers\Controller;
use App\Models\JobDivisi;
use App\Models\JobDivisiFormOrder;
use App\Models\PembatalanItem;
use App\Models\PenambahanItemJobDivisi;
use App\Services\Akta\AktaService;
use App\Services\Job\JobDivisiFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JobDataExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $config = config('job_exports');
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys($config))],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string'],
            'q' => ['nullable', 'string', 'max:150'],
            'parent' => ['nullable', 'string', 'max:150'],
            'proses' => ['nullable', 'string', 'max:150'],
            'nomor_objek' => ['nullable', 'string', 'max:150'],
            'nama_debitur' => ['nullable', 'string', 'max:150'],
            'nama_bank' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:100'],
            'status_akad' => ['nullable', 'string', 'max:100'],
            'status_pengerjaan' => ['nullable', 'string', 'max:100'],
            'nomor_akta' => ['nullable', 'string', 'max:150'],
            'penugasan' => ['nullable', 'string', 'max:150'],
            'penugasan_qc' => ['nullable', 'string', 'max:150'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'scope' => ['nullable', 'in:aktif,selesai'],
        ]);

        $type = $validated['type'];
        $definition = $config[$type];
        abort_unless(Auth::user()->canAny([
            $definition['permission'],
            str_replace('/list', '/approve', $definition['permission']),
        ]), 403);

        $allowed = $this->allowedFields($definition);

        $fields = array_values(array_intersect($validated['fields'], array_keys($allowed)));
        abort_if($fields === [], 422, 'Pilih minimal satu kolom yang akan diekspor.');

        $items = $this->queryItems($type, $validated);

        $filename = 'export-' . str_replace(['/', ' '], '-', strtolower($definition['label'])) . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new SelectedJobDataExport(
                $items,
                array_map(fn($field) => $allowed[$field], $fields),
                fn($item) => array_map(
                    fn($field) => $this->valueFor($item, $field),
                    $fields
                )
            ),
            $filename
        );
    }

    private function queryItems(string $type, array $filters): Builder
    {
        if ($type === 'operasional') {
            $query = JobDivisiFormOrder::query()
                ->with([
                    'jobDivisi.listBank',
                    'jobDivisi.debitur',
                    'jobDivisi.listPembeli',
                    'jobDivisi.listPenjual',
                    'jobDivisi.objek',
                    'statusJobOps',
                ])
                ->whereNotIn('status', ['rejected', 'Dibatalkan'])
                ->where('kategori', 'operasional')
                ->whereHas('jobDivisi')
                ->orderByDesc('id');

            if (Auth::user()->roles()->first()?->name === 'OPS staff') {
                $query->whereHas('statusJobOps', fn($s) => $s->where('status', 'Penugasan')->where('user_id', Auth::id()));
            }

            return \App\Services\Job\OpsFilter::apply(
                $query,
                \App\Services\Job\OpsFilter::resolve(request(), false)
            );
        }

        if ($type === 'pajak') {
            $query = JobDivisiFormOrder::query()
                ->with([
                    'jobDivisi.listBank',
                    'jobDivisi.debitur',
                    'jobDivisi.objek',
                    'statusJobOps',
                ])
                ->whereNotIn('status', ['rejected', 'Dibatalkan'])
                ->where('kategori', 'pajak')
                ->whereHas('jobDivisi')
                ->orderByDesc('id');

            return \App\Services\Job\PajakFilter::apply(
                $query,
                \App\Services\Job\PajakFilter::resolve(request(), false)
            );
        }

        if ($type === 'pnbp') {
            $query = JobDivisiFormOrder::query()
                ->with([
                    'jobDivisi.listBank',
                    'jobDivisi.debitur',
                    'jobDivisi.listPembeli',
                    'jobDivisi.listPenjual',
                    'jobDivisi.objek',
                    'pnbp.user',
                ])
                ->whereNotIn('status', ['rejected', 'Dibatalkan', 'pending'])
                ->where('kategori', 'pnbp_voucher')
                ->whereHas('jobDivisi')
                ->orderByDesc('id');

            return \App\Services\Job\PnbpFilter::apply(
                $query,
                \App\Services\Job\PnbpFilter::resolve(request(), false)
            );
        }
        $aktaTypes = ['notaris', 'ppat', 'legalisasi', 'waarmerking', 'surat-keluar', 'wasiat', 'covernot'];
        if (in_array($type, $aktaTypes, true)) {
            return app(AktaService::class)->exportQuery($type);
        }
        if ($type === 'divisi') {
            $completedOnly = ($filters['scope'] ?? 'aktif') === 'selesai';
            $saved = JobDivisiFilter::resolve(request(), $completedOnly, false);

            return JobDivisiFilter::apply(
                JobDivisi::query()
                    ->with([
                        'jenisAkad',
                        'listBank',
                        'debitur',
                        'listPembeli',
                        'listPenjual',
                        'objek',
                        'formOrder',
                        'penanggungJawab',
                        'pembuat',
                    ])
                    ->orderByDesc('id'),
                $saved,
                $completedOnly
            );
        }
        if ($type === 'divisi') {
            $query = JobDivisi::query()->with([
                'jenisAkad',
                'listBank',
                'debitur',
                'objek',
                'formOrder',

            ])->orderByDesc('id');
        } elseif ($type === 'penambahan-item') {
            $query = PenambahanItemJobDivisi::query()->with([
                'jobDivisi.jenisAkad',
                'jobDivisi.listBank',
                'jobDivisi.debitur',
                'jobDivisi.objek',
                'detail.pekerjaan',
                'user',
                'userApprove',
            ])->orderByDesc('id');
        } elseif ($type === 'pembatalan-item') {
            $query = PembatalanItem::query()->with([
                'jobDivisi.jenisAkad',
                'jobDivisi.listBank',
                'jobDivisi.debitur',
                'jobDivisi.objek',
                'detail.formOrder',
                'user',
                'userApprove',
            ])->orderByDesc('id');
        } else {
            $query = JobDivisiFormOrder::query()->with([
                'jobDivisi.jenisAkad',
                'jobDivisi.listBank',
                'jobDivisi.debitur',
                'jobDivisi.objek',
                'statusJobOps.user',
                'statusJobOps.createdBy',
                'nomorPpat',
                'pnbp.user',
            ])->where('kategori', config("job_exports.{$type}.category"))
                ->whereNotIn('status', ['rejected', 'Dibatalkan'])
                ->whereHas('jobDivisi')
                ->orderByDesc('id');

            if ($type === 'operasional' && Auth::user()->roles()->where('name', 'OPS staff')->exists()) {
                $query->whereHas('statusJobOps', fn(Builder $status) => $status
                    ->where('status', 'Penugasan')
                    ->where('user_id', Auth::id()));
            }
        }

        $jobRelation = $type === 'divisi' ? null : 'jobDivisi.';
        $query->when($filters['parent'] ?? null, function (Builder $query, $value) use ($type) {
            if ($type === 'divisi') {
                return $query->where('kode', 'like', "%{$value}%");
            }

            return $query->whereHas('jobDivisi', fn(Builder $job) => $job->where('kode', 'like', "%{$value}%"));
        });
        $query->when($filters['proses'] ?? null, function (Builder $query, $value) use ($type) {
            if (in_array($type, ['penambahan-item', 'pembatalan-item'], true)) {
                $relation = $type === 'penambahan-item' ? 'detail.pekerjaan' : 'detail.formOrder';

                return $query->whereHas($relation, fn(Builder $process) => $process->where('nama', 'like', "%{$value}%"));
            }
            if ($type === 'divisi') {
                return $query->whereHas('formOrder', fn(Builder $process) => $process->where('nama', 'like', "%{$value}%"));
            }

            return $query->where('nama', 'like', "%{$value}%");
        });
        $query->when($filters['nomor_objek'] ?? null, fn(Builder $query, $value) => $query
            ->whereHas($jobRelation . 'objek', fn(Builder $job) => $job->where('no_sertifikat', 'like', "%{$value}%")));
        $query->when($filters['nama_debitur'] ?? null, fn(Builder $query, $value) => $query
            ->whereHas($jobRelation . 'debitur', fn(Builder $job) => $job->where('nama', 'like', "%{$value}%")));
        $query->when($filters['nama_bank'] ?? null, fn(Builder $query, $value) => $query
            ->whereHas($jobRelation . 'listBank', fn(Builder $job) => $job->where('nama_bank', 'like', "%{$value}%")));
        $query->when($filters['status'] ?? null, function (Builder $query, $value) use ($type) {
            if ($type === 'pnbp') {
                if ($value === 'Belum dikerjakan') {
                    return $query->doesntHave('pnbp');
                }

                return $query->whereHas('pnbp', fn(Builder $pnbp) => $pnbp->where('status', $value));
            }
            if (in_array($type, ['notaris', 'ppat', 'legalisasi', 'waarmerking', 'surat-keluar', 'wasiat', 'covernot'], true)) {
                return $this->whereLatestAktaStatus($query, $value);
            }

            return $query->where('status', $value);
        });
        $query->when($filters['status_akad'] ?? null, function (Builder $query, $value) use ($type) {
            return $type === 'divisi'
                ? $query->where('status', $value)
                : $query->whereHas('jobDivisi', fn(Builder $job) => $job->where('status', $value));
        });
        $query->when($filters['status_pengerjaan'] ?? null, function (Builder $query, $value) use ($type) {
            if ($type !== 'operasional') {
                return $query;
            }
            if ($value === 'belum dikerjakan') {
                return $query->doesntHave('statusJobOps');
            }
            if ($value === 'dispo') {
                return $query->where('status', 'dispo');
            }

            return $query->whereHas('statusJobOps', fn(Builder $status) => $status->where('status', $value));
        });
        if (! in_array($type, ['divisi', 'penambahan-item', 'pembatalan-item'], true)) {
            $query->when($filters['nomor_akta'] ?? null, fn(Builder $query, $value) => $query
                ->whereHas('nomorPpat', fn(Builder $number) => $number->where('nomor', 'like', "%{$value}%")));
            $query->when($filters['penugasan'] ?? null, fn(Builder $query, $value) => $query
                ->whereHas('statusJobOps', fn(Builder $status) => $status->whereHas('user', fn(Builder $user) => $user
                    ->where('name', 'like', "%{$value}%"))
                    ->whereRaw('status_job_ops.id = (select max(s2.id) from status_job_ops s2 where s2.job_divisi_form_order_id = job_divisi_form_orders.id)')));
            $query->when($filters['penugasan_qc'] ?? null, fn(Builder $query, $value) => $query
                ->whereHas('statusJobOps', fn(Builder $status) => $status->whereHas('nextUser', fn(Builder $user) => $user
                    ->where('name', 'like', "%{$value}%"))
                    ->whereRaw('status_job_ops.id = (select max(s2.id) from status_job_ops s2 where s2.job_divisi_form_order_id = job_divisi_form_orders.id)')));
        }
        $query->when($filters['tanggal_mulai'] ?? null, fn(Builder $query, $value) => $query
            ->whereDate('created_at', '>=', $value));
        $query->when($filters['tanggal_selesai'] ?? null, fn(Builder $query, $value) => $query
            ->whereDate('created_at', '<=', $value));
        $query->when($filters['q'] ?? null, function (Builder $query, $value) use ($type) {
            $query->where(function (Builder $search) use ($value, $type) {
                if ($type === 'divisi') {
                    return $search->where('kode', 'like', "%{$value}%")
                        ->orWhereHas('debitur', fn(Builder $debitur) => $debitur->where('nama', 'like', "%{$value}%"));
                }
                if (in_array($search->getModel()::class, [PenambahanItemJobDivisi::class, PembatalanItem::class], true)) {
                    $search->where('kode', 'like', "%{$value}%");
                }
                $search->orWhereHas('jobDivisi', fn(Builder $job) => $job->where('kode', 'like', "%{$value}%"))
                    ->orWhereHas('jobDivisi.debitur', fn(Builder $debitur) => $debitur->where('nama', 'like', "%{$value}%"));
            });
        });

        return $query;
    }

    private function valueFor(object $item, string $field): mixed
    {
        $job = $item instanceof JobDivisi ? $item : $item->jobDivisi;
        $names = fn($relation, $attribute) => $relation?->pluck($attribute)->filter()->implode(', ') ?? '';

        return match ($field) {
            'parent' => $job?->kode ?? '',
            'process' => $item instanceof JobDivisi
                ? ($item->formOrder?->pluck('nama')->filter()->implode(', ') ?? '')
                : ($item->nama ?? $item->detail?->map(fn($detail) => $detail->pekerjaan->nama ?? $detail->formOrder->nama ?? null)->filter()->implode(', ') ?? ''),
            'debtor' => $names($job?->debitur, 'nama'),
            'akad_type' => $job?->jenisAkad?->nama ?? '',
            'bank' => $names($job?->listBank, 'nama_bank'),
            'object' => $names($job?->objek, 'no_sertifikat'),
            // 'status' => $item instanceof JobDivisi
            //     ? ($item->is_pending ? 'Pending' : ($item->status ?? ''))
            //     : ($item->pnbp?->status ?? $item->statusJobOps?->last()?->status ?? $item->status ?? ''),
            // 'status' => $item->pnbp?->status ?? $item->statusJobOps?->last()?->status ?? $item->status ?? '',
            // 'status' => $item instanceof JobDivisi
            //     ? ($item->is_pending ? 'Pending' : ($item->status ?? ''))
            //     : ($item->pnbp?->status ?? $item->statusJobOps?->last()?->status
            //         ?? ($item->kategori === 'pajak' ? 'Belum dikerjakan' : ($item->status ?? ''))),
            'status' => $item instanceof JobDivisi
                ? ($item->is_pending ? 'Pending' : ($item->status ?? ''))
                : match ($item->kategori) {
                    'pnbp_voucher' => $item->pnbp?->status ?? 'Belum dikerjakan',
                    'pajak'        => $item->statusJobOps?->last()?->status ?? 'Belum dikerjakan',
                    default        => $item->statusJobOps?->last()?->status ?? $item->status ?? '',
                },
            'created_at' => $item->created_at?->format('Y-m-d') ?? '',
            // 'akad_date' => $this->formatDate($job?->tanggal_akad),
            'akad_date' => $this->formatDate($job?->tanggal_akad ?? $job?->tanggal_rencana_akad),
            'estimate_internal' => $this->formatDate($job?->tanggal_estimasi_selesai),
            'estimate_external' => $this->formatDate($job?->tanggal_estimasi_selesai_eksternal),
            'number' => $item->nomorPpat?->nomor ?? $item->nomorPpat?->nomor_covernote ?? '',
            'assigned_to' => $item->statusJobOps?->map(fn($status) => $status->user?->name)->filter()->implode(', ') ?? '',
            'remarks' => $item->keterangan ?? $item->catatan ?? '',
            'virtual_account' => $item->pnbp?->va ?? '',
            'amount' => $item->pnbp?->nominal ?? '',
            'request_code' => $item->kode ?? '',
            'created_by' => $item->user?->name ?? '',
            'approved_by' => $item->userApprove?->name ?? '',
            default => '',
            'assigned_qc' => $item->statusJobOps?->last()?->nextUser?->name ?? '',
            'participant' => collect([$job?->debitur, $job?->listPembeli, $job?->listPenjual])
                ->flatMap(fn($list) => $list?->pluck('nama') ?? collect())
                ->filter()->unique()->implode(', '),
            'status_akad' => $job?->status ?? '',
            // 'progress' => $this->divisiProgress($item),
            'person_in_charge' => $job?->penanggungJawab?->name ?? '',
            'created_by_user' => $job?->pembuat?->name ?? '',
            'participant' => collect([$job?->debitur, $job?->listPembeli, $job?->listPenjual])
                ->flatMap(fn($list) => $list?->pluck('nama') ?? collect())
                ->filter()->unique()->implode(', '),
            'status_akad' => $job?->status ?? '',
            'process_fee' => $item->{\App\Services\Job\OpsFilter::BIAYA_COLUMN} ?? '',
            'work_status' => $item->status === 'dispo'
                ? 'Dispo'
                : ($item->statusJobOps->last()?->status ?? 'Belum dikerjakan'),
            'tax_payment' => $this->pajakFinance($item)->sum('total') ?: '',
            'billing_code' => $this->pajakFinance($item)->pluck('kode_billing')->filter()->unique()->implode(', '),
            'pnbp_officer' => $item->pnbp?->user?->name ?? '',
        };
    }

    private function formatDate(mixed $value): string
    {
        return filled($value) ? Carbon::parse($value)->format('Y-m-d') : '';
    }

    private function whereLatestAktaStatus(Builder $query, string $status): Builder
    {
        if ($status === 'Belum diproses') {
            return $query->doesntHave('statusJobOps');
        }
        if ($status === 'Perlu Perbaikan') {
            return $query->whereHas('statusJobOps', fn(Builder $statuses) => $statuses
                ->whereNotNull('status_penolakan')
                ->whereRaw('status_job_ops.id = (select max(s2.id) from status_job_ops s2 where s2.job_divisi_form_order_id = job_divisi_form_orders.id)'));
        }

        return $query->whereHas('statusJobOps', fn(Builder $statuses) => $statuses
            ->where('status', $status)
            ->whereNull('status_penolakan')
            ->whereRaw('status_job_ops.id = (select max(s2.id) from status_job_ops s2 where s2.job_divisi_form_order_id = job_divisi_form_orders.id)'));
    }

    private function allowedFields(array $definition): array
    {
        return collect($definition['fields'])
            ->filter(fn($f) => !is_array($f)
                || empty($f['permission'])
                || Auth::user()->can($f['permission']))
            ->map(fn($f) => is_array($f) ? $f['label'] : $f)
            ->all();
    }

    private function pajakFinance(JobDivisiFormOrder $item)
    {
        return \App\Models\JobDivisiFinance::query()
            ->where('job_divisi_form_order_id', $item->id)
            ->where('peruntukan', 'pajak')
            ->get(['total', 'kode_billing']);
    }
}
