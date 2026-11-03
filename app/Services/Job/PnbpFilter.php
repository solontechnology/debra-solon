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