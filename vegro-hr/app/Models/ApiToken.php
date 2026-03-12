<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class ApiToken extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'user_id', 'token', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
