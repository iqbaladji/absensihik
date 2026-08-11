<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaldoCuti extends Model
{
    protected $table = 't_saldo_cuti';

    protected $fillable = [
        'id_user', 'tahun', 'saldo_awal', 'terpakai', 'penyesuaian', 'sisa',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'saldo_awal' => 'integer',
        'terpakai' => 'integer',
        'penyesuaian' => 'integer',
        'sisa' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
