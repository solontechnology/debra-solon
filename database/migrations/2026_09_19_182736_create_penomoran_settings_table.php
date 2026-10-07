<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penomoran_settings', function (Blueprint $table) {
            $table->id();
            $table->string('kategori')->unique();
            $table->enum('reset_period', ['month', 'year'])->default('year');
            $table->enum('mode', ['automatic', 'manual'])->default('automatic');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penomoran_settings');
    }
};