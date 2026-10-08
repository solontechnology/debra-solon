<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class ApprovalConfiguration extends Model
{
    protected $guarded = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'approval_configuration_users',
            'approval_configuration_id',
            'user_id'
        );
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'approval_configuration_roles',
            'approval_configuration_id',
            'role_id'
        );
    }
}
