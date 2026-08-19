<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prayer_times', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_kantor');
            $table->date('tanggal');
            $table->time('fajr');
            $table->time('dhuhr');
            $table->time('asr');
            $table->time('maghrib');
            $table->time('isha');
            $table->timestamps();

            $table->unique(['id_kantor', 'tanggal']);
            $table->foreign('id_kantor')->references('id')->on('m_kantor')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_times');
    }
};
