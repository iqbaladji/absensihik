<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wfh extends Model
{
    protected $table = 't_wfh';

    protected $fillable = [
        'id_user', 'tanggal_mulai', 'tanggal_selesai',
        'alasan', 'lampiran', 'status', 'approval_snapshot',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'approval_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
