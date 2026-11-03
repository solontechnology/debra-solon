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
