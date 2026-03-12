<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    #[OA\Get(
        path: "/api/roles",
        operationId: "getRoles",
        description: "Get list of all roles",
        summary: "List all roles",
        tags: ["Roles"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Roles retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "description", type: "string"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        return response()->json($this->roleService->getAllRoles());
    }

    #[OA\Get(
        path: "/api/roles/{id}",
        operationId: "getRole",
        description: "Get a specific role",
        summary: "Get role by ID",
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function show($id)
    {
        return response()->json($this->roleService->getRoleById($id));
    }

    #[OA\Post(
        path: "/api/roles",
        operationId: "storeRole",
        description: "Create a new role",
        summary: "Create role",
        tags: ["Roles"],
        requestBody: new OA\RequestBody(
            description: "Role data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Role name", example: "Administrator"),
                    new OA\Property(property: "description", type: "string", description: "Role description", example: "Full administrative access", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Role created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Role created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $request->validate([
            'name' => [
                'required',
                'string',
                Rule::unique('roles', 'title')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string'
        ]);
        
        // Explicitly extract and map values
        $title = $request->input('name');
        $description = $request->input('description', null);
        
        // Create data array with explicit values
        $roleData = [
            'title' => $title,
            'description' => $description
        ];
        
        $role = $this->roleService->createRole($roleData);
        return response()->json($role, 201);
    }

    #[OA\Put(
        path: "/api/roles/{id}",
        operationId: "updateRole",
        description: "Update a role",
        summary: "Update role",
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Role data",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Role name", example: "Administrator", nullable: true),
                    new OA\Property(property: "description", type: "string", description: "Role description", example: "Full administrative access", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Role updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Role updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Role not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id') ?? auth()->user()?->company_id;
        $validated = $request->validate([
            'name' => [
                'nullable',
                'string',
                Rule::unique('roles', 'title')->where('company_id', $companyId)->ignore($id),
            ],
            'description' => 'nullable|string'
        ]);
        
        // Map 'name' to 'title' for the model
        $data = [];
        if (isset($validated['name'])) {
            $data['title'] = $validated['name'];
        }
        if (isset($validated['description'])) {
            $data['description'] = $validated['description'];
        }
        
        return response()->json($this->roleService->updateRole($id, $data));
    }

    #[OA\Delete(
        path: "/api/roles/{id}",
        operationId: "destroyRole",
        description: "Delete a role",
        summary: "Delete role",
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true)
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function destroy($id)
    {
        return response()->json(['success' => $this->roleService->deleteRole($id)]);
    }

    #[OA\Post(
        path: "/api/roles/{role}/users/{user}",
        operationId: "assignUserRoleByRoute",
        description: "Attach a user to a role using route params",
        summary: "Assign role to user (route params)",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "role",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "user",
                in: "path",
                required: true,
                description: "User ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role assigned successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Role assigned successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "User or role not found")
        ]
    )]
    public function assignUserByRoute(Role $role, User $user)
    {
        $previousRoleId = $user->role_id;
        $user->role_id = $role->id;
        $user->save();
        $user->load('role');

        \DB::table('role_assignment_audits')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'previous_role_id' => $previousRoleId,
            'assigned_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
            'data' => [
                'user' => $user,
            ]
        ]);
    }

    #[OA\Get(
        path: "/api/roles/assignable",
        operationId: "getAssignableRoles",
        description: "Get roles that can be assigned to employees",
        summary: "List assignable roles",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Assignable roles retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items())
                    ]
                )
            )
        ]
    )]
    public function assignable()
    {
        $roles = Role::whereIn('title', ['HR', 'Finance', 'Manager', 'Employee'])->orderBy('title')->get();
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $roles,
        ]);
    }

    #[OA\Get(
        path: "/api/permissions",
        operationId: "getPermissions",
        description: "Get list of all permissions",
        summary: "List all permissions",
        tags: ["Permissions"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Permissions retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(property: "data", type: "array", items: new OA\Items())
                    ]
                )
            )
        ]
    )]
    public function permissions()
    {
        return response()->json([
            'success' => true,
            'message' => '',
            'data' => Permission::orderBy('group')->orderBy('label')->get(),
        ]);
    }

    #[OA\Get(
        path: "/api/roles/permissions/matrix",
        operationId: "getRolePermissionMatrix",
        description: "Get role-permission matrix",
        summary: "Role permission matrix",
        tags: ["Roles"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Matrix retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "roles", type: "array", items: new OA\Items()),
                                new OA\Property(property: "permissions", type: "array", items: new OA\Items())
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function permissionsMatrix()
    {
        $roles = Role::with('permissions')->orderBy('title')->get();
        $permissions = Permission::orderBy('group')->orderBy('label')->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }

    #[OA\Put(
        path: "/api/roles/{role}/permissions",
        operationId: "updateRolePermissions",
        description: "Update permissions for a role",
        summary: "Update role permissions",
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "role",
                in: "path",
                required: true,
                description: "Role ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "Permission IDs",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["permission_ids"],
                properties: [
                    new OA\Property(
                        property: "permission_ids",
                        type: "array",
                        items: new OA\Items(type: "integer")
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Permissions updated successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function updatePermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $newIds = collect($validated['permission_ids'])->unique()->values()->all();
        $currentIds = $role->permissions()->pluck('permissions.id')->all();

        $added = array_values(array_diff($newIds, $currentIds));
        $removed = array_values(array_diff($currentIds, $newIds));

        $role->permissions()->sync($newIds);

        $now = now();
        $auditRows = [];
        foreach ($added as $permissionId) {
            $auditRows[] = [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'assigned_by' => auth()->id(),
                'action' => 'assigned',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach ($removed as $permissionId) {
            $auditRows[] = [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'assigned_by' => auth()->id(),
                'action' => 'revoked',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($auditRows)) {
            DB::table('permission_assignment_audits')->insert($auditRows);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully',
            'data' => [
                'role' => $role,
            ],
        ]);
    }
}
