<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockLeavePeriode extends Model
{
    protected $table = 't_block_leave_periode';

    protected $fillable = [
        'tahun', 'nama', 'tanggal_mulai', 'tanggal_selesai', 'is_aktif',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif' => 'boolean',
    ];

    public function blockLeaves(): HasMany
    {
        return $this->hasMany(BlockLeave::class, 'id_periode');
    }
}
