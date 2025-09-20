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
        'user_agent',
        'ip_address',
        'client_name',
        'client_os',
        'client_browser',
        'token_hash',
        'last_used_at',
        'revoked_at'
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $appends = ['plain_token'];

    public function getPlainTokenAttribute(): ?string
    {
        // Retrieve ephemeral plain token retained in service for current lifecycle (mainly for tests)
        return \App\Services\TwoFactorService::getPlainTokenFor($this->id);
    }
}
