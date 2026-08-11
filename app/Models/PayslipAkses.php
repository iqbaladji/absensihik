<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipAkses extends Model
{
    protected $table = 't_payslip_akses';

    public $timestamps = false;

    protected $fillable = [
        'id_payslip', 'id_user', 'aksi', 'ip', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'id_payslip');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
