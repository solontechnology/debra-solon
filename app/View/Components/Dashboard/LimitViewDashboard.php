<?php

namespace App\View\Components\Dashboard;

use App\Models\NomorPpat;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
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
        $hariIni = Carbon::today();
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
            ->whereDate('tanggal_expired', '>=', $hariIni)
            ->whereDate('tanggal_expired', '<=', $satuMingguLagi)
            ->orderBy('tanggal_expired')
            ->get();

        return view(
            'components.dashboard.limit-view-dashboard',
            compact('nomorPpats')
        );
    }
}
