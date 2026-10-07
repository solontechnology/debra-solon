<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobAktaWorkflowStage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_approval' => 'boolean',
            'is_assignment' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'job_akta_workflow_stage_users',
            'workflow_stage_id',
            'user_id'
        );
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class,
            'job_akta_workflow_stage_roles',
            'workflow_stage_id',
            'role_id'
        );
    }

    public function assignerUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'job_akta_workflow_stage_assigner_users',
            'workflow_stage_id',
            'user_id'
        );
    }

    public function assignerRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            \Spatie\Permission\Models\Role::class,
            'job_akta_workflow_stage_assigner_roles',
            'workflow_stage_id',
            'role_id'
        );
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusJobOps::class, 'workflow_stage_id');
    }

    public function toWorkflowStep(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'penugasan' => $this->is_assignment,
            'approval' => [
                'enabled' => $this->is_approval,
                'user_ids' => $this->users->modelKeys(),
                'role_ids' => $this->roles->modelKeys(),
            ],
            'assignment' => [
                'enabled' => $this->is_assignment,
                'type' => $this->assignerRoles->isNotEmpty() && $this->assignerUsers->isEmpty()
                    ? 'role'
                    : 'user',
                'user_ids' => $this->assignerUsers->modelKeys(),
                'role_ids' => $this->assignerRoles->modelKeys(),
            ],
        ];
    }
}
