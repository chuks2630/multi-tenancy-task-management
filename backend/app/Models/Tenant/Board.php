<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'team_id',
        'created_by',
        'is_private',
        'is_active',
        'color',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Board creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Board team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Board members (users with access)
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_user')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    /**
     * Board tasks
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Tasks by status
     */
    public function tasksByStatus(string $status): HasMany
    {
        return $this->hasMany(Task::class)->where('status', $status)->orderBy('position');
    }

    /**
     * Check if user has access to board
     */
    public function hasAccess(User $user): bool
    {
        // Owners and admins have access to all boards
        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        // Creator has access
        if ($this->created_by === $user->id) {
            return true;
        }

        // If not private, all users have access
        if (!$this->is_private) {
            return true;
        }

        // Check if user is a member
        return $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Check if user can edit board
     */
    public function canEdit(User $user): bool
    {
        if ($user->isOwner() || $user->isAdmin() || $this->created_by === $user->id) {
            return true;
        }

        $member = $this->members()->where('users.id', $user->id)->first();
        return $member && in_array($member->pivot->access_level, ['edit', 'admin']);
    }

    /**
     * Check if user can delete board
     */
    public function canDelete(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin() || $this->created_by === $user->id;
    }

    /**
     * Get board statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_tasks' => $this->tasks()->count(),
            'todo' => $this->tasks()->where('status', 'todo')->count(),
            'in_progress' => $this->tasks()->where('status', 'in_progress')->count(),
            'done' => $this->tasks()->where('status', 'done')->count(),
        ];
    }
}