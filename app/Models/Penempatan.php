<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penempatan extends Model
{
    protected $table = 'm_penempatan';

    protected $fillable = [
        'id_user', 'id_kantor', 'id_unit', 'id_jabatan',
        'tanggal_mulai', 'tanggal_selesai', 'is_aktif', 'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_aktif' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function kantor(): BelongsTo
    {
        return $this->belongsTo(Kantor::class, 'id_kantor');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'id_jabatan');
    }
}
