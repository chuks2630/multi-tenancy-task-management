<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; 

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;


     protected $guard_name = 'web';
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships will be with tenant-specific models
    public function boards()
    {
        return $this->belongsToMany(Board::class, 'board_user');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    // public function teams()
    // {
    //     return $this->belongsToMany(Team::class, 'team_user');
    // }

    // Role methods (if not using Spatie)
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer' || $this->hasRole('viewer');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function createdTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function receivedInvitations()
    {
        return Invitation::where('email', $this->email);
    }

     public function canManageTeams(): bool
    {
        return $this->hasPermissionTo('manage teams');
    }

    public function canManageBoards(): bool
    {
        return $this->hasPermissionTo('manage boards');
    }

    public function canManageTasks(): bool
    {
        return $this->hasPermissionTo('manage tasks');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

}