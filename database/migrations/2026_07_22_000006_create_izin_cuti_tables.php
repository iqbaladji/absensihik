<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_jenis_izin', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->boolean('potong_cuti')->default(false);
            $table->unsignedInteger('maks_hari')->nullable();
            $table->boolean('perlu_lampiran')->default(false);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::create('t_izin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_jenis_izin');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_jenis_izin')->references('id')->on('m_jenis_izin')->restrictOnDelete();
            $table->index(['id_user', 'status']);
        });

        Schema::create('t_cuti_tahunan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'status']);
        });

        Schema::create('t_saldo_cuti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('saldo_awal')->default(12);
            $table->unsignedInteger('terpakai')->default(0);
            $table->integer('penyesuaian')->default(0);
            $table->unsignedInteger('sisa')->default(12);
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['id_user', 'tahun']);
        });

        Schema::create('t_block_leave', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_periode')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari_kerja')->default(5);
            $table->text('alasan')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'status']);
        });

        Schema::create('t_block_leave_periode', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('nama', 100);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        Schema::table('t_block_leave', function (Blueprint $table) {
            $table->foreign('id_periode')->references('id')->on('t_block_leave_periode')->nullOnDelete();
        });

        Schema::create('t_cuti_melahirkan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->string('tipe', 20)->default('melahirkan');
            $table->text('catatan')->nullable();
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('t_cuti_besar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('approval_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_cuti_besar');
        Schema::dropIfExists('t_cuti_melahirkan');
        Schema::table('t_block_leave', function (Blueprint $table) {
            $table->dropForeign(['id_periode']);
        });
        Schema::dropIfExists('t_block_leave_periode');
        Schema::dropIfExists('t_block_leave');
        Schema::dropIfExists('t_saldo_cuti');
        Schema::dropIfExists('t_cuti_tahunan');
        Schema::dropIfExists('t_izin');
        Schema::dropIfExists('m_jenis_izin');
    }
};
