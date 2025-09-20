<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFactorResendStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resend_count',
        'last_resend_at',
        'next_allowed_resend_at',
        'suspended_at',
    ];

    protected $casts = [
        'last_resend_at' => 'datetime',
        'next_allowed_resend_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];
}