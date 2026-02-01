<?php

namespace App\Services;

use App\Models\Tenant\Task;
use App\Models\Tenant\Board;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    /**
     * Get analytics overview
     */
    public function getOverview(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Total tasks
        $totalTasks = Task::count();
        $completedTasks = Task::where('status', 'done')->count();
        $inProgressTasks = Task::where('status', 'in_progress')->count();
        
        // Completion rate
        $completionRate = $totalTasks > 0 
            ? ($completedTasks / $totalTasks) * 100 
            : 0;

        // Active boards (boards with tasks)
        $activeBoards = Board::has('tasks')->count();

        // Active users (users who created or were assigned tasks)
        $activeUsers = User::where(function($query) {
            $query->whereHas('createdTasks')
                  ->orWhereHas('assignedTasks');
        })->count();

        // Tasks created this week
        $tasksCreatedThisWeek = Task::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ])->count();

        // Tasks completed this week
        $tasksCompletedThisWeek = Task::where('status', 'done')
            ->whereBetween('updated_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count();

        // Productivity score (0-100)
        $productivityScore = $this->calculateProductivityScore(
            $completionRate,
            $tasksCompletedThisWeek,
            $activeUsers
        );

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'completion_rate' => round($completionRate, 2),
            'active_boards' => $activeBoards,
            'active_users' => $activeUsers,
            'tasks_created_this_week' => $tasksCreatedThisWeek,
            'tasks_completed_this_week' => $tasksCompletedThisWeek,
            'productivity_score' => $productivityScore,
        ];
    }

    /**
     * Get task trends
     */
    public function getTaskTrends(?string $startDate = null, ?string $endDate = null, string $interval = 'daily'): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $trends = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $nextDate = match($interval) {
                'weekly' => $current->copy()->addWeek(),
                'monthly' => $current->copy()->addMonth(),
                default => $current->copy()->addDay(),
            };

            $created = Task::whereBetween('created_at', [$current, $nextDate])->count();
            $completed = Task::where('status', 'done')
                ->whereBetween('updated_at', [$current, $nextDate])
                ->count();

            $trends[] = [
                'date' => $current->toDateString(),
                'created' => $created,
                'completed' => $completed,
            ];

            $current = $nextDate;
        }

        return $trends;
    }

    /**
     * Get board activity
     */
    public function getBoardActivity(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Board::withCount([
            'tasks',
            'tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'done');
            },
        ]);

        $boards = $query->get()->map(function ($board) {
            $completionRate = $board->tasks_count > 0
                ? ($board->completed_tasks_count / $board->tasks_count) * 100
                : 0;

            return [
                'board_id' => $board->id,
                'board_name' => $board->name,
                'total_tasks' => $board->tasks_count,
                'completed_tasks' => $board->completed_tasks_count,
                'completion_rate' => round($completionRate, 2),
            ];
        })->sortByDesc('total_tasks')->values()->toArray();

        return $boards;
    }

    /**
     * Get user activity
     */
    public function getUserActivity(?string $startDate = null, ?string $endDate = null): array
    {
        $users = User::withCount([
            'createdTasks as tasks_created',
            'assignedTasks as tasks_completed' => function ($query) {
                $query->where('status', 'done');
            },
        ])->get()->map(function ($user) {
            // Count boards where user created tasks
            $boardsManaged = Board::whereHas('tasks', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })->count();

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'tasks_created' => $user->tasks_created,
                'tasks_completed' => $user->tasks_completed,
                'boards_managed' => $boardsManaged,
            ];
        })->sortByDesc('tasks_created')->values()->toArray();

        return $users;
    }

    /**
     * Get priority distribution
     */
    public function getPriorityDistribution(): array
    {
        return Task::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->map(function ($item) {
                return [
                    'priority' => $item->priority,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get status distribution
     */
    public function getStatusDistribution(): array
    {
        return Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Export analytics data
     */
    public function exportData(?string $startDate = null, ?string $endDate = null, string $format = 'csv')
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Get all data
        $tasks = Task::with(['board', 'assignee', 'creator'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($format === 'json') {
            return response()->json([
                'tasks' => $tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'board' => $task->board->name ?? null,
                        'assignee' => $task->assignee->name ?? null,
                        'creator' => $task->creator->name ?? null,
                        'created_at' => $task->created_at->toIso8601String(),
                        'due_date' => $task->due_date?->toIso8601String(),
                    ];
                }),
            ]);
        }

        // CSV format
        $csv = "ID,Title,Status,Priority,Board,Assignee,Creator,Created At,Due Date\n";
        
        foreach ($tasks as $task) {
            $csv .= implode(',', [
                $task->id,
                '"' . str_replace('"', '""', $task->title) . '"',
                $task->status,
                $task->priority,
                '"' . str_replace('"', '""', $task->board->name ?? '') . '"',
                '"' . str_replace('"', '""', $task->assignee->name ?? '') . '"',
                '"' . str_replace('"', '""', $task->creator->name ?? '') . '"',
                $task->created_at->toIso8601String(),
                $task->due_date?->toIso8601String() ?? '',
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="analytics.csv"');
    }

    /**
     * Get dashboard analytics (aggregated view)
     */
    public function getDashboardAnalytics(string $period = '30days'): array
    {
        $days = match($period) {
            '7days' => 7,
            '90days' => 90,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        return [
            'overview' => $this->getOverview($startDate->toDateString(), $endDate->toDateString()),
            'trends' => $this->getTaskTrends($startDate->toDateString(), $endDate->toDateString(), 'daily'),
            'board_activity' => $this->getBoardActivity($startDate->toDateString(), $endDate->toDateString()),
            'user_activity' => $this->getUserActivity($startDate->toDateString(), $endDate->toDateString()),
            'priority_distribution' => $this->getPriorityDistribution(),
            'status_distribution' => $this->getStatusDistribution(),
        ];
    }

    /**
     * Track custom analytics event
     */
    public function track(
        string $eventType,
        int $userId,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        // For now, just log the event
        // In production, you'd store this in an analytics_events table
        Log::info('Analytics event tracked', [
            'event_type' => $eventType,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'tenant_id' => tenant('id'),
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        // TODO: Store in database
        // AnalyticsEvent::create([...]);
    }

    /**
     * Calculate productivity score
     */
    private function calculateProductivityScore(
        float $completionRate,
        int $tasksCompletedThisWeek,
        int $activeUsers
    ): int {
        // Based on completion rate, task velocity, and team activity
        $score = min(100, round(
            ($completionRate * 0.5) + // 50% weight on completion
            (min(100, $tasksCompletedThisWeek * 10) * 0.3) + // 30% on weekly completion
            (min(100, $activeUsers * 20) * 0.2) // 20% on team engagement
        ));

        return (int) $score;
    }
}