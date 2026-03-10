<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Sessions extends Model
{
    protected $table = 'sessions';

    protected $fillable = [
        'user_id',
        'session_token',
        'expires_at',
        'payload'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}