<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_role', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('nama', 60);
            $table->string('deskripsi', 255)->nullable();
            $table->json('hak_akses')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_role')->references('id')->on('m_role')->nullOnDelete();
        });

        Schema::create('t_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('username', 50)->nullable();
            $table->string('aksi', 30);
            $table->string('modul', 50);
            $table->string('ref_tabel', 50)->nullable();
            $table->string('id_ref', 36)->nullable();
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('waktu')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_user')->references('id')->on('users')->nullOnDelete();
            $table->index(['modul', 'aksi']);
            $table->index('waktu');
        });

        Schema::create('t_login_attempt', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50);
            $table->string('ip', 45);
            $table->boolean('berhasil')->default(false);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['username', 'ip', 'created_at']);
        });

        Schema::create('t_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('judul', 150);
            $table->text('pesan')->nullable();
            $table->string('tipe', 30)->nullable();
            $table->string('ref_tabel', 50)->nullable();
            $table->unsignedBigInteger('id_ref')->nullable();
            $table->string('url', 255)->nullable();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['id_user', 'dibaca_pada']);
        });

        Schema::create('t_device_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('device_id', 255);
            $table->string('device_model', 100)->nullable();
            $table->string('aksi', 20);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('m_konfigurasi', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 50)->unique();
            $table->text('nilai')->nullable();
            $table->string('tipe', 20)->default('string');
            $table->string('grup', 50)->nullable();
            $table->string('deskripsi', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_konfigurasi');
        Schema::dropIfExists('t_device_log');
        Schema::dropIfExists('t_notifikasi');
        Schema::dropIfExists('t_login_attempt');
        Schema::dropIfExists('t_audit_trail');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_role']);
        });
        Schema::dropIfExists('m_role');
    }
};
