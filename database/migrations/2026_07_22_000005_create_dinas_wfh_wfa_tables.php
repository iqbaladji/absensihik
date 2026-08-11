<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_dinas_luar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('tujuan', 255);
            $table->text('keperluan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'status']);
        });

        Schema::create('t_wfh', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'status']);
        });

        Schema::create('t_wfa', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('lokasi', 255);
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_wfa');
        Schema::dropIfExists('t_wfh');
        Schema::dropIfExists('t_dinas_luar');
    }
};
