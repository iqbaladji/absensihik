<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'nip', 'password',
        'id_role', 'id_kantor', 'id_unit', 'id_jabatan', 'id_atasan', 'id_jadwal',
        'pin_payslip', 'device_id', 'device_model', 'device_registered_at',
        'status', 'last_activity_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'pin_payslip',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'device_registered_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ---- Relationships -------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_role');
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

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'id_jadwal');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_atasan');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(User::class, 'id_atasan');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class, 'id_user');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_user');
    }

    // ---- RBAC helpers --------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    public function roleSlug(): ?string
    {
        return $this->role?->slug;
    }

    public function hasAccess(string $modul, string $ability): bool
    {
        $role = $this->role;
        if (! $role) {
            return false;
        }
        if ($role->slug === 'administrator') {
            return true;
        }

        $matrix = $role->hak_akses ?? [];
        $allowed = $matrix[$modul] ?? [];

        return in_array($ability, $allowed, true);
    }

    public function isOrgScoped(): bool
    {
        return in_array($this->roleSlug(), ['admin_kantor', 'pegawai', 'supervisor'], true)
            && $this->id_kantor !== null;
    }
}
