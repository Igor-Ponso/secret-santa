<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSecondFactorChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'code_hash',
        'attempts_remaining',
        'expires_at',
        'consumed_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
