<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'device_label',
        'token_hash',
        'last_used_at',
        'revoked_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
