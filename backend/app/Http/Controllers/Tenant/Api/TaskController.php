<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTaskRequest;
use App\Http\Requests\Tenant\UpdateTaskRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\Task;
use App\Models\Tenant\Board;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;
use App\Events\Tenant\TaskCreated;

class TaskController extends Controller
{
    /**
     * Get all tasks (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $boardId = $request->input('board_id');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $assignedTo = $request->input('assigned_to');
        $search = $request->input('search');

        $query = Task::with(['board', 'assignee', 'creator']);

        // Filter by board access
        $user = $request->user();
        if (!$user->isOwner() && !$user->isAdmin()) {
            $query->whereHas('board', function ($q) use ($user) {
                $q->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('is_private', false)
                      ->orWhereHas('members', function ($q) use ($user) {
                          $q->where('users.id', $user->id);
                      });
                });
            });
        }

        if ($boardId) {
            $query->where('board_id', $boardId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('position')->paginate($perPage);

        return ApiResponse::paginated(
            $tasks->through(fn($task) => $this->formatTask($task))
        );
    }

    /**
     * Create new task
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $board = Board::findOrFail($request->board_id);

        if (!$board->hasAccess($request->user())) {
            return ApiResponse::forbidden('You do not have access to this board');
        }

        // Get max position for the status
        $maxPosition = Task::where('board_id', $request->board_id)
            ->where('status', $request->input('status', 'todo'))
            ->max('position') ?? 0;

        $task = Task::create([
            'board_id' => $request->board_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->input('status', 'todo'),
            'priority' => $request->input('priority', 'medium'),
            'assigned_to' => $request->assigned_to,
            'created_by' => $request->user()->id,
            'due_date' => $request->due_date,
            'position' => $maxPosition + 1,
        ]);

        if ($task->assigned_to) {
        $assignee = User::find($task->assigned_to);
        $assignee->notify(new TaskAssignedNotification($task));
    }

        return ApiResponse::created(
            $this->formatTask($task->load(['board', 'assignee', 'creator'])),
            'Task created successfully'
        );
    }

    /**
     * Get single task
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        if (!$task->board->hasAccess($request->user())) {
            return ApiResponse::forbidden('You do not have access to this task');
        }

        $task->load(['board', 'assignee', 'creator']);

        return ApiResponse::success(
            $this->formatTask($task, true)
        );
    }

    /**
     * Update task
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        // If status changed, update position
        if ($request->has('status') && $request->status !== $task->status) {
            $maxPosition = Task::where('board_id', $task->board_id)
                ->where('status', $request->status)
                ->max('position') ?? 0;
            
            $task->update(['position' => $maxPosition + 1]);
        }

        $task->update($request->validated());

        return ApiResponse::success(
            $this->formatTask($task->fresh(['board', 'assignee', 'creator'])),
            'Task updated successfully'
        );
    }

    /**
     * Delete task
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        if (!$task->canDelete($request->user())) {
            return ApiResponse::forbidden('You do not have permission to delete this task');
        }

        $task->delete();

        return ApiResponse::success(null, 'Task deleted successfully');
    }

    /**
     * Bulk update task positions (for drag-and-drop)
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.position' => 'required|integer|min:0',
            'tasks.*.status' => 'required|in:todo,in_progress,done',
        ]);

        foreach ($request->tasks as $taskData) {
            $task = Task::find($taskData['id']);
            
            if ($task && $task->board->hasAccess($request->user())) {
                $task->update([
                    'position' => $taskData['position'],
                    'status' => $taskData['status'],
                ]);
            }
        }

        return ApiResponse::success(null, 'Task positions updated successfully');
    }

    /**
     * Get user's assigned tasks
     */
    public function myTasks(Request $request): JsonResponse
    {
        $status = $request->input('status');

        $query = Task::with(['board', 'creator'])
            ->where('assigned_to', $request->user()->id);

        if ($status) {
            $query->where('status', $status);
        }

        $tasks = $query->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc')
            ->get();

        return ApiResponse::success([
            'tasks' => $tasks->map(fn($task) => $this->formatTask($task)),
            'summary' => [
                'total' => $tasks->count(),
                'todo' => $tasks->where('status', 'todo')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'done' => $tasks->where('status', 'done')->count(),
                'overdue' => $tasks->filter->isOverdue()->count(),
            ],
        ]);
    }

    /**
     * Format task for API response
     */
    private function formatTask(Task $task, bool $detailed = false): array
    {
        $data = [
            'id' => $task->id,
            'board_id' => $task->board_id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'position' => $task->position,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'email' => $task->assignee->email,
            ] : null,
            'creator' => [
                'id' => $task->creator->id,
                'name' => $task->creator->name,
            ],
            'due_date' => $task->due_date?->toIso8601String(),
            'is_overdue' => $task->isOverdue(),
            'created_at' => $task->created_at->toIso8601String(),
            'updated_at' => $task->updated_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['board'] = [
                'id' => $task->board->id,
                'name' => $task->board->name,
                'color' => $task->board->color,
            ];
        }

        return $data;
    }
}