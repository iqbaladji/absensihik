<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_presensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_kantor')->nullable();
            $table->date('tanggal');
            $table->timestamp('jam_masuk')->nullable();
            $table->timestamp('jam_keluar')->nullable();
            $table->decimal('lat_masuk', 10, 7)->nullable();
            $table->decimal('lng_masuk', 10, 7)->nullable();
            $table->decimal('accuracy_masuk', 8, 2)->nullable();
            $table->decimal('jarak_masuk', 10, 2)->nullable();
            $table->decimal('lat_keluar', 10, 7)->nullable();
            $table->decimal('lng_keluar', 10, 7)->nullable();
            $table->decimal('accuracy_keluar', 8, 2)->nullable();
            $table->decimal('jarak_keluar', 10, 2)->nullable();
            $table->string('foto_masuk', 255)->nullable();
            $table->string('foto_keluar', 255)->nullable();
            $table->string('device_id', 255)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('tipe', 20)->default('di_kantor');
            $table->string('status_masuk', 20)->nullable();
            $table->string('status_keluar', 20)->nullable();
            $table->boolean('perlu_verifikasi')->default(false);
            $table->unsignedBigInteger('id_verifikator')->nullable();
            $table->timestamp('waktu_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_kantor')->references('id')->on('m_kantor')->nullOnDelete();
            $table->foreign('id_verifikator')->references('id')->on('users')->nullOnDelete();
            $table->unique(['id_user', 'tanggal']);
            $table->index('tanggal');
        });

        Schema::create('t_presensi_koreksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_presensi')->nullable();
            $table->date('tanggal');
            $table->time('jam_masuk_koreksi')->nullable();
            $table->time('jam_keluar_koreksi')->nullable();
            $table->text('alasan');
            $table->string('lampiran', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('id_approver')->nullable();
            $table->timestamp('waktu_approval')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_presensi')->references('id')->on('t_presensi')->nullOnDelete();
            $table->foreign('id_approver')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_presensi_koreksi');
        Schema::dropIfExists('t_presensi');
    }
};
