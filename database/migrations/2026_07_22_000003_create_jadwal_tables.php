<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_jadwal', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('tipe', 20)->default('reguler');
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_jadwal')->references('id')->on('m_jadwal')->nullOnDelete();
        });

        Schema::create('m_shift', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_jadwal');
            $table->string('nama', 50);
            $table->string('hari', 10);
            $table->time('jam_masuk');
            $table->time('jam_keluar');
            $table->unsignedInteger('toleransi_terlambat')->default(0);
            $table->unsignedInteger('toleransi_pulang_awal')->default(0);
            $table->boolean('is_libur')->default(false);
            $table->timestamps();

            $table->foreign('id_jadwal')->references('id')->on('m_jadwal')->cascadeOnDelete();
        });

        Schema::create('m_hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama', 100);
            $table->string('tipe', 20)->default('nasional');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
        });

        Schema::create('m_kalender_kerja', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->boolean('is_hari_kerja')->default(true);
            $table->unsignedBigInteger('id_hari_libur')->nullable();
            $table->string('keterangan', 100)->nullable();
            $table->timestamps();

            $table->foreign('id_hari_libur')->references('id')->on('m_hari_libur')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_kalender_kerja');
        Schema::dropIfExists('m_hari_libur');
        Schema::dropIfExists('m_shift');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_jadwal']);
        });
        Schema::dropIfExists('m_jadwal');
    }
};
