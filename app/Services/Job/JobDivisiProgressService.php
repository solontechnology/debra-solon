<?php

namespace App\Services\Job;

use Illuminate\Support\Collection;

class JobDivisiProgressService
{
    public function summarize(Collection $formOrders, array $workflows): array
    {
        $total = $formOrders->count();
        $completed = $formOrders->filter(function ($formOrder) use ($workflows): bool {
            $workflow = $workflows[$formOrder->kategori] ?? [];
            $finalStep = $workflow === [] ? null : $workflow[array_key_last($workflow)];
            $latestStatus = $formOrder->statusJobOps->last();

            if ($finalStep === null || $latestStatus === null) {
                return false;
            }

            $isFinalStage = ($finalStep['id'] ?? null) !== null
                ? (int) $latestStatus->workflow_stage_id === (int) $finalStep['id']
                : $latestStatus->status === ($finalStep['name'] ?? null);

            return $isFinalStage
                && in_array($latestStatus->work_status, [null, 'completed'], true)
                && ! in_array($latestStatus->approval_status, ['pending', 'rejected'], true);
        })->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
        ];
    }
}
