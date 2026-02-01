<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // TODO: Replace with actual database queries
        $stats = [
            'total_boards' => 0,
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'pending_tasks' => 0,
            'team_members' => 1,
            'my_tasks' => 0,
        ];

        $recentActivity = []; // TODO: Fetch recent activity

        return ApiResponse::success([
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'tenant' => [
                'id' => tenant('id'),
                'name' => tenant('name'),
            ],
        ]);
    }

    /**
     * Get user's tasks summary
     */
    public function myTasks(Request $request): JsonResponse
    {
        // TODO: Implement when tasks are ready
        return ApiResponse::success([
            'tasks' => [],
            'summary' => [
                'total' => 0,
                'todo' => 0,
                'in_progress' => 0,
                'done' => 0,
            ],
        ]);
    }
}