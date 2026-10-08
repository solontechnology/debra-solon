<?php

namespace App\Services\Akta;

use App\Models\JobAktaWorkflowStage;
use App\Models\StatusJobOps;
use App\Services\Tenancy\TenantFeatureService;

class WorkflowAktaService
{
    public function __construct(private ?TenantFeatureService $tenantFeatures = null) {}

    public function forCategory(string $category): array
    {
        $stages = $this->tenantFeatures?->isEnabled('configurable_akta_workflow') === false
            ? collect()
            : JobAktaWorkflowStage::query()
                ->where('kategori', $category)
                ->with(['users:id', 'roles:id', 'assignerUsers:id', 'assignerRoles:id'])
                ->orderBy('position')
                ->get();

        if ($stages->isNotEmpty()) {
            return $stages->map->toWorkflowStep()->all();
        }

        $legacyCategory = in_array($category, ['notaris', 'ppat'], true)
            ? 'notaris'
            : $category;
        $client = config('app.notaris', 'default');
        $legacyWorkflow = config("workflow.akta.{$client}.{$legacyCategory}")
            ?? config("workflow.akta.default.{$legacyCategory}")
            ?? [];

        return array_map(function (array $stage) {
            return [
                'id' => null,
                'name' => $stage['name'],
                'penugasan' => (bool) ($stage['penugasan'] ?? false),
                'approval' => $stage['approval'] ?? ['enabled' => false],
                'assignment' => $stage['assignment'] ?? ['enabled' => false, 'user_ids' => [], 'role_ids' => []],
            ];
        }, $legacyWorkflow);
    }

    public function currentStep(array $workflow, ?StatusJobOps $lastStatus): array
    {
        if ($lastStatus?->approval_status === 'pending') {
            return [
                'current_status' => $lastStatus->status,
                'next_step' => null,
                'pending_approval' => $lastStatus,
                'active_stage' => null,
            ];
        }

        $currentIndex = $lastStatus?->workflow_stage_id
            ? collect($workflow)->search(
                fn ($step) => (int) ($step['id'] ?? 0) === (int) $lastStatus->workflow_stage_id
            )
            : false;
        if ($currentIndex === false && $lastStatus) {
            $currentIndex = collect($workflow)->search(fn ($step) => $step['name'] === $lastStatus->status);
        }

        if ($lastStatus
            && ($lastStatus->work_status === 'assigned' || $lastStatus->approval_status === 'rejected')
            && $currentIndex !== false) {
            return [
                'current_status' => $currentIndex === 0 ? 'Belum diproses' : $workflow[$currentIndex - 1]['name'],
                'next_step' => $workflow[$currentIndex],
                'pending_approval' => null,
                'active_stage' => $lastStatus,
            ];
        }

        $currentStatus = $lastStatus?->status ?? 'Belum diproses';
        $nextStep = $lastStatus === null || $currentIndex === false
            ? ($workflow[0] ?? null)
            : ($workflow[$currentIndex + 1] ?? null);

        return [
            'current_status' => $currentStatus,
            'next_step' => $nextStep,
            'pending_approval' => null,
            'active_stage' => null,
        ];
    }

    public function canApprove(JobAktaWorkflowStage $stage, int $userId): bool
    {
        return $stage->users->contains('id', $userId)
            || $stage->roles()->whereHas('users', fn ($query) => $query->whereKey($userId))->exists();
    }

    public function canAssign(JobAktaWorkflowStage $stage, int $userId): bool
    {
        if ($stage->assignerRoles->isNotEmpty() && $stage->assignerUsers->isEmpty()) {
            return $stage->assignerRoles()
                ->whereHas('users', fn ($query) => $query->whereKey($userId))
                ->exists();
        }

        return $stage->assignerUsers->contains('id', $userId);
    }

    public function canSubmitStage(array $state, int $userId): bool
    {
        $nextStep = $state['next_step'] ?? null;
        if (! $nextStep) {
            return false;
        }

        if (! ($nextStep['penugasan'] ?? false)) {
            return true;
        }

        $activeStage = $state['active_stage'] ?? null;

        return $activeStage !== null && (int) $activeStage->user_id === $userId;
    }

    public function isFinalStage(JobAktaWorkflowStage $stage): bool
    {
        return ! JobAktaWorkflowStage::query()
            ->where('kategori', $stage->kategori)
            ->where('position', '>', $stage->position)
            ->exists();
    }

    public function isFinalStep(array $workflow, array $step): bool
    {
        $lastStep = end($workflow);

        return $lastStep !== false
            && (($step['id'] ?? null) !== null
                ? ($lastStep['id'] ?? null) === $step['id']
                : ($lastStep['name'] ?? null) === ($step['name'] ?? null));
    }
}
