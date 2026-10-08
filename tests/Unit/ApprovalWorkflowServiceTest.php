<?php

namespace Tests\Unit;

use App\Models\ApprovalFreez;
use App\Models\User;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApprovalWorkflowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The SQLite PDO driver is required for approval workflow tests.');
        }

        Schema::dropIfExists('tenant_features');
        Schema::dropIfExists('approval_request_approvers');
        Schema::dropIfExists('approval_configuration_roles');
        Schema::dropIfExists('approval_configuration_users');
        Schema::dropIfExists('approval_configurations');
        Schema::dropIfExists('approval_freezs');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });
        Schema::create('approval_freezs', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_10_08_030000_create_approval_configuration_tables.php');
        $migration->up();
        $featureMigration = require database_path('migrations/2026_10_08_040000_create_tenant_features_table.php');
        $featureMigration->up();
    }

    public function test_user_approver_snapshots_remain_authoritative_after_configuration_changes(): void
    {
        $approvers = collect(['Approver One', 'Approver Two', 'Other User'])->map(
            fn ($name) => User::create(['name' => $name])
        );
        $service = new ApprovalWorkflowService(new TenantFeatureService);
        $service->saveConfiguration('freeze', 'user', $approvers->take(2)->pluck('id')->all());
        $request = ApprovalFreez::create();

        $snapshotIds = $service->snapshot('freeze', $request);
        $service->saveConfiguration('freeze', 'user', [$approvers[2]->id]);

        $this->assertEqualsCanonicalizing(
            [$approvers[0]->id, $approvers[1]->id],
            $snapshotIds
        );
        $this->assertTrue($service->canApprove('freeze', $request, (int) $approvers[0]->id));
        $this->assertTrue($service->canApprove('freeze', $request, (int) $approvers[1]->id));
        $this->assertFalse($service->canApprove('freeze', $request, (int) $approvers[2]->id));
    }
}
