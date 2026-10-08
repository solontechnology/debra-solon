<?php

namespace Tests\Unit;

use App\Models\StatusJobOps;
use App\Models\JobAktaWorkflowStage;
use App\Services\Akta\WorkflowAktaService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class WorkflowAktaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private array $workflow = [
        ['id' => 1, 'name' => 'Draft', 'approval' => ['enabled' => false]],
        ['id' => 2, 'name' => 'Minuta', 'approval' => ['enabled' => true]],
        ['id' => 3, 'name' => 'Selesai', 'approval' => ['enabled' => false]],
    ];

    public function test_it_blocks_progress_while_waiting_for_approval(): void
    {
        $lastStatus = new StatusJobOps([
            'status' => 'Minuta',
            'approval_status' => 'pending',
        ]);

        $state = (new WorkflowAktaService())->currentStep($this->workflow, $lastStatus);

        $this->assertNull($state['next_step']);
        $this->assertSame($lastStatus, $state['pending_approval']);
    }

    public function test_it_continues_to_the_next_stage_after_approval(): void
    {
        $lastStatus = new StatusJobOps([
            'status' => 'Minuta',
            'approval_status' => 'approved',
        ]);

        $state = (new WorkflowAktaService())->currentStep($this->workflow, $lastStatus);

        $this->assertSame('Selesai', $state['next_step']['name']);
    }

    public function test_it_keeps_an_assigned_stage_active_until_the_worker_submits_it(): void
    {
        $lastStatus = new StatusJobOps([
            'status' => 'Minuta',
            'workflow_stage_id' => 2,
            'work_status' => 'assigned',
            'user_id' => 42,
        ]);

        $state = (new WorkflowAktaService())->currentStep($this->workflow, $lastStatus);

        $this->assertSame('Minuta', $state['next_step']['name']);
        $this->assertSame($lastStatus, $state['active_stage']);
    }

    public function test_any_menu_authorized_user_can_submit_a_stage_without_assignment(): void
    {
        $state = [
            'next_step' => ['name' => 'Draft', 'penugasan' => false],
            'active_stage' => null,
        ];

        $this->assertTrue((new WorkflowAktaService())->canSubmitStage($state, 42));
    }

    public function test_only_the_assigned_user_can_submit_an_assignment_stage(): void
    {
        $state = [
            'next_step' => ['name' => 'Minuta', 'penugasan' => true],
            'active_stage' => new StatusJobOps(['user_id' => 42]),
        ];
        $service = new WorkflowAktaService();

        $this->assertTrue($service->canSubmitStage($state, 42));
        $this->assertFalse($service->canSubmitStage($state, 99));
    }

    public function test_an_assignment_stage_cannot_be_submitted_before_assignment(): void
    {
        $state = [
            'next_step' => ['name' => 'Minuta', 'penugasan' => true],
            'active_stage' => null,
        ];

        $this->assertFalse((new WorkflowAktaService())->canSubmitStage($state, 42));
    }

    public function test_it_returns_rejected_work_to_the_same_stage_for_rework(): void
    {
        $lastStatus = new StatusJobOps([
            'status' => 'Minuta',
            'workflow_stage_id' => 2,
            'work_status' => 'rework',
            'approval_status' => 'rejected',
        ]);

        $state = (new WorkflowAktaService())->currentStep($this->workflow, $lastStatus);

        $this->assertSame('Draft', $state['current_status']);
        $this->assertSame('Minuta', $state['next_step']['name']);
        $this->assertSame($lastStatus, $state['active_stage']);
    }

    public function test_it_identifies_the_configured_final_stage(): void
    {
        $service = new WorkflowAktaService();

        $this->assertTrue($service->isFinalStep($this->workflow, $this->workflow[2]));
        $this->assertFalse($service->isFinalStep($this->workflow, $this->workflow[1]));
    }

    public function test_only_selected_assigner_users_can_assign(): void
    {
        $stage = new JobAktaWorkflowStage();
        $stage->setRelation('assignerUsers', new EloquentCollection([
            (object) ['id' => 42],
        ]));
        $stage->setRelation('assignerRoles', new EloquentCollection());
        $service = new WorkflowAktaService();

        $this->assertTrue($service->canAssign($stage, 42));
        $this->assertFalse($service->canAssign($stage, 43));
    }

    public function test_only_members_of_selected_assigner_roles_can_assign(): void
    {
        $userQuery = new class {
            public ?int $userId = null;

            public function whereKey(int $userId): self
            {
                $this->userId = $userId;

                return $this;
            }
        };
        $roleRelation = Mockery::mock(BelongsToMany::class);
        $roleRelation->shouldReceive('whereHas')
            ->twice()
            ->with('users', Mockery::on(function (callable $callback) use ($userQuery) {
                $callback($userQuery);

                return true;
            }))
            ->andReturnSelf();
        $roleRelation->shouldReceive('exists')
            ->twice()
            ->andReturnUsing(fn () => $userQuery->userId === 42);
        $stage = Mockery::mock(JobAktaWorkflowStage::class)->makePartial();
        $stage->setRelation('assignerUsers', new EloquentCollection());
        $stage->setRelation('assignerRoles', new EloquentCollection([(object) ['id' => 7]]));
        $stage->shouldReceive('assignerRoles')->twice()->andReturn($roleRelation);
        $service = new WorkflowAktaService();

        $this->assertTrue($service->canAssign($stage, 42));
        $this->assertFalse($service->canAssign($stage, 43));
    }
}
