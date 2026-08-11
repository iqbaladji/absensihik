<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_approval_log', function (Blueprint $table) {
            $table->id();
            $table->string('ref_tabel', 50);
            $table->unsignedBigInteger('id_ref');
            $table->unsignedBigInteger('id_approver');
            $table->unsignedBigInteger('id_delegasi_dari')->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->string('aksi', 20);
            $table->text('catatan')->nullable();
            $table->timestamp('waktu');
            $table->timestamp('created_at')->nullable();

            $table->foreign('id_approver')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_delegasi_dari')->references('id')->on('users')->nullOnDelete();
            $table->index(['ref_tabel', 'id_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_approval_log');
    }
};
