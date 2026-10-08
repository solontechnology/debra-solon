<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_key')->unique();
            $table->string('approver_type');
            $table->timestamps();
        });

        Schema::create('approval_configuration_users', function (Blueprint $table) {
            $table->foreignId('approval_configuration_id')
                ->constrained('approval_configurations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['approval_configuration_id', 'user_id']);
        });

        Schema::create('approval_configuration_roles', function (Blueprint $table) {
            $table->foreignId('approval_configuration_id')
                ->constrained('approval_configurations')
                ->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['approval_configuration_id', 'role_id']);
        });

        Schema::create('approval_request_approvers', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_key');
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(
                ['workflow_key', 'approvable_type', 'approvable_id', 'user_id'],
                'approval_request_approvers_unique'
            );
            $table->index(['approvable_type', 'approvable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_approvers');
        Schema::dropIfExists('approval_configuration_roles');
        Schema::dropIfExists('approval_configuration_users');
        Schema::dropIfExists('approval_configurations');
    }
};
