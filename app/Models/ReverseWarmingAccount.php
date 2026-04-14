<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReverseWarmingAccount extends Model
{
    protected $fillable = [
        'email',
        'name',
        'access_token',
        'refresh_token',
        'expires_in',
        'token_expires_at',
        'daily_limit',
        'sent_today',
        'is_active',
        'status',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
