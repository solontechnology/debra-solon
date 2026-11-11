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

    // Ekspresi SQL per kolom sort. Semua string statis (bukan input user),
    // input user cuma dipakai buat milih key lewat whitelist.
    private const EXPIRED_TERBESAR = '(select max(np.tanggal_expired)
    from nomor_ppats np
    join job_divisi_form_orders fo on fo.id = np.job_divisi_form_order_id
    where fo.job_divisi_id = job_divisis.id)';

    private const SORT_COLUMNS = [
        'kode' => [
            'type' => 'text',
            'expr' => 'job_divisis.kode',
        ],
        // sama kayak yang tampil di view: is_pending ? 'Pending' : status
        'status' => [
            'type' => 'text',
            'expr' => "CASE WHEN job_divisis.is_pending = 1 THEN 'Pending' ELSE job_divisis.status END",
        ],
        // urutan prioritas ngikutin tampilan view: Debitur, Pembeli, Penjual
        'nama_penghadap' => [
            'type' => 'text',
            'expr' => "COALESCE(
        NULLIF((select min(d.nama) from debiturs d where d.job_divisi_id = job_divisis.id), ''),
        NULLIF((select min(p.nama) from pembelis p where p.job_divisi_id = job_divisis.id), ''),
        NULLIF((select min(j.nama) from penjuals j where j.job_divisi_id = job_divisis.id), '')
    )",
        ],
        'bank' => [
            'type' => 'text',
            'expr' => '(select min(b.nama_bank) from job_banks b where b.job_divisi_id = job_divisis.id)',
        ],
        'objek' => [
            'type' => 'text',
            'expr' => '(select min(o.no_sertifikat) from job_divisi_objeks o where o.job_divisi_id = job_divisis.id)',
        ],
        'jenis_akad' => [
            'type' => 'text',
            'expr' => '(select m.nama from master_data_form_orders m where m.id = job_divisis.jenis_akad limit 1)',
        ],
        // view nampilin tanggal_rencana_akad, jadi sort-nya ikut itu
        'tanggal_akad' => [
            'type' => 'date',
            'expr' => 'DATE(job_divisis.tanggal_rencana_akad)',
        ],
        // niru logika PHP: pakai expired terbesar kalau ada, kalau gak ada pakai kolom asli
        'est_internal' => [
            'type' => 'date',
            'expr' => 'DATE(COALESCE(' . self::EXPIRED_TERBESAR . ', job_divisis.tanggal_estimasi_selesai))',
        ],
        // niru logika PHP: expired terbesar + selisih (eksternal - internal) dari tanggal lama
        'est_eksternal' => [
            'type' => 'date',
            'expr' => 'DATE(COALESCE(
            DATE_ADD(' . self::EXPIRED_TERBESAR . ',
                INTERVAL COALESCE(DATEDIFF(job_divisis.tanggal_estimasi_selesai_eksternal, job_divisis.tanggal_estimasi_selesai), 0) DAY),
            job_divisis.tanggal_estimasi_selesai_eksternal
        ))',
        ],
        'penanggung_jawab' => [
            'type' => 'text',
            'expr' => '(select u.username from users u where u.id = job_divisis.user_ops limit 1)',
        ],
        'dibuat_oleh' => [
            'type' => 'text',
            'expr' => '(select u.username from users u where u.id = job_divisis.created_by limit 1)',
        ],
        // view arsip nampilin: tanggal_akad kalau ada, kalau gak ada tanggal_rencana_akad
'tanggal_akad_arsip' => [
    'type' => 'date',
    'expr' => 'DATE(COALESCE(job_divisis.tanggal_akad, job_divisis.tanggal_rencana_akad))',
],
// niru view: total harga_proses semua form order - kas masuk yang disetujui
'piutang' => [
    'type' => 'number',
    'expr' => "(COALESCE((select sum(fo.harga_proses) from job_divisi_form_orders fo
            where fo.job_divisi_id = job_divisis.id), 0)
        - COALESCE((select sum(f.total) from job_divisi_finances f
            where f.job_divisi_id = job_divisis.id
            and f.tipe = 'in'
            and LOWER(f.status) in ('disetujui', 'approved')), 0))",
],
    ];
    public function __construct(
        private WorkflowAktaService $workflowAktaService,
        private JobDivisiProgressService $progressService
    ) {}

    public function execute(Request $request, array $options = []): array
    {
        $completedOnly = (bool) ($options["completed_only"] ?? false);
        $filters     = JobDivisiFilter::resolve($request, $completedOnly);
        $countFilter = JobDivisiFilter::count($filters);
        // whitelist: input dari URL gak boleh langsung masuk query
        $sortInput = $request->query('sort');
        $sort = is_string($sortInput) && isset(self::SORT_COLUMNS[$sortInput]) ? $sortInput : null;
        $direction = in_array($request->query('direction'), ['asc', 'desc', 'empty'], true)
            ? $request->query('direction')
            : 'asc';

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
        $query = JobDivisiFilter::apply(
            JobDivisi::with([
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
                ->orderBy("id", "desc"),
            $filters,
            $completedOnly
        );
        $items = $this->applySort($query, $sort, $direction)
            ->paginate(12)
            ->withQueryString()
            ->appends(['apply_filter' => null, 'reset_filter' => null]);

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
                        // "sort" => $sort,
                        // "direction" => $direction,

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
            "filters" => $filters,
            "sort" => $sort,
            "direction" => $direction,
        ];
    }
    private function applySort($query, ?string $sort, string $direction)
    {
        $col = self::SORT_COLUMNS[$sort] ?? null;

        // gak ada sort aktif: urutan default lu (is_pending desc, id desc) tetap jalan
        if (!$col) {
            return $query;
        }

        $query->reorder();

        $expr = $col['expr'];

        // date cukup cek NULL, text juga dianggap kosong kalau string kosong/spasi
        $isEmpty = $col['type'] === 'date'
            ? "($expr) IS NULL"
            : "NULLIF(TRIM($expr), '') IS NULL";

        if ($direction === 'empty') {
            // kosong di atas, sisanya A-Z
            $query->orderByRaw("$isEmpty DESC")->orderByRaw("($expr) ASC");
        } else {
            // kosong selalu di bawah, sisanya A-Z / Z-A
            $query->orderByRaw("$isEmpty ASC")
                ->orderByRaw("($expr) " . ($direction === 'desc' ? 'DESC' : 'ASC'));
        }

        // tie-breaker biar urutan antar halaman stabil
        return $query->orderByDesc('job_divisis.id');
    }
}
