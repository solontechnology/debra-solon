<?php

namespace App\Services\Job;

use Illuminate\Http\Request;

class PnbpFilter
{
    public const KEYS = [
        'parent', 'proses', 'nomor_objek', 'nama_penghadap', 'nama_bank',
        'petugas', 'status_akad', 'status', 'nomor_va', 'nominal_min', 'nominal_max',
    ];

    private const SESSION_KEY = 'pnbp_filter';
    private const SORT_COLUMNS = [
    'parent' => [
        'type' => 'text',
        'expr' => '(select j.kode from job_divisis j where j.id = job_divisi_form_orders.job_divisi_id limit 1)',
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
    'nama_bank' => [
        'type' => 'text',
        'expr' => '(select min(b.nama_bank) from job_banks b where b.job_divisi_id = job_divisi_form_orders.job_divisi_id)',
    ],
    // view nampilin $item->pnbp->user->name
    'petugas' => [
        'type' => 'text',
        'expr' => '(select u.name from pnbps p
            join users u on u.id = p.user_id
            where p.job_divisi_form_order_id = job_divisi_form_orders.id
            order by p.id asc limit 1)',
    ],
    'status_akad' => [
        'type' => 'text',
        'expr' => '(select j.status from job_divisis j where j.id = job_divisi_form_orders.job_divisi_id limit 1)',
    ],
    // sama kayak view: gak ada pnbp = 'Belum dikerjakan', Terbayar/Selesai = 'Selesai'
    'status' => [
        'type' => 'text',
        'expr' => "COALESCE((select CASE WHEN p.status IN ('Terbayar', 'Selesai') THEN 'Selesai' ELSE p.status END
            from pnbps p
            where p.job_divisi_form_order_id = job_divisi_form_orders.id
            order by p.id asc limit 1), 'Belum dikerjakan')",
    ],
    'nomor_va' => [
        'type' => 'text',
        'expr' => '(select p.va from pnbps p
            where p.job_divisi_form_order_id = job_divisi_form_orders.id
            order by p.id asc limit 1)',
    ],
    // view nampilin nominal ?? 0, jadi yang gak ada pnbp dihitung 0
    'nominal' => [
        'type' => 'number',
        'expr' => 'COALESCE((select p.nominal from pnbps p
            where p.job_divisi_form_order_id = job_divisi_form_orders.id
            order by p.id asc limit 1), 0)',
    ],
];

/** Whitelist: input URL cuma dipakai buat milih key, bukan masuk query. */
public static function sortKey($input): ?string
{
    return is_string($input) && isset(self::SORT_COLUMNS[$input]) ? $input : null;
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

    /** $persist = false dipakai export: baca session saja. */
    public static function resolve(Request $request, bool $persist = true): array
    {
        if ($persist) {
            if ($request->boolean('reset_filter')) {
                session()->forget(self::SESSION_KEY);
            } elseif ($request->has('apply_filter')) {
                session()->put(self::SESSION_KEY, $request->only(self::KEYS));
            }
        }

        $saved = session(self::SESSION_KEY, []);

        return collect(self::KEYS)->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])->all();
    }

    public static function count(array $filters): int
    {
        return collect($filters)->filter(fn($v) => filled($v))->count();
    }

    public static function apply($query, array $filters)
    {
        $min = filled($filters['nominal_min']) ? (int) preg_replace('/\D/', '', $filters['nominal_min']) : null;
        $max = filled($filters['nominal_max']) ? (int) preg_replace('/\D/', '', $filters['nominal_max']) : null;

        return $query
            ->when($filters['parent'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('kode', 'LIKE', "%$v%")))
            ->when($filters['proses'], fn($q, $v) => $q->where('nama', $v))
            ->when($filters['nomor_objek'], fn($q, $v) => $q->whereHas('jobDivisi.objek', fn($o) => $o->where('no_sertifikat', 'LIKE', "%$v%")))
            ->when($filters['nama_penghadap'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where(function ($j) use ($v) {
                $j->whereHas('debitur', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPembeli', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPenjual', fn($d) => $d->where('nama', 'LIKE', "%$v%"));
            })))
            ->when($filters['nama_bank'], fn($q, $v) => $q->whereHas('jobDivisi.listBank', fn($b) => $b->where('nama_bank', $v)))
            ->when($filters['petugas'], fn($q, $v) => $q->whereHas('pnbp', fn($p) => $p->where('user_id', $v)))
            ->when($filters['status_akad'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('status', $v)))
            ->when($filters['status'], function ($q, $v) {
                if ($v === 'Belum dikerjakan') {
                    return $q->doesntHave('pnbp');
                }
                return $q->whereHas('pnbp', fn($p) => $p->where('status', $v));
            })
            ->when($filters['nomor_va'], fn($q, $v) => $q->whereHas('pnbp', fn($p) => $p->where('va', 'LIKE', "%$v%")))
            ->when($min !== null, fn($q) => $q->whereHas('pnbp', fn($p) => $p->where('nominal', '>=', $min)))
            ->when($max !== null, fn($q) => $q->whereHas('pnbp', fn($p) => $p->where('nominal', '<=', $max)));
    }
}