<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\InviteUserRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Tenant\Invitation;
use App\Models\Tenant\Team;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller
{
    /**
     * Send invitation
     */
    public function store(InviteUserRequest $request): JsonResponse
    {
        $invitation = Invitation::create([
            'email' => $request->email,
            'team_id' => $request->team_id,
            'invited_by' => $request->user()->id,
            'token' => Invitation::generateToken(),
            'role' => $request->input('role', 'member'),
            'expires_at' => now()->addDays(7),
        ]);

        // TODO: Send invitation email
        // Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return ApiResponse::created(
            $this->formatInvitation($invitation->load(['team', 'inviter'])),
            'Invitation sent successfully'
        );
    }

    /**
     * Get user's invitations
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $invitations = Invitation::where('email', $user->email)
            ->with(['team', 'inviter'])
            ->latest()
            ->get();

        return ApiResponse::success([
            'invitations' => $invitations->map(fn($inv) => $this->formatInvitation($inv)),
        ]);
    }

    /**
     * Get pending invitations (admin/owner only)
     */
    public function pending(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && !$request->user()->isOwner()) {
            return ApiResponse::forbidden('You do not have permission to view all invitations');
        }

        $invitations = Invitation::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['team', 'inviter'])
            ->latest()
            ->get();

        return ApiResponse::success([
            'invitations' => $invitations->map(fn($inv) => $this->formatInvitation($inv)),
        ]);
    }

    /**
     * Accept invitation
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return ApiResponse::error('Invitation is no longer valid', null, 400);
        }

        if ($invitation->email !== $request->user()->email) {
            return ApiResponse::forbidden('This invitation is not for you');
        }

        $invitation->accept($request->user());

        return ApiResponse::success(
            null,
            'Invitation accepted successfully'
        );
    }

    /**
     * Reject invitation
     */
    public function reject(Request $request, string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->email !== $request->user()->email) {
            return ApiResponse::forbidden('This invitation is not for you');
        }

        $invitation->reject();

        return ApiResponse::success(
            null,
            'Invitation rejected'
        );
    }

    /**
     * Cancel invitation (by inviter or admin)
     */
    public function cancel(Request $request, Invitation $invitation): JsonResponse
    {
        if ($invitation->invited_by !== $request->user()->id 
            && !$request->user()->isAdmin() 
            && !$request->user()->isOwner()) {
            return ApiResponse::forbidden('You do not have permission to cancel this invitation');
        }

        $invitation->delete();

        return ApiResponse::success(
            null,
            'Invitation cancelled'
        );
    }

    /**
     * Format invitation for API response
     */
    private function formatInvitation(Invitation $invitation): array
    {
        return [
            'id' => $invitation->id,
            'email' => $invitation->email,
            'token' => $invitation->token,
            'status' => $invitation->status,
            'role' => $invitation->role,
            'team' => $invitation->team ? [
                'id' => $invitation->team->id,
                'name' => $invitation->team->name,
            ] : null,
            'invited_by' => [
                'id' => $invitation->inviter->id,
                'name' => $invitation->inviter->name,
            ],
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'created_at' => $invitation->created_at->toIso8601String(),
        ];
    }
}