<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'creator_id',
        'token',
        'last_rotated_at'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    protected $hidden = [
        'token',
    ];
}