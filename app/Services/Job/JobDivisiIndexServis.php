<?php

namespace App\Services\Job;

use App\Models\JobDivisi;
use App\Models\MasterDataFormOrder;
use App\Services\Akta\WorkflowAktaService;
use Illuminate\Http\Request;

class JobDivisiIndexServis
{
    public function __construct(
        private WorkflowAktaService $workflowAktaService,
        private JobDivisiProgressService $progressService
    ) {}

    public function execute(Request $request, array $options = []): array
    {
        $completedOnly = (bool) ($options["completed_only"] ?? false);
        $kode = $request->kode ?? $request->q;
        $status = $completedOnly ? null : $request->status;
        $tanggalAkad = $request->tanggal_akad;
        $namaPenghadap = $request->nama_penghadap;

        $countFilter = collect([
            'kode' => $kode,
            'status' => $status,
            'tanggal_akad' => $tanggalAkad,
            'nama_penghadap' => $namaPenghadap,
        ])
            ->filter(fn($value) => filled($value))
            ->count();

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
            "formOrder.statusJobOps" => fn ($query) => $query->orderBy('id'),
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
            ->when($tanggalAkad, function ($query) use ($tanggalAkad) {
                return $query->where(function ($query) use ($tanggalAkad) {
                    $query->whereDate("tanggal_akad", $tanggalAkad)
                        ->orWhere(function ($query) use ($tanggalAkad) {
                            $query->whereNull("tanggal_akad")
                                ->whereDate("tanggal_rencana_akad", $tanggalAkad);
                        });
                });
            })
            ->when($namaPenghadap, function ($query) use ($namaPenghadap) {
                return $query->where(function ($query) use ($namaPenghadap) {
                    $query->whereHas("debitur", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"))
                        ->orWhereHas("listPembeli", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"))
                        ->orWhereHas("listPenjual", fn($q) => $q->where("nama", "LIKE", "%$namaPenghadap%"));
                });
            })
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
            ->flatMap(fn ($item) => $item->formOrder->pluck('kategori'))
            ->filter()
            ->unique();
        $workflows = $categories->mapWithKeys(fn ($category) => [
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
        ];
    }
}
