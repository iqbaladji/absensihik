<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalMatrix extends Model
{
    protected $table = 'm_approval_matrix';

    protected $fillable = [
        'modul', 'id_jabatan_pemohon', 'id_unit', 'urutan',
        'tipe_approver', 'id_jabatan_approver', 'id_user_approver', 'is_aktif',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'is_aktif' => 'boolean',
    ];

    public function jabatanPemohon(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan_pemohon');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit');
    }

    public function jabatanApprover(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan_approver');
    }

    public function userApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_approver');
    }
}
