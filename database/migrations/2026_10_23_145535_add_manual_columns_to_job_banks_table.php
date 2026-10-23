<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_banks', function (Blueprint $table) {
            $table->string('head_legal_manual')->nullable()->after('head_legal');
            $table->string('legal_manual')->nullable()->after('legal');
            $table->string('head_marketing_manual')->nullable()->after('head_marketing');
            $table->string('marketing_manual')->nullable()->after('marketing');
        });
    }

    public function down(): void
    {
        Schema::table('job_banks', function (Blueprint $table) {
            $table->dropColumn([
                'head_legal_manual',
                'legal_manual',
                'head_marketing_manual',
                'marketing_manual',
            ]);
        });
    }
};