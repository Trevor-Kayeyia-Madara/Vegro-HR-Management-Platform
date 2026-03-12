<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Role extends Model
{
    use BelongsToCompany;

    protected $fillable = ['title', 'description', 'company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    

    public function employees() { return $this->belongsToMany(Employee::class); }
    public function users() { return $this->belongsToMany(User::class); }
    public function permissions() { return $this->belongsToMany(Permission::class); }
}
