<?php

namespace App\Http\Controllers\Job\Akta;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Job\UpdateStatusJobDivisiController;
use App\Models\JobDivisiFormOrder;
use App\Models\JobAktaWorkflowStage;
use App\Models\NotarisRekanan;
use App\Models\PenomoranSetting;
use App\Models\StatusJobOps;
use App\Models\User;
use App\Services\Akta\AktaService;
use App\Services\Akta\WorkflowAktaService;
use App\Services\Notifikasi\NotifikasiServis;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataAktaController extends Controller
{

    public function __construct(
        protected AktaService $aktaService,
        protected NotifikasiServis $notifikasiServis,
        protected WorkflowAktaService $workflowAktaService
    ) {}

    /**
     * Tampilkan daftar data akta dengan kategori notaris, ppat, legalisasi.
     */
    public function index(Request $request, ?string $tipe = null)
    {
        $tipe = $tipe ?: 'notaris';

        $workflow = $this->workflowAktaService->forCategory($tipe);
        $data = $this->aktaService->getIndexData($request, $tipe);

        foreach ($data["items"] as $item) {
            $lastStatus = $item->statusJobOps->last();
            $state = $this->workflowAktaService->currentStep($workflow, $lastStatus);
            $item->nextStep = $state['pending_approval']
                ? 'Menunggu approval: ' . $lastStatus->status
                : ($state['active_stage']
                    ? 'Sedang dikerjakan: ' . $lastStatus->status
                    : (($state['next_step']['penugasan'] ?? false)
                        ? 'Menunggu penugasan: ' . $state['next_step']['name']
                        : ($state['next_step']['name'] ?? $state['current_status'])));
        }
        $prosesOptions = JobDivisiFormOrder::query()
            ->where('kategori', $tipe)
            ->distinct()
            ->pluck('nama');

        $statusOptions = StatusJobOps::query()
            ->distinct()
            ->pluck('status');

        $userOptions = User::orderBy('name')->get();

        $bankOptions = \App\Models\Bank::orderBy('nama')->get();
        $notarisRekananOptions = NotarisRekanan::query()
            ->with('kota')
            ->orderBy('nama')
            ->get();

        return view("pages.Job.Akta.index", $data, [
            'prosesOptions' => $prosesOptions,
            'statusOptions' => $statusOptions,
            'userOptions' => $userOptions,
            'bankOptions' => $bankOptions,
            'notarisRekananOptions' => $notarisRekananOptions,
        ]);
    }

    /**
     * Simpan penugasan atau penyelesaian data akta.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_id' => ['required', 'integer', 'exists:job_divisi_form_orders,id'],
            'kategori' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(PenomoranSetting::kategori()))],
            'tipe' => ['required', 'in:next_step'],
            'workflow_action' => ['required', 'in:assign,submit'],
            'staff' => ['required_if:workflow_action,assign', 'nullable', 'integer', 'exists:users,id'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $formOrder = JobDivisiFormOrder::with('jobDivisi')
            ->where('kategori', $validated['kategori'])
            ->findOrFail($validated['form_id']);
        $lastStatus = StatusJobOps::where('job_divisi_form_order_id', $formOrder->id)
            ->orderByDesc('id')
            ->first();
        $workflow = $this->workflowAktaService->forCategory($formOrder->kategori);
        $state = $this->workflowAktaService->currentStep($workflow, $lastStatus);

        if ($state['pending_approval']) {
            return back()->with(
                'info',
                "Stage {$state['pending_approval']->status} sudah diajukan dan sedang menunggu approval."
            );
        }

        $nextStep = $state['next_step'];
        if (!$nextStep) {
            return back()->with('error', 'Workflow pekerjaan ini sudah selesai atau belum memiliki stage berikutnya.');
        }

        $stage = !empty($nextStep['id'])
            ? JobAktaWorkflowStage::with(['users', 'roles.users', 'assignerUsers', 'assignerRoles.users'])
                ->findOrFail($nextStep['id'])
            : null;
        $isAssignment = (bool) ($nextStep['penugasan'] ?? false);
        $isActiveAssignment = $state['active_stage'] !== null;
        $action = $validated['workflow_action'];

        if ($isAssignment && !$isActiveAssignment && $action !== 'assign') {
            return back()->with('error', 'Stage ini harus ditugaskan sebelum petugas mulai mengerjakannya.');
        }
        if ((!$isAssignment || $isActiveAssignment) && $action === 'assign') {
            return back()->with('error', 'Stage ini tidak sedang menunggu penugasan.');
        }
        if ($action === 'assign' && (!$stage || !$this->workflowAktaService->canAssign($stage, Auth::id()))) {
            abort(403, 'Anda tidak memiliki kewenangan untuk menugaskan pekerjaan pada stage ini.');
        }
        if ($action === 'submit') {
            abort_unless(
                $this->workflowAktaService->canSubmitStage($state, (int) Auth::id()),
                403,
                'Anda tidak memiliki kewenangan untuk mengajukan hasil stage ini.'
            );
        }
        if ($action === 'assign'
            && $stage->assignerUsers->isEmpty()
            && $stage->assignerRoles->isEmpty()) {
            return back()->with('error', 'Stage penugasan belum memiliki user atau role yang berwenang assign.');
        }
        $requiresApproval = (bool) ($nextStep['approval']['enabled'] ?? false);
        if ($action === 'submit' && $requiresApproval && (!$stage || $this->approverUserIds($stage) === [])) {
            return back()->with('error', 'Stage approval belum memiliki user atau role approver.');
        }

        $lastStaff = StatusJobOps::where('job_divisi_form_order_id', $formOrder->id)
            ->whereNotNull('user_id')
            ->orderByDesc('id')
            ->first();
        $isFinalStep = $this->workflowAktaService->isFinalStep($workflow, $nextStep);

        DB::transaction(function () use (
            $validated,
            $formOrder,
            $lastStatus,
            $state,
            $nextStep,
            $stage,
            $isActiveAssignment,
            $action,
            $requiresApproval,
            $lastStaff,
            $isFinalStep
        ) {
            JobDivisiFormOrder::query()
                ->whereKey($formOrder->id)
                ->lockForUpdate()
                ->firstOrFail();
            $latestStatus = StatusJobOps::query()
                ->where('job_divisi_form_order_id', $formOrder->id)
                ->orderByDesc('id')
                ->first();
            abort_unless(
                $latestStatus?->id === $lastStatus?->id,
                409,
                'Status pekerjaan berubah. Muat ulang halaman sebelum melanjutkan.'
            );

            if ($action === 'assign') {
                StatusJobOps::create([
                    'created_by' => Auth::id(),
                    'job_divisi_form_order_id' => $formOrder->id,
                    'workflow_stage_id' => $nextStep['id'] ?? null,
                    'status' => $nextStep['name'],
                    'work_status' => 'assigned',
                    'user_id' => $validated['staff'],
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                $this->notifikasiServis->create(
                    (int) $validated['staff'],
                    'Penugasan Akta ' . strtoupper($formOrder->kategori),
                    "Anda ditugaskan mengerjakan stage {$nextStep['name']}.",
                    $this->categoryLink($formOrder->kategori),
                    $formOrder->jobDivisi?->id,
                    $formOrder->id
                );

                return;
            }

            $activeStatus = $isActiveAssignment ? $state['active_stage'] : null;
            if ($activeStatus) {
                $activeStatus->update(['work_status' => 'superseded']);
            }
            $status = new StatusJobOps();
            $status->fill([
                'created_by' => Auth::id(),
                'job_divisi_form_order_id' => $formOrder->id,
                'workflow_stage_id' => $nextStep['id'] ?? null,
                'status' => $nextStep['name'],
                'approval_status' => $requiresApproval ? 'pending' : null,
                'work_status' => $requiresApproval ? 'submitted' : 'completed',
                'user_id' => $activeStatus?->user_id ?? Auth::id(),
                'next_user' => $lastStaff?->next_user,
                'keterangan' => $validated['keterangan'] ?? null,
                'approved_by' => null,
                'approval_comment' => null,
                'status_penolakan' => null,
            ]);
            $status->save();

            if ($requiresApproval) {
                $this->notifyApprovers($stage, $formOrder);
            } elseif ($isFinalStep && $formOrder->jobDivisi) {
                (new UpdateStatusJobDivisiController())->updateSelesai($formOrder->jobDivisi->id);
            }
        });

        return back()->with(
            'success',
            $action === 'assign'
                ? 'Petugas berhasil ditugaskan untuk stage ' . $nextStep['name'] . '.'
                : ($requiresApproval
                    ? 'Hasil stage berhasil diajukan untuk approval.'
                    : 'Stage berhasil diselesaikan.')
        );
    }

    public function decideApproval(Request $request, StatusJobOps $statusJobOps)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $approver = Auth::user();
        $decision = $validated['decision'];
        DB::transaction(function () use ($statusJobOps, $validated, $decision, $approver) {
            $statusJobOps = StatusJobOps::query()
                ->with([
                    'workflowStage.users',
                    'workflowStage.roles',
                    'formOrder.jobDivisi',
                ])
                ->lockForUpdate()
                ->findOrFail($statusJobOps->id);

            abort_unless($statusJobOps->approval_status === 'pending', 409, 'Approval ini sudah diproses.');
            abort_unless($statusJobOps->workflowStage, 409, 'Stage approval sudah dihapus dari workflow.');
            abort_unless(
                $this->workflowAktaService->canApprove($statusJobOps->workflowStage, $approver->id),
                403
            );

            $statusJobOps->update([
                'approval_status' => $decision === 'approve' ? 'approved' : 'rejected',
                'work_status' => $decision === 'approve'
                    ? 'completed'
                    : ($statusJobOps->workflowStage->is_assignment ? 'assigned' : 'rework'),
                'approved_by' => $approver->id,
                'approval_comment' => $validated['comment'] ?? null,
                'status_penolakan' => $decision === 'reject' ? $statusJobOps->status : null,
            ]);

            $formOrder = $statusJobOps->formOrder;
            if ($decision === 'reject' && $statusJobOps->user_id && $formOrder?->jobDivisi) {
                $this->notifikasiServis->create(
                    $statusJobOps->user_id,
                    'Stage Akta Ditolak',
                    "Hasil stage {$statusJobOps->status} ditolak. Silakan perbaiki lalu ajukan kembali.",
                    $this->categoryLink($formOrder->kategori),
                    $formOrder->jobDivisi->id,
                    $formOrder->id
                );
            }

            if ($decision === 'approve' && $statusJobOps->user_id && $formOrder?->jobDivisi) {
                $this->notifikasiServis->create(
                    $statusJobOps->user_id,
                    'Stage Akta Disetujui',
                    "Hasil stage {$statusJobOps->status} telah disetujui.",
                    $this->categoryLink($formOrder->kategori),
                    $formOrder->jobDivisi->id,
                    $formOrder->id
                );
            }

            if ($decision === 'approve'
                && $this->workflowAktaService->isFinalStage($statusJobOps->workflowStage)
                && $formOrder?->jobDivisi) {
                (new UpdateStatusJobDivisiController())->updateSelesai($formOrder->jobDivisi->id);
            }
        });

        return back()->with(
            'success',
            $decision === 'approve' ? 'Approval berhasil diberikan.' : 'Stage ditolak dan dikembalikan untuk dikerjakan ulang.'
        );
    }

    private function notifyApprovers(JobAktaWorkflowStage $stage, JobDivisiFormOrder $formOrder): void
    {
        foreach ($this->approverUserIds($stage) as $approverId) {
            $this->notifikasiServis->create(
                (int) $approverId,
                'Approval Akta ' . strtoupper($formOrder->kategori),
                "Stage {$stage->name} membutuhkan approval.",
                $this->categoryLink($formOrder->kategori),
                $formOrder->jobDivisi?->id,
                $formOrder->id
            );
        }
    }

    private function approverUserIds(JobAktaWorkflowStage $stage): array
    {
        $stage->loadMissing('users', 'roles.users');
        $approverIds = $stage->users->modelKeys();
        foreach ($stage->roles as $role) {
            $approverIds = array_merge($approverIds, $role->users->modelKeys());
        }

        return array_values(array_unique($approverIds));
    }

    private function categoryLink(string $category): string
    {
        return $category === 'notaris'
            ? route('job.akta.data.index')
            : route('job.akta.data.filter', $category);
    }

    /**
     * Detail satuan job akta (opsional).
     */
    public function show(string $id)
    {
        $job_divisi = JobDivisiFormOrder::with('user', 'jobDivisi')->find($id);

        if (!$job_divisi) {
            return redirect()->back()->with('msg_error', 'Data tidak ditemukan');
        }

        $userOps = User::orderBy('name', "asc")->get()->map(fn($user) => [
            'value' => (string)$user->id,
            'label' => $user->name,
        ]);
    }
}
