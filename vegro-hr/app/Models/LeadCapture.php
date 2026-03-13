<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadCapture extends Model
{
    protected $fillable = [
        'name',
        'email',
        'company',
        'message',
        'source',
    ];
}
