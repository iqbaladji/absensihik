<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $table = 't_payslip';

    protected $fillable = [
        'id_periode', 'id_user', 'komponen',
        'gaji_bruto', 'total_potongan', 'gaji_netto',
    ];

    protected $casts = [
        'komponen' => 'array',
        'gaji_bruto' => 'decimal:2',
        'total_potongan' => 'decimal:2',
        'gaji_netto' => 'decimal:2',
    ];

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PayslipPeriode::class, 'id_periode');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function akses(): HasMany
    {
        return $this->hasMany(PayslipAkses::class, 'id_payslip');
    }
}
