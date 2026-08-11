<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_komponen_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 100);
            $table->string('tipe', 20);
            $table->unsignedInteger('urutan')->default(0);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });

        Schema::create('t_payslip_periode', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->string('nama', 100);
            $table->string('status', 20)->default('draft');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('id_user_publish')->nullable();
            $table->timestamps();

            $table->foreign('id_user_publish')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('t_payslip', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_periode');
            $table->unsignedBigInteger('id_user');
            $table->json('komponen')->nullable();
            $table->decimal('gaji_bruto', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_netto', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_periode')->references('id')->on('t_payslip_periode')->cascadeOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['id_periode', 'id_user']);
        });

        Schema::create('t_payslip_akses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_payslip');
            $table->unsignedBigInteger('id_user');
            $table->string('aksi', 20);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_payslip')->references('id')->on('t_payslip')->cascadeOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_payslip_akses');
        Schema::dropIfExists('t_payslip');
        Schema::dropIfExists('t_payslip_periode');
        Schema::dropIfExists('m_komponen_gaji');
    }
};
