<?php

namespace App\View\Components\Dashboard;

use App\Models\JobDivisiFormOrder;
use App\Models\NomorPpat;
use App\Models\NotarisRekanan;
use App\Models\Pekerjaan;
use App\Models\PenomoranSetting;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class LimitViewDashboard extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $satuMingguLagi = Carbon::today()->addWeek();

        $nomorPpats = NomorPpat::query()
            ->with([
                'formOrder.objek',
                'formOrder.jobDivisi.objek',
                'formOrder.jobDivisi.debitur',
                'formOrder.jobDivisi.listPembeli',
                'formOrder.jobDivisi.listPenjual',
            ])
            ->whereNotNull('tanggal')
            ->whereNotNull('tanggal_expired')
            ->whereDate('tanggal_expired', '<=', $satuMingguLagi)
            ->orderBy('tanggal_expired')
            ->get();

        $formOrdersBelumNomor = JobDivisiFormOrder::query()
            ->with([
                'nomorPpat',
                'jobDivisi.objek',
                'jobDivisi.debitur',
                'jobDivisi.listPembeli',
                'jobDivisi.listPenjual',
            ])
            ->whereIn('kategori', array_keys(PenomoranSetting::kategori()))
            ->whereNotIn('status', ['rejected', 'Dibatalkan', 'pending'])
            ->whereDoesntHave('nomorPpat')
            ->whereHas('jobDivisi', fn ($query) => $query->where('status', '!=', 'Batal Akad'))
            ->orderByDesc('created_at')
            ->get();

        $formOrdersBelumNomor = $formOrdersBelumNomor
            ->filter(fn ($formOrder) => $nomorPpats->contains(function ($nomorPpat) use ($formOrder) {
                return $nomorPpat->kategori === $formOrder->kategori
                    && (int) $nomorPpat->formOrder?->job_divisi_id === (int) $formOrder->job_divisi_id
                    && Carbon::parse($formOrder->created_at)
                        ->gt(Carbon::parse($nomorPpat->tanggal_expired)->startOfDay());
            }))
            ->values();

        $nomorPpats = $nomorPpats
            ->reject(fn ($nomorPpat) => $formOrdersBelumNomor->contains(function ($formOrder) use ($nomorPpat) {
                return $nomorPpat->kategori === $formOrder->kategori
                    && (int) $nomorPpat->formOrder?->job_divisi_id === (int) $formOrder->job_divisi_id
                    && Carbon::parse($formOrder->created_at)
                        ->gt(Carbon::parse($nomorPpat->tanggal_expired)->startOfDay());
            }))
            ->values();

        $covernotBelumNomor = $formOrdersBelumNomor->where('kategori', 'covernot')->values();
        $prosesBelumNomor = $formOrdersBelumNomor->where('kategori', '!=', 'covernot')->values();

        $ppatRows = $nomorPpats->where('kategori', '!=', 'covernot')
            ->map(fn ($item) => ['type' => 'nomor', 'item' => $item])
            ->concat($prosesBelumNomor->map(fn ($item) => ['type' => 'proses', 'item' => $item]))
            ->values();
        $covernotRows = $nomorPpats->where('kategori', 'covernot')
            ->map(fn ($item) => ['type' => 'nomor', 'item' => $item])
            ->concat($covernotBelumNomor->map(fn ($item) => ['type' => 'proses', 'item' => $item]))
            ->values();

        $paginateRows = function (Collection $rows, string $pageName): LengthAwarePaginator {
            $perPage = 12;
            $page = LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => $pageName,
                ]
            );
        };
        $masterPekerjaan = Cache::remember('master_pekerjaan', 86400, fn() => Pekerjaan::orderBy("nama", "asc")->get());

        $ppatItems = $paginateRows($ppatRows, 'ppat_page');
        $covernotItems = $paginateRows($covernotRows, 'covernot_page');

        $notarisRekananOptions = NotarisRekanan::query()
            ->with('kota')
            ->orderBy('nama')
            ->get();

        return view(
            'components.dashboard.limit-view-dashboard',
            compact('ppatItems', 'covernotItems', 'notarisRekananOptions', 'masterPekerjaan')
        );
    }
}
