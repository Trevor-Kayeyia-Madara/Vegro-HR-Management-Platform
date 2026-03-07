<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\RoleService;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        return response()->json($this->roleService->getAllRoles());
    }

    public function show($id)
    {
        return response()->json($this->roleService->getRoleById($id));
    }

    public function store(Request $request)
    {
        $data = $request->only(['name', 'description']);
        return response()->json($this->roleService->createRole($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only(['name', 'description']);
        return response()->json($this->roleService->updateRole($id, $data));
    }

    public function destroy($id)
    {
        return response()->json(['success' => $this->roleService->deleteRole($id)]);
    }
}