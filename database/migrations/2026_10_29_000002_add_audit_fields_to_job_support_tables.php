<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'job_banks',
            'job_developers',
            'debiturs',
            'job_divisi_objeks',
            'pembelis',
            'penjuals',
            'job_divisi_badan_hukums',
            'job_divisi_badan_usaha_debiturs',
            'job_divisi_badan_usaha_pembelis',
            'job_divisi_badan_usaha_penjuals',
            'job_divisi_brokers',
            'job_divisi_pendirian_lembagas',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                if (!Schema::hasColumn($table, 'created_by')) {
                    $tableBlueprint->bigInteger('created_by')->nullable()->after('id');
                }

                if (!Schema::hasColumn($table, 'updated_by')) {
                    $tableBlueprint->bigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'job_banks',
            'job_developers',
            'debiturs',
            'job_divisi_objeks',
            'pembelis',
            'penjuals',
            'job_divisi_badan_hukums',
            'job_divisi_badan_usaha_debiturs',
            'job_divisi_badan_usaha_pembelis',
            'job_divisi_badan_usaha_penjuals',
            'job_divisi_brokers',
            'job_divisi_pendirian_lembagas',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $tableBlueprint) {
                if (Schema::hasColumn($table, 'updated_by')) {
                    $tableBlueprint->dropColumn('updated_by');
                }

                if (Schema::hasColumn($table, 'created_by')) {
                    $tableBlueprint->dropColumn('created_by');
                }
            });
        }
    }
};
