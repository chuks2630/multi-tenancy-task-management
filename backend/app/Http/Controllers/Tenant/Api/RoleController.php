<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Get all roles with permissions
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return ApiResponse::success([
            'roles' => $roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]),
        ]);
    }

    /**
     * Get all permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode(' ', $permission->name)[1] ?? 'other';
        });

        return ApiResponse::success([
            'permissions' => $permissions,
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = \App\Models\Tenant\User::findOrFail($request->user_id);
        $user->syncRoles([$request->role]);

        return ApiResponse::success(null, 'Role assigned successfully');
    }

    /**
     * Get user permissions
     */
    public function userPermissions(Request $request, int $userId): JsonResponse
    {
        $user = \App\Models\Tenant\User::findOrFail($userId);

        return ApiResponse::success([
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}