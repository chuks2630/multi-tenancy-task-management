<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'board_id',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'due_date',
        'position',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    /**
     * Board
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Task creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Primary assignee
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Multiple assignees (if using task_assignments table)
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    /**
     * Check if user can edit task
     */
    public function canEdit(User $user): bool
    {
        // Owners and admins can edit all tasks
        if ($user->isOwner() || $user->isAdmin()) {
            return true;
        }

        // Creator can edit
        if ($this->created_by === $user->id) {
            return true;
        }

        // Assigned user can edit
        if ($this->assigned_to === $user->id) {
            return true;
        }

        // Check board access
        return $this->board->canEdit($user);
    }

    /**
     * Check if user can delete task
     */
    public function canDelete(User $user): bool
    {
        return $user->isOwner() 
            || $user->isAdmin() 
            || $this->created_by === $user->id
            || $this->board->canDelete($user);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'done');
    }
}