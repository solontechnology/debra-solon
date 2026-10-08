<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_akta_workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 50);
            $table->string('name');
            $table->unsignedInteger('position');
            $table->boolean('is_approval')->default(false);
            $table->boolean('is_assignment')->default(false);
            $table->timestamps();
            $table->unique(['kategori', 'name']);
            $table->unique(['kategori', 'position']);
        });

        Schema::create('job_akta_workflow_stage_users', function (Blueprint $table) {
            $table->foreignId('workflow_stage_id')
                ->constrained('job_akta_workflow_stages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['workflow_stage_id', 'user_id']);
        });

        Schema::create('job_akta_workflow_stage_roles', function (Blueprint $table) {
            $table->foreignId('workflow_stage_id')
                ->constrained('job_akta_workflow_stages')
                ->cascadeOnDelete();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->primary(['workflow_stage_id', 'role_id']);
        });

        Schema::table('status_job_ops', function (Blueprint $table) {
            $table->foreignId('workflow_stage_id')
                ->nullable()
                ->after('job_divisi_form_order_id')
                ->constrained('job_akta_workflow_stages')
                ->nullOnDelete();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->nullable()
                ->after('status');
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approval_status')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('approval_comment')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('status_job_ops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workflow_stage_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approval_comment']);
        });

        Schema::dropIfExists('job_akta_workflow_stage_roles');
        Schema::dropIfExists('job_akta_workflow_stage_users');
        Schema::dropIfExists('job_akta_workflow_stages');
    }
};
