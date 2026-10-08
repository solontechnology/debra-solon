<?php

namespace App\View\Components\Job\Akta;

use Closure;
use App\Models\JobAktaWorkflowStage;
use App\Services\Akta\WorkflowAktaService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EditAkta extends Component
{
    public function __construct(
        public $formOrder,
        public $jobDivisi,
        public $statusJobOps,
        public $tipe,
        public $users,
        public $key
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        $tipe = $this->tipe ?? 'notaris';
        $workflowService = app(WorkflowAktaService::class);
        $workflow = $workflowService->forCategory($tipe);
        $lastStatus = $this->statusJobOps->last();
        $stepState = $workflowService->currentStep($workflow, $lastStatus);
        $nextStep = $stepState['next_step'];
        $approvalPending = $stepState['pending_approval'];
        $currentStatus = $stepState['current_status'];
        $nextStatus = $nextStep['name'] ?? 'Selesai';
        $isApproval = $nextStep['approval']['enabled'] ?? false;
        $isPenugasan = $nextStep['penugasan'] ?? false;
        $activeStage = $stepState['active_stage'];
        $stageModel = $activeStage?->workflowStage;
        if (!$stageModel && !empty($nextStep['id'])) {
            $stageModel = JobAktaWorkflowStage::with([
                'users:id',
                'roles:id',
                'assignerUsers:id',
                'assignerRoles:id',
            ])->find($nextStep['id']);
        }
        $steps = array_column($workflow, 'name');
        $canDecideApproval = $approvalPending
            && $approvalPending->workflowStage
            && $workflowService->canApprove($approvalPending->workflowStage, auth()->id());
        $isAssignedWork = $activeStage !== null;
        $canAssignStage = $isPenugasan
            && !$isAssignedWork
            && $stageModel
            && $workflowService->canAssign($stageModel, auth()->id());
        $canSubmitStage = $workflowService->canSubmitStage($stepState, (int) auth()->id());
        $workflowAction = $isPenugasan && !$isAssignedWork ? 'assign' : 'submit';

        return view('components.job.akta.edit-akta', [
            'workflow' => $workflow,
            'steps' => $steps,
            'currentStatus' => $currentStatus,
            'nextStatus' => $nextStatus,
            'nextStep' => $nextStep,
            'isApproval' => $isApproval,
            "users" => $this->users,
            "isPenugasan" => $isPenugasan,
            "activeStage" => $activeStage,
            "isAssignedWork" => $isAssignedWork,
            "canAssignStage" => $canAssignStage,
            "canSubmitStage" => $canSubmitStage,
            "workflowAction" => $workflowAction,
            "approvalPending" => $approvalPending,
            "canDecideApproval" => $canDecideApproval,
        ]);
    }
}
