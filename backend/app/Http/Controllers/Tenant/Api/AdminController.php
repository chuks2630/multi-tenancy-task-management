<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get all users (admin only)
     */
    public function users(): JsonResponse
    {
        $users = User::with('roles')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'roles' => $user->roles->map(fn($role) => ['name' => $role->name]),
                ];
            });

        return ApiResponse::success([
            'users' => $users,
        ]);
    }

    /**
     * Deactivate user
     */
    public function deactivateUser(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return ApiResponse::error('You cannot deactivate yourself', null, 400);
        }

        // Prevent deactivating owners
        if ($user->role === 'owner') {
            return ApiResponse::error('Cannot deactivate owner users', null, 403);
        }

        $user->update(['is_active' => false]);

        // Revoke all tokens
        $user->tokens()->delete();

        return ApiResponse::success(null, 'User deactivated successfully');
    }

    /**
     * Activate user
     */
    public function activateUser(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $user->update(['is_active' => true]);

        return ApiResponse::success(null, 'User activated successfully');
    }

    /**
     * Delete user
     */
    public function deleteUser(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return ApiResponse::error('You cannot delete yourself', null, 400);
        }

        // Prevent deleting owners
        if ($user->role === 'owner') {
            return ApiResponse::error('Cannot delete owner users', null, 403);
        }

        // Delete user's tokens
        $user->tokens()->delete();

        // Delete user
        $user->delete();

        return ApiResponse::success(null, 'User deleted successfully');
    }
}