<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayslipPeriode extends Model
{
    protected $table = 't_payslip_periode';

    protected $fillable = [
        'periode', 'nama', 'status', 'validated_at', 'published_at', 'id_user_publish',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'id_periode');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_publish');
    }
}
