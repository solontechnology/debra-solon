<?php

namespace App\Services\Job;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpsFilter
{
    public const KEYS = [
        'parent', 'status_akad', 'proses', 'nomor_objek', 'nama_penghadap',
        'bank', 'biaya_min', 'biaya_max', 'status_pengerjaan',
    ];

    public const BIAYA_COLUMN = 'harga_proses';

    private const SESSION_KEY = 'ops_filter';

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