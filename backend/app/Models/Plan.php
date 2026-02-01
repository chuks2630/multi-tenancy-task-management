<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
     protected $connection = 'central';
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'price',
        'billing_period',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function getFeatureLimit(string $feature): ?int
    {
        return $this->features[$feature] ?? null;
    }

    public function hasFeature(string $feature): bool
    {
        return isset($this->features[$feature]);
    }

    public function isFree(): bool
    {
        return $this->slug === 'free';
    }

    public function isPro(): bool
    {
        return $this->slug === 'pro';
    }
}