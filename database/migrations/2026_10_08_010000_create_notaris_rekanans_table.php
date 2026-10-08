<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notaris_rekanans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->foreignId('kota_id')->constrained('kotas');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('nomor_ppats', function (Blueprint $table) {
            $table->foreignId('notaris_rekanan_id')
                ->nullable()
                ->after('rekanan')
                ->constrained('notaris_rekanans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nomor_ppats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('notaris_rekanan_id');
        });

        Schema::dropIfExists('notaris_rekanans');
    }
};
