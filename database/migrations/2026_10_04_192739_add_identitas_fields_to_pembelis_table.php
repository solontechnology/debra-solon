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
    Schema::table('pembelis', function (Blueprint $table) {
        $table->string('tempat_lahir')->nullable()->after('nik');
        $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
        $table->text('alamat_lengkap')->nullable()->after('tanggal_lahir');
        $table->softDeletes();
    });
}

public function down(): void
{
    Schema::table('pembelis', function (Blueprint $table) {
        $table->dropColumn(['tempat_lahir', 'tanggal_lahir', 'alamat_lengkap']);
        $table->dropSoftDeletes();
    });
}
};
