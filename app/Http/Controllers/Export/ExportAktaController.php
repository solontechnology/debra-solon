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
        $rawItems = $this->aktaService->getItemsForExport($request, $tipe);

        $client = config('app.notaris', 'default');
        $workflow = config("workflow.akta.$client.$tipe")
            ?? config("workflow.akta.default.$tipe")
            ?? [];

        $items = collect();

        foreach ($rawItems as $item) {
            // Calculate workflow status for the item
            $status = $item->statusJobOps->last()->status ?? 'Belum dikerjakan';
            $nextStep = $this->getNextStep($workflow, $status);
            $item->nextStep = $nextStep['name'] ?? $status;

            // Since nomorPpat is defined as hasOne, it is a single model object (or null)
            $item->activeCovernote = $item->nomorPpat;

            $items->push($item);
        }

        $fileName = 'job-' . strtolower($tipe) . '.xlsx';

        if (strtolower($tipe) === 'covernot') {
            return Excel::download(new ExportAktaCovernot($items), $fileName);
        }

        return Excel::download(new ExportJobOps($items), $fileName);
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