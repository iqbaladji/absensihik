<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['t_cuti_besar', 't_cuti_melahirkan', 't_block_leave'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('id_pengganti')->nullable()->after('id_user');
                $t->foreign('id_pengganti')->references('id')->on('users')->nullOnDelete();
                $t->index('id_pengganti');
            });
        }
    }

    public function down(): void
    {
        foreach (['t_cuti_besar', 't_cuti_melahirkan', 't_block_leave'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['id_pengganti']);
                $t->dropIndex(['id_pengganti']);
                $t->dropColumn('id_pengganti');
            });
        }
    }
};
