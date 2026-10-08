<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penomoran_settings', function (Blueprint $table) {
            $table->string('format')->default('nnn/CN/mm/yyyy')->after('mode');
            $table->enum('month_format', ['number', 'roman'])->default('number')->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('penomoran_settings', function (Blueprint $table) {
            $table->dropColumn(['format', 'month_format']);
        });
    }
};
