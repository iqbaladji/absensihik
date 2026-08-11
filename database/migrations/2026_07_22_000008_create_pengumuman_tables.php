<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_jenis_pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::create('t_pengumuman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_jenis')->nullable();
            $table->string('judul', 200);
            $table->text('isi');
            $table->string('lampiran', 255)->nullable();
            $table->string('prioritas', 20)->default('normal');
            $table->boolean('wajib_konfirmasi')->default(false);
            $table->string('target_tipe', 20)->default('semua');
            $table->json('target_ids')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_jenis')->references('id')->on('m_jenis_pengumuman')->nullOnDelete();
            $table->index('status');
        });

        Schema::create('t_pengumuman_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pengumuman');
            $table->unsignedBigInteger('id_user');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('dikonfirmasi_pada')->nullable();
            $table->timestamps();

            $table->foreign('id_pengumuman')->references('id')->on('t_pengumuman')->cascadeOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['id_pengumuman', 'id_user']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_pengumuman_penerima');
        Schema::dropIfExists('t_pengumuman');
        Schema::dropIfExists('m_jenis_pengumuman');
    }
};
