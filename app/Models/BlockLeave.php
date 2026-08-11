<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockLeave extends Model
{
    protected $table = 't_block_leave';

    protected $fillable = [
        'id_user', 'id_periode', 'tanggal_mulai', 'tanggal_selesai',
        'jumlah_hari_kerja', 'alasan', 'status', 'approval_snapshot',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_hari_kerja' => 'integer',
        'approval_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(BlockLeavePeriode::class, 'id_periode');
    }
}
