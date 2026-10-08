<?php

namespace App\Services\Approval;

use App\Models\ApprovalConfiguration;
use App\Models\User;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

class ApprovalWorkflowService
{
    public function __construct(private TenantFeatureService $tenantFeatures) {}

    private const DEFAULT_ROLES = [
        'freeze' => ['super admin'],
        'penambahan_item' => ['super admin'],
        'pembatalan_item' => ['super admin'],
        'finance' => ['super admin', 'finance'],
    ];

    public function configuration(string $workflowKey): array
    {
        $configuration = ApprovalConfiguration::query()
            ->with(['users:id,name,username', 'roles:id,name'])
            ->where('workflow_key', $workflowKey)
            ->first();

        if ($configuration) {
            return [
                'approver_type' => $configuration->approver_type,
                'user_ids' => $configuration->users->modelKeys(),
                'role_ids' => $configuration->roles->modelKeys(),
            ];
        }

        $defaultRoleIds = Role::query()
            ->whereIn('name', self::DEFAULT_ROLES[$workflowKey] ?? [])
            ->pluck('id')
            ->all();

        return [
            'approver_type' => 'role',
            'user_ids' => [],
            'role_ids' => $defaultRoleIds,
        ];
    }

    public function saveConfiguration(string $workflowKey, string $approverType, array $principalIds): void
    {
        $configuration = ApprovalConfiguration::query()->updateOrCreate(
            ['workflow_key' => $workflowKey],
            ['approver_type' => $approverType]
        );

        $configuration->users()->sync($approverType === 'user' ? $principalIds : []);
        $configuration->roles()->sync($approverType === 'role' ? $principalIds : []);
    }

    public function approverIds(string $workflowKey): array
    {
        if (! $this->tenantFeatures->isEnabled('configurable_approval')) {
            return $this->defaultApproverIds($workflowKey);
        }

        $configuration = ApprovalConfiguration::query()
            ->with(['users:id', 'roles.users:id'])
            ->where('workflow_key', $workflowKey)
            ->first();

        if (! $configuration) {
            return $this->defaultApproverIds($workflowKey);
        }

        $approverIds = $configuration->approver_type === 'user'
            ? $configuration->users->modelKeys()
            : $configuration->roles->flatMap(fn ($role) => $role->users->modelKeys())->all();

        return array_values(array_unique(array_map('intval', $approverIds)));
    }

    private function defaultApproverIds(string $workflowKey): array
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn(
                'name',
                self::DEFAULT_ROLES[$workflowKey] ?? []
            ))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function snapshot(string $workflowKey, Model $request): array
    {
        $approverIds = $this->approverIds($workflowKey);
        if ($approverIds === []) {
            throw new RuntimeException("Belum ada approver aktif untuk proses {$workflowKey}.");
        }

        $now = now();
        DB::table('approval_request_approvers')->insertOrIgnore(
            array_map(fn ($userId) => [
                'workflow_key' => $workflowKey,
                'approvable_type' => $request::class,
                'approvable_id' => $request->getKey(),
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $approverIds)
        );

        return $approverIds;
    }

    public function canApprove(string $workflowKey, Model $request, int $userId): bool
    {
        $hasSnapshot = DB::table('approval_request_approvers')
            ->where('workflow_key', $workflowKey)
            ->where('approvable_type', $request::class)
            ->where('approvable_id', $request->getKey())
            ->exists();

        if ($hasSnapshot) {
            return DB::table('approval_request_approvers')
                ->where('workflow_key', $workflowKey)
                ->where('approvable_type', $request::class)
                ->where('approvable_id', $request->getKey())
                ->where('user_id', $userId)
                ->exists();
        }

        return in_array($userId, $this->approverIds($workflowKey), true);
    }
}
