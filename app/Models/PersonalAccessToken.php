<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'expires_at',
        'device_name',
        'device_type',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'abilities'    => 'json',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}
