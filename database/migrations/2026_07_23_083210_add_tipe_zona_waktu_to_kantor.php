<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('m_kantor', function (Blueprint $table) {
            $table->string('tipe', 20)->default('cabang')->after('nama');
            $table->string('zona_waktu', 30)->nullable()->default('Asia/Jakarta')->after('radius');
        });
    }

    public function down(): void
    {
        Schema::table('m_kantor', function (Blueprint $table) {
            $table->dropColumn(['tipe', 'zona_waktu']);
        });
    }
};
