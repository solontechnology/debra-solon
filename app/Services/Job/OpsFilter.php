<?php

namespace App\Services\Job;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpsFilter
{
    public const KEYS = [
        'parent',
        'status_akad',
        'proses',
        'nomor_objek',
        'nama_penghadap',
        'bank',
        'biaya_min',
        'biaya_max',
        'status_pengerjaan',
    ];

    public const BIAYA_COLUMN = 'harga_proses';

    private const SESSION_KEY = 'ops_filter';
    private const J = 'job_divisi_form_orders.job_divisi_id';

    private const SORT_COLUMNS = [
        'parent' => [
            'type' => 'text',
            'expr' => '(select j.kode from job_divisis j where j.id = job_divisi_form_orders.job_divisi_id limit 1)',
        ],
        'status_akad' => [
            'type' => 'text',
            'expr' => '(select j.status from job_divisis j where j.id = job_divisi_form_orders.job_divisi_id limit 1)',
        ],
        'proses' => [
            'type' => 'text',
            'expr' => 'job_divisi_form_orders.nama',
        ],
        'nomor_objek' => [
            'type' => 'text',
            'expr' => '(select min(o.no_sertifikat) from job_divisi_objeks o where o.job_divisi_id = job_divisi_form_orders.job_divisi_id)',
        ],
        // prioritas ngikutin view: Debitur, Pembeli, Penjual
        'nama_penghadap' => [
            'type' => 'text',
            'expr' => "COALESCE(
            NULLIF((select min(d.nama) from debiturs d where d.job_divisi_id = job_divisi_form_orders.job_divisi_id), ''),
            NULLIF((select min(p.nama) from pembelis p where p.job_divisi_id = job_divisi_form_orders.job_divisi_id), ''),
            NULLIF((select min(j.nama) from penjuals j where j.job_divisi_id = job_divisi_form_orders.job_divisi_id), '')
        )",
        ],
        'bank' => [
            'type' => 'text',
            'expr' => '(select min(b.nama_bank) from job_banks b where b.job_divisi_id = job_divisi_form_orders.job_divisi_id)',
        ],
        'biaya_proses' => [
            'type' => 'number',
            'expr' => 'job_divisi_form_orders.harga_proses',
        ],
        // sama kayak view: dispo -> 'Dispo', kalau gak ada status -> 'Belum dikerjakan'
        'status_pengerjaan' => [
            'type' => 'text',
            'expr' => "CASE WHEN job_divisi_form_orders.status = 'dispo' THEN 'Dispo'
            ELSE COALESCE((select s.status from status_job_ops s
                where s.job_divisi_form_order_id = job_divisi_form_orders.id
                order by s.id desc limit 1), 'Belum dikerjakan') END",
        ],
    ];

    /** Whitelist: input URL cuma dipakai buat milih key, bukan masuk query. */
    public static function sortKey($input): ?string
    {
        if (!is_string($input) || !isset(self::SORT_COLUMNS[$input])) {
            return null;
        }
        // kolom biaya cuma boleh buat yang punya izin
        if ($input === 'biaya_proses' && !Auth::user()->can('job/ops/biaya proses')) {
            return null;
        }
        return $input;
    }

    public static function sortDirection($input): string
    {
        return in_array($input, ['asc', 'desc', 'empty'], true) ? $input : 'asc';
    }

    public static function applySort($query, ?string $sort, string $direction)
    {
        $col = self::SORT_COLUMNS[$sort] ?? null;

        // gak ada sort aktif: urutan default (id desc) tetap jalan
        if (!$col) {
            return $query;
        }

        $query->reorder();
        $expr = $col['expr'];

        $isEmpty = $col['type'] === 'text'
            ? "NULLIF(TRIM($expr), '') IS NULL"
            : "($expr) IS NULL";

        if ($direction === 'empty') {
            $query->orderByRaw("$isEmpty DESC")->orderByRaw("($expr) ASC");
        } else {
            $query->orderByRaw("$isEmpty ASC")
                ->orderByRaw("($expr) " . ($direction === 'desc' ? 'DESC' : 'ASC'));
        }

        return $query->orderByDesc('job_divisi_form_orders.id');
    }

    /** $persist = false dipakai export: baca session saja, tanpa simpan/hapus. */
    public static function resolve(Request $request, bool $persist = true): array
    {
        if ($persist) {
            if ($request->boolean('reset_filter')) {
                session()->forget(self::SESSION_KEY);
            } elseif ($request->has('apply_filter')) {
                session()->put(self::SESSION_KEY, $request->only(self::KEYS));
            }
        }

        $saved   = session(self::SESSION_KEY, []);
        $filters = collect(self::KEYS)->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])->all();

        if (!Auth::user()->can('job/ops/biaya proses')) {
            $filters['biaya_min'] = $filters['biaya_max'] = null;
        }

        return $filters;
    }

    public static function count(array $filters): int
    {
        return collect($filters)->filter(fn($v) => filled($v))->count();
    }

    public static function apply($query, array $filters)
    {
        $min = filled($filters['biaya_min']) ? (int) preg_replace('/\D/', '', $filters['biaya_min']) : null;
        $max = filled($filters['biaya_max']) ? (int) preg_replace('/\D/', '', $filters['biaya_max']) : null;

        return $query
            ->when($filters['parent'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('kode', 'LIKE', "%$v%")))
            ->when($filters['status_akad'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('status', $v)))
            ->when($filters['proses'], fn($q, $v) => $q->where('nama', $v))
            ->when($filters['nomor_objek'], fn($q, $v) => $q->whereHas('jobDivisi.objek', fn($o) => $o->where('no_sertifikat', 'LIKE', "%$v%")))
            ->when($filters['nama_penghadap'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where(function ($j) use ($v) {
                $j->whereHas('debitur', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPembeli', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPenjual', fn($d) => $d->where('nama', 'LIKE', "%$v%"));
            })))
            ->when($filters['bank'], fn($q, $v) => $q->whereHas('jobDivisi.listBank', fn($b) => $b->where('nama_bank', $v)))
            ->when($min !== null, fn($q) => $q->where(self::BIAYA_COLUMN, '>=', $min))
            ->when($max !== null, fn($q) => $q->where(self::BIAYA_COLUMN, '<=', $max))
            ->when($filters['status_pengerjaan'], function ($q, $v) {
                if ($v === 'belum dikerjakan') {
                    return $q->doesntHave('statusJobOps');
                }
                if ($v === 'dispo') {
                    return $q->where('status', 'dispo');
                }
                return $q->whereHas('statusJobOps', fn($s) => $s->where('status', $v));
            });
    }
}
