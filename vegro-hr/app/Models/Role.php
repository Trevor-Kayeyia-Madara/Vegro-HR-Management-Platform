<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['title', 'description'];
    

    public function employees() { return $this->belongsToMany(Employee::class); }
    public function users() { return $this->belongsToMany(User::class); }
    public function permissions() { return $this->belongsToMany(Permission::class); }
}
