<?php

namespace App\Services;
use App\Repositories\RoleRepository;

class RoleService
{
    protected $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function getAllRoles()
    {
        return $this->roleRepository->all();
    }

    public function getRoleById($id)
    {
        return $this->roleRepository->find($id);
    }

    public function createRole(array $data)
    {
        $role = $this->roleRepository->create($data);
        return $role?->load('permissions');
    }

    public function updateRole($id, array $data)
    {
        $role = $this->roleRepository->update($id, $data);
        return $role?->load('permissions');
    }

    public function deleteRole($id)
    {
        return $this->roleRepository->delete($id);
    }
}
