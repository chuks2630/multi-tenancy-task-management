<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

     protected $connection = 'central';
    protected $fillable = [
        'id',
        'name',
        'plan_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan_id',
            'stripe_customer_id',
            'stripe_subscription_id',
            'subscription_status',
            'trial_ends_at',
            'subscription_ends_at',
            'settings',
        ];
    }

    // Relationships
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptionLogs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class);
    }

    // Helper methods
    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active';
    }

    public function subscriptionIsActive(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing']);
    }

    public function canAccessFeature(string $feature): bool
    {
        if (!$this->plan) {
            return false;
        }

        return $this->plan->hasFeature($feature);
    }

    public function getFeatureLimit(string $feature): ?int
    {
        return $this->plan?->getFeatureLimit($feature);
    }

    public function isSubscriptionPastDue(): bool
    {
        return $this->subscription_status === 'past_due';
    }

    public function isSubscriptionCanceled(): bool
    {
        return $this->subscription_status === 'canceled';
    }

    public function subdomain(): string
    {
        return $this->domains->first()?->domain ?? '';
    }
}