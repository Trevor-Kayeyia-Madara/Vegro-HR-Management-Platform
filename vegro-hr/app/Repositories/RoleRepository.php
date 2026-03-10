<?php

namespace App\Repositories;
use App\Models\Role;

class RoleRepository
{
    public function all()
    {
        return Role::with('permissions')->get();
    }

    public function getRoleIdByName($name)
    {
        $role = Role::where('title', $name)->first();
        return $role ? $role->id : null;
    }

    public function find($id)
    {
        return Role::with('permissions')->find($id);
    }

    public function findByName($name)
    {
        return Role::where('title', $name)->first();
    }

    public function create(array $data)
    {
        return Role::create($data);
    }

    public function update($id, array $data)
    {
        $role = $this->find($id);
        if ($role) {
            $role->update($data);
            return $role;
        }
        return null;
    }

    public function delete($id)
    {
        $role = $this->find($id);
        if ($role) {
            return $role->delete();
        }
        return false;
    }
}
