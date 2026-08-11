<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_lembur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal');
            $table->time('jam_mulai_rencana');
            $table->time('jam_selesai_rencana');
            $table->timestamp('jam_mulai_aktual')->nullable();
            $table->timestamp('jam_selesai_aktual')->nullable();
            $table->decimal('durasi_rencana', 5, 2)->nullable();
            $table->decimal('durasi_aktual', 5, 2)->nullable();
            $table->text('uraian_pekerjaan');
            $table->text('hasil_pekerjaan')->nullable();
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_lembur');
    }
};
