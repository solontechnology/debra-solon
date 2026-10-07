<?php

namespace Tests\Unit;

use App\Models\JobDivisiFormOrder;
use App\Models\StatusJobOps;
use App\Services\Job\JobDivisiProgressService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\TestCase;

class JobDivisiProgressServiceTest extends TestCase
{
    public function test_it_calculates_completed_processes_against_all_processes(): void
    {
        $orders = new EloquentCollection([
            $this->formOrder('notaris', [
                'status' => 'Selesai',
                'workflow_stage_id' => 3,
                'work_status' => 'completed',
            ]),
            $this->formOrder('notaris', [
                'status' => 'Minuta',
                'workflow_stage_id' => 2,
                'work_status' => 'completed',
            ]),
        ]);

        $progress = (new JobDivisiProgressService())->summarize($orders, [
            'notaris' => [
                ['id' => 1, 'name' => 'Draft'],
                ['id' => 2, 'name' => 'Minuta'],
                ['id' => 3, 'name' => 'Selesai'],
            ],
        ]);

        $this->assertSame([
            'completed' => 1,
            'total' => 2,
            'percentage' => 50,
        ], $progress);
    }

    public function test_it_does_not_count_final_stage_waiting_for_approval_as_complete(): void
    {
        $orders = new EloquentCollection([
            $this->formOrder('notaris', [
                'status' => 'Selesai',
                'workflow_stage_id' => 3,
                'work_status' => 'submitted',
                'approval_status' => 'pending',
            ]),
        ]);

        $progress = (new JobDivisiProgressService())->summarize($orders, [
            'notaris' => [['id' => 3, 'name' => 'Selesai']],
        ]);

        $this->assertSame(0, $progress['completed']);
        $this->assertSame(0, $progress['percentage']);
    }

    public function test_it_returns_zero_when_there_are_no_processes(): void
    {
        $progress = (new JobDivisiProgressService())->summarize(new EloquentCollection(), []);

        $this->assertSame([
            'completed' => 0,
            'total' => 0,
            'percentage' => 0,
        ], $progress);
    }

    public function test_it_calculates_process_progress_from_completed_workflow_stages(): void
    {
        $formOrder = new JobDivisiFormOrder(['kategori' => 'notaris']);
        $formOrder->setRelation('statusJobOps', new EloquentCollection([
            new StatusJobOps([
                'workflow_stage_id' => 1,
                'status' => 'Draft',
                'work_status' => 'completed',
            ]),
            new StatusJobOps([
                'workflow_stage_id' => 2,
                'status' => 'Minuta',
                'work_status' => 'assigned',
            ]),
        ]));

        $progress = (new JobDivisiProgressService())->summarizeStages($formOrder, [
            ['id' => 1, 'name' => 'Draft'],
            ['id' => 2, 'name' => 'Minuta'],
            ['id' => 3, 'name' => 'Selesai'],
        ]);

        $this->assertSame([
            'completed' => 1,
            'total' => 3,
            'percentage' => 33,
        ], $progress);
    }

    public function test_it_does_not_count_a_stage_that_was_rejected_after_completion(): void
    {
        $formOrder = new JobDivisiFormOrder(['kategori' => 'notaris']);
        $formOrder->setRelation('statusJobOps', new EloquentCollection([
            new StatusJobOps([
                'id' => 1,
                'workflow_stage_id' => 1,
                'status' => 'Draft',
                'work_status' => 'completed',
            ]),
            new StatusJobOps([
                'id' => 2,
                'workflow_stage_id' => 1,
                'status' => 'Draft',
                'work_status' => 'rework',
                'approval_status' => 'rejected',
            ]),
        ]));

        $progress = (new JobDivisiProgressService())->summarizeStages($formOrder, [
            ['id' => 1, 'name' => 'Draft'],
        ]);

        $this->assertSame([
            'completed' => 0,
            'total' => 1,
            'percentage' => 0,
        ], $progress);
    }

    private function formOrder(string $category, array $status): JobDivisiFormOrder
    {
        $formOrder = new JobDivisiFormOrder(['kategori' => $category]);
        $formOrder->setRelation('statusJobOps', new EloquentCollection([
            new StatusJobOps($status),
        ]));

        return $formOrder;
    }
}
