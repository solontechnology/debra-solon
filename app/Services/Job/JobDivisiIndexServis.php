<?php

namespace App\Services\Job;

use App\Models\Bank;
use App\Models\JobDivisi;
use App\Models\MasterDataFormOrder;
use App\Models\User;
use App\Services\Akta\WorkflowAktaService;
use App\Services\Job\JobDivisiProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobDivisiIndexServis
{
    public function __construct(
        private WorkflowAktaService $workflowAktaService,
        private JobDivisiProgressService $progressService
    ) {}

    public function execute(Request $request, array $options = []): array
    {
        $completedOnly = (bool) ($options["completed_only"] ?? false);
        // $kode = $request->kode ?? $request->q;
        // $status = $completedOnly ? null : $request->status;
        // $tanggalAkad = $request->tanggal_akad;
        // $namaPenghadap = $request->nama_penghadap;
        // $user = Cache::remember('master_users', 86400, fn() => User::orderBy("name", "asc")->get());

        // $countFilter = collect([
        //     'kode' => $kode,
        //     'status' => $status,
        //     'tanggal_akad' => $tanggalAkad,
        //     'nama_penghadap' => $namaPenghadap,
        // ])
        //     ->filter(fn($value) => filled($value))
        //     ->count();
        $sessionKey = 'job_divisi_filter_' . ($completedOnly ? 'selesai' : 'aktif');

        // if ($request->boolean('reset_filter')) {
        //     // User klik Reset Filter -> hapus filter tersimpan
        //     session()->forget($sessionKey);
        // } elseif ($request->has('apply_filter')) {
        //     // User klik Terapkan Filter -> simpen pilihan terbaru
        //     session()->put($sessionKey, [
        //         'kode'           => $request->input('kode', $request->input('q')),
        //         'status'         => $request->input('status'),
        //         'tanggal_akad'   => $request->input('tanggal_akad'),
        //         'nama_penghadap' => $request->input('nama_penghadap'),
        //     ]);
        // }

        // // Selalu baca dari session, jadi refresh / pindah halaman tetep sama
        // $saved = session($sessionKey, []);

        // $kode          = $saved['kode'] ?? null;
        // $status        = $completedOnly ? null : ($saved['status'] ?? null);
        // $tanggalAkad   = $saved['tanggal_akad'] ?? null;
        // $namaPenghadap = $saved['nama_penghadap'] ?? null;
        $dateFields = ['tanggal_akad', 'est_internal', 'est_eksternal'];

        $filterKeys = [
            'kode',
            'status',
            'nama_penghadap',
            'bank',
            'objek',
            'jenis_akad',
            'penanggung_jawab',
            'dibuat_oleh',
            'tanggal_akad_mode',
            'tanggal_akad_dari',
            'tanggal_akad_sampai',
            'est_internal_mode',
            'est_internal_dari',
            'est_internal_sampai',
            'est_eksternal_mode',
            'est_eksternal_dari',
            'est_eksternal_sampai',
        ];
        if ($request->boolean('reset_filter')) {
            session()->forget($sessionKey);
        } elseif ($request->has('apply_filter')) {
            $input = $request->only($filterKeys);
            $input['kode'] = $request->input('kode', $request->input('q'));
            session()->put($sessionKey, $input);
        }

        $saved   = session($sessionKey, []);
        $filters = collect($filterKeys)->mapWithKeys(fn($k) => [$k => $saved[$k] ?? null])->all();
        if ($completedOnly) {
            $filters['status'] = null;
        }

        $toRange = function (string $p) use ($filters): ?array {
            $mode   = $filters["{$p}_mode"] ?: 'hari';
            $dari   = $filters["{$p}_dari"];
            $sampai = $filters["{$p}_sampai"];

            if (!filled($dari) && !filled($sampai)) {
                return null;
            }
            $dari   = $dari ?: $sampai;   // salah satu kosong = periode tunggal
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
        };

        $rangeAkad      = $toRange('tanggal_akad');
        $rangeInternal  = $toRange('est_internal');
        $rangeEksternal = $toRange('est_eksternal');

        $countFilter = collect($filters)
            ->only(['kode', 'status', 'nama_penghadap', 'bank', 'objek', 'jenis_akad', 'penanggung_jawab', 'dibuat_oleh'])
            ->filter(fn($v) => filled($v))->count()
            + collect([$rangeAkad, $rangeInternal, $rangeEksternal])->filter()->count();

        $kode          = $filters['kode'];
        $status        = $filters['status'];
        // $tanggalAkad   = $filters['tanggal_akad'];
        $namaPenghadap = $filters['nama_penghadap'];
        $bank          = $filters['bank'];
        $objek         = $filters['objek'];
        $jenisAkad     = $filters['jenis_akad'];
        // $estInternal   = $filters['est_internal'];
        // $estEksternal  = $filters['est_eksternal'];
        $penanggungJawab = $filters['penanggung_jawab'];
        $dibuatOleh    = $filters['dibuat_oleh'];

        $user = Cache::remember('master_users', 86400, fn() => User::orderBy("name", "asc")->get());
        $banks = Cache::remember(
            'master_banks',
            86400,
            fn() => Bank::where('is_active', 1)->orderBy('nama')->pluck('nama')
        );
        $jenisAkadOptions = Cache::remember(
            'master_jenis_akad',
            86400,
            fn() =>
            MasterDataFormOrder::orderBy('nama')->get(['id', 'nama'])
        );

        // $countFilter = collect([
        //     'kode' => $kode,
        //     'status' => $status,
        //     'tanggal_akad' => $tanggalAkad,
        //     'nama_penghadap' => $namaPenghadap,
        // ])
        //     ->filter(fn($value) => filled($value))
        //     ->count();


        $items = JobDivisi::with([
            "bank",
            "listBank",
            "objek",
            "divisiYangDituju",
            "jenisAkad",
            "debitur",
            "listPembeli",
            "listPenjual",
            "pembatalan.user",
            "pembatalan.user2",
            "formOrder.nomorPpat",
            "formOrder.statusJobOps" => fn($query) => $query->orderBy('id'),
            "finance",
        ])
            ->orderBy("is_pending", "desc")
            ->orderBy("id", "desc")
            ->when($kode, function ($query) use ($kode) {
                return $query->where("kode", "like", "%$kode%");
            })
            ->when($status, function ($query) use ($status) {
                if ($status === "Pending") {
                    return $query->where("is_pending", 1);
                }

                return $query->where("status", $status)
                    ->where("is_pending", 0);
            })
            ->when($rangeAkad, fn($q, $r) => $q->where(function ($q) use ($r) {
                $q->whereBetween(DB::raw('DATE(tanggal_akad)'), $r)
                    ->orWhere(fn($q) => $q->whereNull('tanggal_akad')
                        ->whereBetween(DB::raw('DATE(tanggal_rencana_akad)'), $r));
            }))
            ->when($namaPenghadap, function ($query) use ($namaPenghadap) {
                return $query->where(function ($query) use ($namaPenghadap) {
                    $query->whereHas("debitur", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"))
                        ->orWhereHas("listPembeli", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"))
                        ->orWhereHas("listPenjual", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"));
                });
            })
            ->when($filters['bank'], fn($q, $v) => $q->whereHas('listBank', fn($b) => $b->where('nama_bank', 'like', "%$v%")))->when($filters['objek'], fn($q, $v) => $q->whereHas('objek', fn($o) => $o->where(function ($o) use ($v) {
                $o->where('nama_pemilik', 'like', "%$v%")
                    ->orWhere('no_sertifikat', 'like', "%$v%")
                    ->orWhere('alamat', 'like', "%$v%")
                    ->orWhere('jenis_sertifikat', 'like', "%$v%");
            })))->when($filters['jenis_akad'], fn($q, $v) => $q->where('jenis_akad', $v))
            ->when($rangeInternal, fn($q, $r) => $q->whereBetween(DB::raw('DATE(tanggal_estimasi_selesai)'), $r))
            ->when($rangeEksternal, fn($q, $r) => $q->whereBetween(DB::raw('DATE(tanggal_estimasi_selesai_eksternal)'), $r))
            ->when($filters['penanggung_jawab'], fn($q, $v) => $q->where('user_ops', $v))
            ->when($filters['dibuat_oleh'], fn($q, $v) => $q->where('created_by', $v))
            ->when($completedOnly, function ($query) {
                return $query->where("status", "Selesai")
                    ->where("is_pending", 0);
            }, function ($query) {
                return $query->where("status", "!=", "Selesai");
            })
            // ->paginate(12)
            // ->withQueryString();
            ->paginate(12)
            ->withQueryString();

        $categories = $items->getCollection()
            ->flatMap(fn($item) => $item->formOrder->pluck('kategori'))
            ->filter()
            ->unique();
        $workflows = $categories->mapWithKeys(fn($category) => [
            $category => $this->workflowAktaService->forCategory($category),
        ])->all();

        $items->getCollection()->transform(function ($item) use ($workflows) {
            $item->progress = $this->progressService->summarize($item->formOrder, $workflows);
            $item->progressByCategory = $item->formOrder
                ->groupBy('kategori')
                ->map(function ($formOrders, $category) use ($workflows) {
                    $categoryLabels = [
                        'notaris' => 'Notaris',
                        'ppat' => 'PPAT',
                        'legalisasi' => 'Legalisasi',
                        'waarmerking' => 'Waarmerking',
                        'surat-keluar' => 'Surat Keluar',
                        'wasiat' => 'Wasiat',
                        'covernot' => 'Cover Note',
                        'pajak' => 'Pajak',
                        'pnbp_voucher' => 'PNBP/Voucher',
                        'operasional' => 'Operasional',
                    ];

                    return [
                        'key' => $category,
                        'label' => $categoryLabels[$category] ?? str($category)->replace(['-', '_'], ' ')->headline()->toString(),
                        'progress' => $this->progressService->summarize($formOrders, $workflows),
                        'form_orders' => $formOrders,

                    ];
                })
                ->values();
            $tanggalEstimasiInternal = $item->tanggal_estimasi_selesai;
            $tanggalEstimasiEksternal = $item->tanggal_estimasi_selesai_eksternal;

            $tanggalExpiredTerbesar = $item->formOrder
                ->map(fn($formOrder) => $formOrder->nomorPpat?->tanggal_expired)
                ->filter()
                ->max();

            // Kalau ada tanggal expired, selalu gunakan tanggal expired.
            // Kalau tidak ada, gunakan tanggal estimasi internal yang tersimpan.
            if ($tanggalExpiredTerbesar) {
                $tanggalEstimasiBaru = \Carbon\Carbon::parse($tanggalExpiredTerbesar);

                // Hitung selisih internal -> eksternal dari tanggal lama
                $selisihHari = 0;

                if ($tanggalEstimasiInternal && $tanggalEstimasiEksternal) {
                    $selisihHari = \Carbon\Carbon::parse($tanggalEstimasiInternal)
                        ->diffInDays(
                            \Carbon\Carbon::parse($tanggalEstimasiEksternal),
                            false
                        );
                }

                // Geser eksternal dengan selisih yang sama
                $tanggalEstimasiEksternalBaru = $tanggalEstimasiBaru
                    ->copy()
                    ->addDays($selisihHari);

                $item->tanggal_estimasi_selesai = $tanggalEstimasiBaru->toDateString();
                $item->tanggal_estimasi_selesai_eksternal = $tanggalEstimasiEksternalBaru->toDateString();
            } else {
                $item->tanggal_estimasi_selesai = $tanggalEstimasiInternal;
                $item->tanggal_estimasi_selesai_eksternal = $tanggalEstimasiEksternal;
            }

            return $item;
        });

        return [
            "items" => $items,
            "filter_active" => $countFilter > 0,
            "masterPekerjaan" => MasterDataFormOrder::query()->get(),
            "countFilter" => $countFilter,
            "statusOptions" => $completedOnly ? ["Selesai"] : ["Pra Akad", "Akad", "Pending", "Batal Akad", "Selesai"],
            "user" => $user,
            "banks" => $banks,
            "jenisAkadOptions" => $jenisAkadOptions,
            // "filters" => [
            //     'kode'           => $kode,
            //     'status'         => $status,
            //     // 'tanggal_akad'   => $tanggalAkad,
            //     'nama_penghadap' => $namaPenghadap,
            //     'bank'           => $bank,
            //     'objek'          => $objek,
            //     'jenis_akad'     => $jenisAkad,
            //     // 'est_internal'   => $estInternal,
            //     // 'est_eksternal'  => $estEksternal,
            //     'penanggung_jawab' => $penanggungJawab,
            //     'dibuat_oleh'    => $dibuatOleh,
            // ],
            "filters" => $filters,
        ];
    }
}
