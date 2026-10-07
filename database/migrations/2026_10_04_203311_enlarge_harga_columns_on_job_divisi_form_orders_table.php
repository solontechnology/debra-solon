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
        Schema::table('job_divisi_form_orders', function (Blueprint $table) {
            $table->decimal('harga_modal', 65, 2)->change();
            $table->decimal('harga_proses', 65, 2)->change();
            $table->decimal('harga_jual', 65, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_divisi_form_orders', function (Blueprint $table) {
            $table->decimal('harga_modal', 10, 2)->change();
            $table->decimal('harga_proses', 10, 2)->change();
            $table->decimal('harga_jual', 10, 2)->change();
        });
    }
};
