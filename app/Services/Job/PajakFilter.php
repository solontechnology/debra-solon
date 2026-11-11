<?php

namespace App\Services\Job;

use App\Models\JobDivisiFinance;
use Illuminate\Http\Request;

class PajakFilter
{
    public const KEYS = [
        'parent',
        'proses',
        'nomor_objek',
        'nama_penghadap',
        'nama_bank',
        'pembayaran_min',
        'pembayaran_max',
        'kode_billing',
        'status_akad',
        'status',
    ];

    private const SESSION_KEY = 'pajak_filter';
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
    // view nampilin $item->harga_jual
    'pembayaran' => [
        'type' => 'number',
        'expr' => 'job_divisi_form_orders.harga_jual',
    ],
    // view nampilin $item->finance->kode_billing (hasOne = baris finance pertama)
    'kode_billing' => [
        'type' => 'text',
        'expr' => '(select f.kode_billing from job_divisi_finances f
            where f.job_divisi_form_order_id = job_divisi_form_orders.id
            order by f.id asc limit 1)',
    ],
    'status_akad' => [
        'type' => 'text',
        'expr' => '(select j.status from job_divisis j where j.id = job_divisi_form_orders.job_divisi_id limit 1)',
    ],
    // status terakhir, kalau belum ada = 'Belum dikerjakan' (sama kayak view)
    'status' => [
        'type' => 'text',
        'expr' => "COALESCE((select s.status from status_job_ops s
            where s.job_divisi_form_order_id = job_divisi_form_orders.id
            order by s.id desc limit 1), 'Belum dikerjakan')",
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
        $min = filled($filters['pembayaran_min']) ? (int) preg_replace('/\D/', '', $filters['pembayaran_min']) : null;
        $max = filled($filters['pembayaran_max']) ? (int) preg_replace('/\D/', '', $filters['pembayaran_max']) : null;

        $finance = fn() => JobDivisiFinance::query()
            ->select('job_divisi_form_order_id')
            ->where('peruntukan', 'pajak');

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
            ->when($filters['kode_billing'], fn($q, $v) => $q->whereIn('id', $finance()->where('kode_billing', 'LIKE', "%$v%")))
            ->when($min !== null, fn($q) => $q->whereIn('id', $finance()->where('total', '>=', $min)))
            ->when($max !== null, fn($q) => $q->whereIn('id', $finance()->where('total', '<=', $max)))
            ->when($filters['status_akad'], fn($q, $v) => $q->whereHas('jobDivisi', fn($j) => $j->where('status', $v)))
            ->when($filters['status'], function ($q, $v) {
                if ($v === 'Belum dikerjakan') {
                    return $q->doesntHave('statusJobOps');
                }
                return $q->whereHas('statusJobOps', fn($s) => $s->where('status', $v));
            });
    }
}
