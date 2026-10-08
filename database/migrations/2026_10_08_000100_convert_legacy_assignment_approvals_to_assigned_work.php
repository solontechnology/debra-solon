<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('status_job_ops')
            ->where('approval_status', 'pending')
            ->whereNotNull('workflow_stage_id')
            ->whereNotNull('user_id')
            ->whereIn(
                'workflow_stage_id',
                DB::table('job_akta_workflow_stages')
                    ->select('id')
                    ->where('is_assignment', true)
            )
            ->update([
                'approval_status' => null,
                'work_status' => 'assigned',
            ]);
    }

    public function down(): void
    {
        DB::table('status_job_ops')
            ->where('approval_status', null)
            ->where('work_status', 'assigned')
            ->whereIn(
                'workflow_stage_id',
                DB::table('job_akta_workflow_stages')
                    ->select('id')
                    ->where('is_assignment', true)
            )
            ->update([
                'approval_status' => 'pending',
                'work_status' => null,
            ]);
    }
};
