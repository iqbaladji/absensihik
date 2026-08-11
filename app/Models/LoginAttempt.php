<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 't_login_attempt';

    public $timestamps = false;

    protected $fillable = [
        'username', 'ip', 'berhasil', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'berhasil' => 'boolean',
        'created_at' => 'datetime',
    ];
}
