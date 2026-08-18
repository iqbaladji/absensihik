<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('t_cuti_tahunan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pengganti')->nullable()->after('id_user');
            $table->foreign('id_pengganti')->references('id')->on('users')->nullOnDelete();
            $table->index('id_pengganti');
        });
    }

    public function down(): void
    {
        Schema::table('t_cuti_tahunan', function (Blueprint $table) {
            $table->dropForeign(['id_pengganti']);
            $table->dropIndex(['id_pengganti']);
            $table->dropColumn('id_pengganti');
        });
    }
};
