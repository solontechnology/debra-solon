<?php

namespace App\Services\Job;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JobDivisiFilter
{
    public const KEYS = [
        'kode', 'status', 'nama_penghadap', 'bank', 'objek', 'jenis_akad',
        'penanggung_jawab', 'dibuat_oleh',
        'tanggal_akad_mode', 'tanggal_akad_dari', 'tanggal_akad_sampai',
        'est_internal_mode', 'est_internal_dari', 'est_internal_sampai',
        'est_eksternal_mode', 'est_eksternal_dari', 'est_eksternal_sampai',
    ];

    private const PLAIN = [
        'kode', 'status', 'nama_penghadap', 'bank', 'objek', 'jenis_akad',
        'penanggung_jawab', 'dibuat_oleh',
    ];

    public static function sessionKey(bool $completedOnly): string
    {
        return 'job_divisi_filter_' . ($completedOnly ? 'selesai' : 'aktif');
    }

    /** $persist = false dipakai export: baca session saja, tidak menyimpan/menghapus. */
    public static function resolve(Request $request, bool $completedOnly, bool $persist = true): array
    {
        $key = self::sessionKey($completedOnly);

        if ($persist) {
            if ($request->boolean('reset_filter')) {
                session()->forget($key);
            } elseif ($request->has('apply_filter')) {
                $input = $request->only(self::KEYS);
                $input['kode'] = $request->input('kode', $request->input('q'));
                session()->put($key, $input);
            }
        }

        $saved   = session($key, []);
        $filters = collect(self::KEYS)->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])->all();

        if ($completedOnly) {
            $filters['status'] = null;
        }

        return $filters;
    }

    public static function range(array $filters, string $prefix): ?array
    {
        $mode   = $filters["{$prefix}_mode"] ?: 'hari';
        $dari   = $filters["{$prefix}_dari"];
        $sampai = $filters["{$prefix}_sampai"];

        if (!filled($dari) && !filled($sampai)) {
            return null;
        }
        $dari   = $dari ?: $sampai;
        $sampai = $sampai ?: $dari;

        try {
            [$start, $end] = match ($mode) {
                'bulan' => [Carbon::parse($dari . '-01')->startOfMonth(), Carbon::parse($sampai . '-01')->endOfMonth()],
                'tahun' => [Carbon::create((int) $dari, 1, 1)->startOfYear(), Carbon::create((int) $sampai, 1, 1)->endOfYear()],
                default => [Carbon::parse($dari)->startOfDay(), Carbon::parse($sampai)->endOfDay()],
            };
        } catch (\Throwable $e) {
            return null;
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    public static function count(array $filters): int
    {
        return collect($filters)->only(self::PLAIN)->filter(fn($v) => filled($v))->count()
            + collect(['tanggal_akad', 'est_internal', 'est_eksternal'])
                ->filter(fn($p) => self::range($filters, $p) !== null)->count();
    }

    public static function apply($query, array $filters, bool $completedOnly)
    {
        $rangeAkad      = self::range($filters, 'tanggal_akad');
        $rangeInternal  = self::range($filters, 'est_internal');
        $rangeEksternal = self::range($filters, 'est_eksternal');

        return $query
            ->when($filters['kode'], fn($q, $v) => $q->where('kode', 'like', "%$v%"))
            ->when($filters['status'], function ($q, $v) {
                if ($v === 'Pending') {
                    return $q->where('is_pending', 1);
                }
                return $q->where('status', $v)->where('is_pending', 0);
            })
            ->when($rangeAkad, fn($q, $r) => $q->where(function ($q) use ($r) {
                $q->whereBetween(DB::raw('DATE(tanggal_akad)'), $r)
                    ->orWhere(fn($q) => $q->whereNull('tanggal_akad')
                        ->whereBetween(DB::raw('DATE(tanggal_rencana_akad)'), $r));
            }))
            ->when($filters['nama_penghadap'], fn($q, $v) => $q->where(function ($q) use ($v) {
                $q->whereHas('debitur', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPembeli', fn($d) => $d->where('nama', 'LIKE', "%$v%"))
                    ->orWhereHas('listPenjual', fn($d) => $d->where('nama', 'LIKE', "%$v%"));
            }))
            ->when($filters['bank'], fn($q, $v) => $q->whereHas('listBank', fn($b) => $b->where('nama_bank', $v)))
            ->when($filters['objek'], fn($q, $v) => $q->whereHas('objek', fn($o) => $o->where(function ($o) use ($v) {
                $o->where('nama_pemilik', 'like', "%$v%")
                    ->orWhere('no_sertifikat', 'like', "%$v%")
                    ->orWhere('alamat', 'like', "%$v%")
                    ->orWhere('jenis_sertifikat', 'like', "%$v%");
            })))
            ->when($filters['jenis_akad'], fn($q, $v) => $q->where('jenis_akad', $v))
            ->when($rangeInternal, fn($q, $r) => $q->whereBetween(DB::raw('DATE(tanggal_estimasi_selesai)'), $r))
            ->when($rangeEksternal, fn($q, $r) => $q->whereBetween(DB::raw('DATE(tanggal_estimasi_selesai_eksternal)'), $r))
            ->when($filters['penanggung_jawab'], fn($q, $v) => $q->where('user_ops', $v))
            ->when($filters['dibuat_oleh'], fn($q, $v) => $q->where('created_by', $v))
            ->when(
                $completedOnly,
                fn($q) => $q->where('status', 'Selesai')->where('is_pending', 0),
                fn($q) => $q->where('status', '!=', 'Selesai')
            );
    }
}