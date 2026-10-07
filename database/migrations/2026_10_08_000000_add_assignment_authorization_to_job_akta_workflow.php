<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_akta_workflow_stage_assigner_users', function (Blueprint $table) {
            $table->foreignId('workflow_stage_id')
                ->constrained('job_akta_workflow_stages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['workflow_stage_id', 'user_id']);
        });

        Schema::create('job_akta_workflow_stage_assigner_roles', function (Blueprint $table) {
            $table->foreignId('workflow_stage_id')
                ->constrained('job_akta_workflow_stages')
                ->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['workflow_stage_id', 'role_id']);
        });

        Schema::table('status_job_ops', function (Blueprint $table) {
            $table->string('work_status', 20)->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('status_job_ops', function (Blueprint $table) {
            $table->dropColumn('work_status');
        });

        Schema::dropIfExists('job_akta_workflow_stage_assigner_roles');
        Schema::dropIfExists('job_akta_workflow_stage_assigner_users');
    }
};
