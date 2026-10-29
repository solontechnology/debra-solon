<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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

            if (Schema::hasColumn($table, 'created_by') && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)
                    ->whereNull('created_by')
                    ->whereNotNull('user_id')
                    ->update(['created_by' => DB::raw('user_id')]);
            }

            if (Schema::hasColumn($table, 'updated_by') && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)
                    ->whereNull('updated_by')
                    ->whereNotNull('user_id')
                    ->update(['updated_by' => DB::raw('user_id')]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op; this is a data repair migration.
    }
};
