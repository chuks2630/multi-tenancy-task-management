<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreBoardRequest;
use App\Http\Requests\Tenant\UpdateBoardRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\Board;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    /**
     * Get all boards
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $teamId = $request->input('team_id');

        $query = Board::with(['creator', 'team'])
            ->withCount('tasks');

        // Filter boards user has access to
        $user = $request->user();
        if (!$user->isOwner() && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('is_private', false)
                  ->orWhereHas('members', function ($q) use ($user) {
                      $q->where('users.id', $user->id);
                  });
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $boards = $query->latest()->paginate($perPage);

        return ApiResponse::paginated(
            $boards->through(fn($board) => $this->formatBoard($board))
        );
    }

    /**
     * Create new board
     */
    public function store(StoreBoardRequest $request): JsonResponse
    {
        $board = Board::create([
            'name' => $request->name,
            'description' => $request->description,
            'team_id' => $request->team_id,
            'created_by' => $request->user()->id,
            'is_private' => $request->input('is_private', false),
            'color' => $request->input('color', '#3B82F6'),
        ]);

        // Add creator as admin member
        $board->members()->attach($request->user()->id, [
            'access_level' => 'admin',
        ]);

        return ApiResponse::created(
            $this->formatBoard($board->load(['creator', 'team'])),
            'Board created successfully'
        );
    }

    /**
     * Get single board with tasks
     */
    public function show(Request $request, Board $board): JsonResponse
    {
        if (!$board->hasAccess($request->user())) {
            return ApiResponse::forbidden('You do not have access to this board');
        }

        $board->load(['creator', 'team', 'members', 'tasks.assignee', 'tasks.creator']);

        return ApiResponse::success(
            $this->formatBoard($board, true)
        );
    }

    /**
     * Update board
     */
    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $board->update($request->validated());

        return ApiResponse::success(
            $this->formatBoard($board->fresh(['creator', 'team'])),
            'Board updated successfully'
        );
    }

    /**
     * Delete board
     */
    public function destroy(Request $request, Board $board): JsonResponse
    {
        if (!$board->canDelete($request->user())) {
            return ApiResponse::forbidden('You do not have permission to delete this board');
        }

        $board->delete();

        return ApiResponse::success(null, 'Board deleted successfully');
    }

    /**
     * Get board statistics
     */
    public function statistics(Request $request, Board $board): JsonResponse
    {
        if (!$board->hasAccess($request->user())) {
            return ApiResponse::forbidden('You do not have access to this board');
        }

        $stats = $board->getStatistics();

        return ApiResponse::success([
            'statistics' => $stats,
        ]);
    }

    /**
     * Add member to board
     */
    public function addMember(Request $request, Board $board): JsonResponse
    {
        if (!$board->canEdit($request->user())) {
            return ApiResponse::forbidden('You do not have permission to manage board members');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:view,edit,admin',
        ]);

        if ($board->members()->where('users.id', $request->user_id)->exists()) {
            return ApiResponse::error('User is already a member of this board', null, 400);
        }

        $board->members()->attach($request->user_id, [
            'access_level' => $request->access_level,
        ]);

        return ApiResponse::success(null, 'Member added successfully');
    }

    /**
     * Remove member from board
     */
    public function removeMember(Request $request, Board $board): JsonResponse
    {
        if (!$board->canEdit($request->user())) {
            return ApiResponse::forbidden('You do not have permission to manage board members');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Prevent removing the creator
        if ($board->created_by === $request->user_id) {
            return ApiResponse::error('Cannot remove board creator', null, 400);
        }

        $board->members()->detach($request->user_id);

        return ApiResponse::success(null, 'Member removed successfully');
    }

    /**
     * Format board for API response
     */
    private function formatBoard(Board $board, bool $detailed = false): array
    {
        $data = [
            'id' => $board->id,
            'name' => $board->name,
            'description' => $board->description,
            'color' => $board->color,
            'is_private' => $board->is_private,
            'is_active' => $board->is_active,
            'tasks_count' => $board->tasks_count ?? $board->tasks->count(),
            'creator' => [
                'id' => $board->creator->id,
                'name' => $board->creator->name,
            ],
            'team' => $board->team ? [
                'id' => $board->team->id,
                'name' => $board->team->name,
            ] : null,
            'created_at' => $board->created_at->toIso8601String(),
            'updated_at' => $board->updated_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['statistics'] = $board->getStatistics();
            
            $data['members'] = $board->members->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_level' => $user->pivot->access_level,
                ];
            });

            $data['tasks'] = $board->tasks->groupBy('status')->map(function ($tasks) {
                return $tasks->map(fn($task) => $this->formatTask($task));
            });
        }

        return $data;
    }

    /**
     * Format task for API response
     */
    private function formatTask($task): array
    {
        return [
            'id' => $task->id,
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
    }
}