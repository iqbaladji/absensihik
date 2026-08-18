<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = ['id_user', 'endpoint', 'p256dh', 'auth', 'user_agent'];
}
