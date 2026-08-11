<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_entitas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('alamat', 255)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::create('m_direktorat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_entitas');
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('id_entitas')->references('id')->on('m_entitas')->restrictOnDelete();
        });

        Schema::create('m_divisi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_direktorat');
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('id_direktorat')->references('id')->on('m_direktorat')->restrictOnDelete();
        });

        Schema::create('m_departemen', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_divisi');
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('id_divisi')->references('id')->on('m_divisi')->restrictOnDelete();
        });

        Schema::create('m_kantor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_entitas');
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('alamat', 255)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius')->default(100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('id_entitas')->references('id')->on('m_entitas')->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_kantor')->references('id')->on('m_kantor')->nullOnDelete();
        });

        Schema::create('m_unit_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_departemen')->nullable();
            $table->unsignedBigInteger('id_kantor')->nullable();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();

            $table->foreign('id_departemen')->references('id')->on('m_departemen')->nullOnDelete();
            $table->foreign('id_kantor')->references('id')->on('m_kantor')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_unit')->references('id')->on('m_unit_kerja')->nullOnDelete();
        });

        Schema::create('m_jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->unsignedInteger('level')->default(0);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_jabatan')->references('id')->on('m_jabatan')->nullOnDelete();
        });

        Schema::create('m_penempatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_kantor');
            $table->unsignedBigInteger('id_unit')->nullable();
            $table->unsignedBigInteger('id_jabatan')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_kantor')->references('id')->on('m_kantor')->restrictOnDelete();
            $table->foreign('id_unit')->references('id')->on('m_unit_kerja')->nullOnDelete();
            $table->foreign('id_jabatan')->references('id')->on('m_jabatan')->nullOnDelete();
        });

        Schema::create('m_delegasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_dari');
            $table->unsignedBigInteger('id_kepada');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('modul', 50)->nullable();
            $table->text('alasan')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->foreign('id_dari')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_kepada')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('m_approval_matrix', function (Blueprint $table) {
            $table->id();
            $table->string('modul', 50);
            $table->unsignedBigInteger('id_jabatan_pemohon')->nullable();
            $table->unsignedBigInteger('id_unit')->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->string('tipe_approver', 30);
            $table->unsignedBigInteger('id_jabatan_approver')->nullable();
            $table->unsignedBigInteger('id_user_approver')->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->foreign('id_jabatan_pemohon')->references('id')->on('m_jabatan')->nullOnDelete();
            $table->foreign('id_unit')->references('id')->on('m_unit_kerja')->nullOnDelete();
            $table->foreign('id_jabatan_approver')->references('id')->on('m_jabatan')->nullOnDelete();
            $table->foreign('id_user_approver')->references('id')->on('users')->nullOnDelete();
            $table->index(['modul', 'is_aktif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_approval_matrix');
        Schema::dropIfExists('m_delegasi');
        Schema::dropIfExists('m_penempatan');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_jabatan']);
        });
        Schema::dropIfExists('m_jabatan');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_unit']);
        });
        Schema::dropIfExists('m_unit_kerja');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_kantor']);
        });
        Schema::dropIfExists('m_kantor');
        Schema::dropIfExists('m_departemen');
        Schema::dropIfExists('m_divisi');
        Schema::dropIfExists('m_direktorat');
        Schema::dropIfExists('m_entitas');
    }
};
