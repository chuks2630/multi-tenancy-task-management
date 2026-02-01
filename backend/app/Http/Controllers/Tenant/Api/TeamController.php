<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTeamRequest;
use App\Http\Requests\Tenant\UpdateTeamRequest;
use App\Http\Requests\Tenant\ManageTeamMemberRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\Team;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Get all teams
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        $query = Team::with(['creator', 'members'])
            ->withCount('members');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by user's teams if not admin/owner
        if (!$request->user()->isAdmin() && !$request->user()->isOwner()) {
            $query->whereHas('members', function ($q) use ($request) {
                $q->where('users.id', $request->user()->id);
            });
        }

        $teams = $query->latest()->paginate($perPage);

        return ApiResponse::paginated(
            $teams->through(fn($team) => $this->formatTeam($team))
        );
    }

    /**
     * Create new team
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        $team = Team::create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        // Add creator as team leader
        $team->addMember($request->user(), 'leader');

        return ApiResponse::created(
            $this->formatTeam($team->load(['creator', 'members'])),
            'Team created successfully'
        );
    }

    /**
     * Get single team
     */
    public function show(Team $team): JsonResponse
    {
        $team->load(['creator', 'members', 'pendingInvitations']);

        return ApiResponse::success(
            $this->formatTeam($team, true)
        );
    }

    /**
     * Update team
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        $team->update($request->validated());

        return ApiResponse::success(
            $this->formatTeam($team->fresh(['creator', 'members'])),
            'Team updated successfully'
        );
    }

    /**
     * Delete team
     */
    public function destroy(Request $request, Team $team): JsonResponse
    {
        // Only owner, admin, or team creator can delete
        if (!$request->user()->isOwner() 
            && !$request->user()->isAdmin() 
            && $team->created_by !== $request->user()->id) {
            return ApiResponse::forbidden('You do not have permission to delete this team');
        }

        $team->delete();

        return ApiResponse::success(null, 'Team deleted successfully');
    }

    /**
     * Get team members
     */
    public function members(Team $team): JsonResponse
    {
        $members = $team->members()
            ->withPivot('role', 'joined_at')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'joined_at' => $user->pivot->joined_at,
                ];
            });

        return ApiResponse::success(['members' => $members]);
    }

    /**
     * Add member to team
     */
    public function addMember(ManageTeamMemberRequest $request, Team $team): JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if ($team->hasMember($user)) {
            return ApiResponse::error('User is already a member of this team', null, 400);
        }

        $team->addMember($user, $request->input('role', 'member'));

        return ApiResponse::success(
            null,
            'Member added to team successfully'
        );
    }

    /**
     * Remove member from team
     */
    public function removeMember(ManageTeamMemberRequest $request, Team $team): JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if (!$team->hasMember($user)) {
            return ApiResponse::error('User is not a member of this team', null, 400);
        }

        // Prevent removing the creator
        if ($team->created_by === $user->id) {
            return ApiResponse::error('Cannot remove team creator', null, 400);
        }

        $team->removeMember($user);

        return ApiResponse::success(
            null,
            'Member removed from team successfully'
        );
    }

    /**
     * Update member role
     */
    public function updateMemberRole(ManageTeamMemberRequest $request, Team $team): JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if (!$team->hasMember($user)) {
            return ApiResponse::error('User is not a member of this team', null, 400);
        }

        $team->updateMemberRole($user, $request->role);

        return ApiResponse::success(
            null,
            'Member role updated successfully'
        );
    }

    /**
     * Format team for API response
     */
    private function formatTeam(Team $team, bool $detailed = false): array
    {
        $data = [
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'is_active' => $team->is_active,
            'members_count' => $team->members_count ?? $team->members->count(),
            'creator' => [
                'id' => $team->creator->id,
                'name' => $team->creator->name,
                'email' => $team->creator->email,
            ],
            'created_at' => $team->created_at->toIso8601String(),
            'updated_at' => $team->updated_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['members'] = $team->members->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'joined_at' => $user->pivot->joined_at->toIso8601String(),
                ];
            });

            $data['pending_invitations'] = $team->pendingInvitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'invited_by' => $invitation->inviter->name,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ];
            });
        }

        return $data;
    }
}