<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    protected $table = 't_audit_trail';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_user', 'username', 'aksi', 'modul', 'ref_tabel', 'id_ref',
        'data_lama', 'data_baru', 'ip', 'user_agent', 'waktu', 'created_at',
    ];

    protected $casts = [
        'data_lama' => 'array',
        'data_baru' => 'array',
        'waktu' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
