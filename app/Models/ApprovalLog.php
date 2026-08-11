<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    protected $table = 't_approval_log';

    public $timestamps = false;

    protected $fillable = [
        'ref_tabel', 'id_ref', 'id_approver', 'id_delegasi_dari',
        'urutan', 'aksi', 'catatan', 'waktu', 'created_at',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'waktu' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_approver');
    }

    public function delegasiDari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_delegasi_dari');
    }
}
