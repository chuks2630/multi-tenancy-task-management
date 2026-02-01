<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $with = ['creator'];

    /**
     * Team creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Team members
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Team leaders
     */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->wherePivot('role', 'leader')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Team invitations
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Pending invitations
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class)
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Check if user is a member
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if user is a leader
     */
    public function hasLeader(User $user): bool
    {
        return $this->members()
            ->wherePivot('role', 'leader')
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * Add member to team
     */
    public function addMember(User $user, string $role = 'member'): void
    {
        if (!$this->hasMember($user)) {
            $this->members()->attach($user->id, [
                'role' => $role,
                'joined_at' => now(),
            ]);
        }
    }

    /**
     * Remove member from team
     */
    public function removeMember(User $user): void
    {
        $this->members()->detach($user->id);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(User $user, string $role): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);
    }
}