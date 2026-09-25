<?php

namespace App\Http\Controllers\Export;

use App\Exports\ExportAktaCovernot;
use App\Exports\ExportJobOps;
use App\Http\Controllers\Controller;
use App\Services\Akta\AktaService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportAktaController extends Controller
{
    public function __construct(
        protected AktaService $aktaService
    ) {}

    public function export(Request $request, $tipe) 
    {
        // 1. Get the items using your existing AktaService function
        $rawItems = $this->aktaService->getItemsForExport($request, $tipe);

        $client = config('app.notaris', 'default');
        $workflow = config("workflow.akta.$client.$tipe")
            ?? config("workflow.akta.default.$tipe")
            ?? [];

        $items = collect();

        foreach ($rawItems as $item) {

            $status = $item->statusJobOps->last()->status ?? 'Belum dikerjakan';
            $nextStep = $this->getNextStep($workflow, $status);
            $item->nextStep = $nextStep['name'] ?? $status;

            $nomorPpat = $item->nomorPpat;
            $item->activeCovernote = $nomorPpat instanceof \Illuminate\Support\Collection 
                ? $nomorPpat->first() 
                : $nomorPpat;

            $items->push($item);
        }

        $fileName = 'job-' . strtolower($tipe) . '.xlsx';

        return Excel::download(new ExportAktaCovernot($items), $fileName);
    }

    private function getNextStep(array $workflow, string $currentStatus): ?array
    {
        if ($currentStatus === 'Belum diproses') {
            return $workflow[0] ?? null;
        }

        $currentIndex = collect($workflow)->search(function ($step) use ($currentStatus) {
            return ($step['name'] ?? null) === $currentStatus;
        });

        if ($currentIndex === false) {
            return $workflow[0] ?? null;
        }

        return $workflow[$currentIndex + 1] ?? null;
    }
}