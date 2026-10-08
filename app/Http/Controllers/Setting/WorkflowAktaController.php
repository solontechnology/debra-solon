<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\JobAktaWorkflowStage;
use App\Models\PenomoranSetting;
use App\Models\StatusJobOps;
use App\Models\User;
use App\Services\Akta\WorkflowAktaService;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class WorkflowAktaController extends Controller
{
    public function __construct(private TenantFeatureService $tenantFeatureService) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('setting/workflow-akta/list'), 403);
        abort_unless($this->tenantFeatureService->isEnabled('configurable_akta_workflow'), 404);
        $categories = PenomoranSetting::kategori();
        $category = $request->query('kategori', 'notaris');
        abort_unless(array_key_exists($category, $categories), 404);

        $stages = JobAktaWorkflowStage::query()
            ->where('kategori', $category)
            ->with(['users:id,name', 'roles:id,name', 'assignerUsers:id', 'assignerRoles:id'])
            ->orderBy('position')
            ->get()
            ->map(fn ($stage) => [
                'id' => $stage->id,
                'name' => $stage->name,
                'is_approval' => $stage->is_approval,
                'is_assignment' => $stage->is_assignment,
                'user_ids' => $stage->users->modelKeys(),
                'role_ids' => $stage->roles->modelKeys(),
                'approver_type' => $stage->roles->isNotEmpty() && $stage->users->isEmpty()
                    ? 'role'
                    : 'user',
                'assigner_user_ids' => $stage->assignerUsers->modelKeys(),
                'assigner_role_ids' => $stage->assignerRoles->modelKeys(),
                'assigner_type' => $stage->assignerRoles->isNotEmpty() && $stage->assignerUsers->isEmpty()
                    ? 'role'
                    : 'user',
            ])
            ->all();

        if ($stages === []) {
            $workflow = app(WorkflowAktaService::class)->forCategory($category);
            $stages = array_map(fn ($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'is_approval' => (bool) ($stage['approval']['enabled'] ?? false),
                'is_assignment' => (bool) ($stage['penugasan'] ?? false),
                'user_ids' => $stage['approval']['user_ids'] ?? [],
                'role_ids' => $stage['approval']['role_ids'] ?? [],
                'approver_type' => ! empty($stage['approval']['role_ids']) && empty($stage['approval']['user_ids'])
                    ? 'role'
                    : 'user',
                'assigner_user_ids' => $stage['assignment']['user_ids'] ?? [],
                'assigner_role_ids' => $stage['assignment']['role_ids'] ?? [],
                'assigner_type' => ! empty($stage['assignment']['role_ids']) && empty($stage['assignment']['user_ids'])
                    ? 'role'
                    : 'user',
            ], $workflow);
        }

        $stages = old('stages', $stages);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'username']);
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('pages.setting.workflow-akta.index', compact(
            'categories',
            'category',
            'stages',
            'users',
            'roles'
        ));
    }

    public function update(Request $request, string $category)
    {
        abort_unless(auth()->user()->can('setting/workflow-akta/list'), 403);
        abort_unless($this->tenantFeatureService->isEnabled('configurable_akta_workflow'), 404);
        abort_unless(array_key_exists($category, PenomoranSetting::kategori()), 404);

        $validated = $request->validate([
            'stages' => ['present', 'array'],
            'stages.*.id' => [
                'nullable',
                'integer',
                Rule::exists('job_akta_workflow_stages', 'id')
                    ->where('kategori', $category),
            ],
            'stages.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'stages.*.is_approval' => ['nullable', 'boolean'],
            'stages.*.is_assignment' => ['nullable', 'boolean'],
            'stages.*.approver_type' => ['required', 'in:user,role'],
            'stages.*.assigner_type' => ['required', 'in:user,role'],
            'stages.*.users' => ['nullable', 'array', 'max:1'],
            'stages.*.users.*' => ['integer', 'exists:users,id'],
            'stages.*.roles' => ['nullable', 'array', 'max:1'],
            'stages.*.roles.*' => ['integer', 'exists:roles,id'],
            'stages.*.assigner_users' => ['nullable', 'array'],
            'stages.*.assigner_users.*' => ['integer', 'exists:users,id'],
            'stages.*.assigner_roles' => ['nullable', 'array'],
            'stages.*.assigner_roles.*' => ['integer', 'exists:roles,id'],
        ]);

        foreach ($validated['stages'] as $index => $stage) {
            foreach (['users', 'roles', 'assigner_users', 'assigner_roles'] as $approverType) {
                $approverIds = $stage[$approverType] ?? [];
                if (count($approverIds) !== count(array_unique($approverIds))) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "stages.{$index}.{$approverType}" => 'Pilihan user/role yang sama tidak boleh diulang pada stage yang sama.',
                        ]);
                }
            }

            $approverSelection = $stage['approver_type'] === 'user'
                ? ($stage['users'] ?? [])
                : ($stage['roles'] ?? []);
            if (! empty($stage['is_approval']) && empty($approverSelection)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "stages.{$index}.approver_type" => 'Pilih minimal satu '.($stage['approver_type'] === 'user' ? 'user' : 'role').
                            " approver untuk stage {$stage['name']}.",
                    ]);
            }
            $assignerSelection = $stage['assigner_type'] === 'user'
                ? ($stage['assigner_users'] ?? [])
                : ($stage['assigner_roles'] ?? []);
            if (! empty($stage['is_assignment']) && empty($assignerSelection)) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "stages.{$index}.assigner_type" => 'Pilih minimal satu '.($stage['assigner_type'] === 'user' ? 'user' : 'role').
                            " yang berwenang memberi penugasan untuk stage {$stage['name']}.",
                    ]);
            }
        }

        $currentStages = JobAktaWorkflowStage::query()
            ->where('kategori', $category)
            ->with(['users:id', 'roles:id', 'assignerUsers:id', 'assignerRoles:id'])
            ->get()
            ->keyBy('id');
        $latestStatusIds = StatusJobOps::query()
            ->selectRaw('MAX(id)')
            ->groupBy('job_divisi_form_order_id');
        $pendingStageIds = StatusJobOps::query()
            ->whereIn('id', $latestStatusIds)
            ->whereIn('workflow_stage_id', $currentStages->keys())
            ->where(function ($query) {
                $query->where('approval_status', 'pending')
                    ->orWhere(function ($query) {
                        $query->where('approval_status', 'rejected')
                            ->where(function ($query) {
                                $query->whereNull('work_status')
                                    ->orWhere('work_status', '!=', 'superseded');
                            });
                    })
                    ->orWhere('work_status', 'assigned');
            })
            ->distinct()
            ->pluck('workflow_stage_id');

        foreach ($pendingStageIds as $pendingStageId) {
            $currentStage = $currentStages->get($pendingStageId);
            $submittedStages = collect($validated['stages']);
            $submittedPosition = $submittedStages->search(
                fn ($stage) => (int) ($stage['id'] ?? 0) === (int) $pendingStageId
            );
            $submittedStage = $submittedStages
                ->first(fn ($stage) => (int) ($stage['id'] ?? 0) === (int) $pendingStageId) ?? [];
            $submittedUserIds = collect($submittedStage['users'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submittedRoleIds = collect($submittedStage['roles'] ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            $currentUserIds = $currentStage->users->modelKeys();
            $currentRoleIds = $currentStage->roles->modelKeys();
            $hasPendingApproval = StatusJobOps::query()
                ->whereIn('id', $latestStatusIds)
                ->where('workflow_stage_id', $pendingStageId)
                ->where('approval_status', 'pending')
                ->exists();
            $submittedApproverType = $submittedStage['approver_type'] ?? 'user';
            sort($currentUserIds);
            sort($currentRoleIds);
            $approvalConfigChanged = $hasPendingApproval
                && ((bool) ($submittedStage['is_approval'] ?? false) !== $currentStage->is_approval
                    || ($submittedApproverType === 'user'
                        ? ($submittedUserIds !== $currentUserIds || $currentRoleIds !== [])
                        : ($submittedRoleIds !== $currentRoleIds || $currentUserIds !== [])));

            if (! $submittedStage
                || trim($submittedStage['name']) !== $currentStage->name
                || $submittedPosition + 1 !== (int) $currentStage->position
                || (bool) ($submittedStage['is_assignment'] ?? false) !== $currentStage->is_assignment) {
                return back()
                    ->withInput()
                    ->with('error', "Nama, urutan, dan status penugasan untuk stage {$currentStage->name} tidak dapat diubah saat pekerjaan masih aktif.");
            }

            if ($approvalConfigChanged) {
                return back()
                    ->withInput()
                    ->with('error', "Pengaturan approval stage {$currentStage->name} tidak dapat diubah karena hasil pekerjaan sedang menunggu approval.");
            }
        }

        DB::transaction(function () use ($validated, $category) {
            $currentStages = JobAktaWorkflowStage::query()
                ->where('kategori', $category)
                ->get();
            $submittedIds = collect($validated['stages'])
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id);

            if ($currentStages->isNotEmpty()) {
                JobAktaWorkflowStage::query()
                    ->where('kategori', $category)
                    ->increment('position', $currentStages->count() + count($validated['stages']));
            }

            JobAktaWorkflowStage::query()
                ->where('kategori', $category)
                ->when($submittedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $submittedIds))
                ->when($submittedIds->isEmpty(), fn ($query) => $query)
                ->delete();

            foreach ($validated['stages'] as $position => $data) {
                $stage = ! empty($data['id'])
                    ? JobAktaWorkflowStage::query()->where('kategori', $category)->findOrFail($data['id'])
                    : new JobAktaWorkflowStage;
                $stage->fill([
                    'kategori' => $category,
                    'name' => trim($data['name']),
                    'position' => $position + 1,
                    'is_approval' => ! empty($data['is_approval']),
                    'is_assignment' => ! empty($data['is_assignment']),
                ]);
                $stage->save();
                $stage->users()->sync(($data['approver_type'] ?? 'user') === 'user' ? ($data['users'] ?? []) : []);
                $stage->roles()->sync(($data['approver_type'] ?? 'user') === 'role' ? ($data['roles'] ?? []) : []);
                $stage->assignerUsers()->sync(($data['assigner_type'] ?? 'user') === 'user' ? ($data['assigner_users'] ?? []) : []);
                $stage->assignerRoles()->sync(($data['assigner_type'] ?? 'user') === 'role' ? ($data['assigner_roles'] ?? []) : []);
            }
        });

        return redirect()
            ->route('setting.workflow-akta.index', ['kategori' => $category])
            ->with('success', 'Workflow berhasil disimpan.');
    }
}
