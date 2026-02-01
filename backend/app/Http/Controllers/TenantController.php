<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function __construct(
        private TenantProvisioningService $provisioningService
    ) {}

    /**
     * Create new tenant/organization
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Generate subdomain if not provided
        $subdomain = $validated['subdomain'] 
            ?? Str::slug($validated['name'] ?? $validated['organization_name']);
        
        // Ensure subdomain uniqueness
        $subdomain = $this->ensureUniqueSubdomain($subdomain);

        try {
            // Create and provision tenant
            $tenant = $this->provisioningService->provision([
                'name' => $validated['name'] ?? $validated['organization_name'],
                'subdomain' => $subdomain,
                'plan_id' => $validated['plan_id'] ?? Plan::where('slug', 'free')->first()->id,
                'owner' => [
                    'name' => $validated['owner_name'],
                    'email' => $validated['owner_email'],
                    'password' => $validated['owner_password'],
                ],
            ]);

            // Build tenant URL for frontend redirect
            $tenantUrl = $this->buildTenantUrl($subdomain);

            return ApiResponse::created([
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'subdomain' => $subdomain,
                ],
                'redirect_url' => $tenantUrl . '/login',
            ], 'Organization created successfully');

        } catch (\Exception $e) {
            Log::error('Tenant creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['owner_password']), // Don't log password
            ]);

            return ApiResponse::error(
                'Failed to create organization: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Check if subdomain is available
     */
    public function checkSubdomain(string $subdomain): JsonResponse
    {
        // Validate subdomain format
        if (!preg_match('/^[a-z0-9-]+$/', $subdomain)) {
            return response()->json([
                'available' => false
            ], 400);
        }

        // Check if subdomain exists
        $exists = Tenant::where('id', $subdomain)->exists();
        
        // Check reserved subdomains
        $reserved = ['www', 'app', 'api', 'admin', 'mail', 'ftp', 'localhost', 'dashboard', 'help', 'support', 'blog'];
        $isReserved = in_array($subdomain, $reserved);

        return response()->json([
            'available' => !$exists && !$isReserved
        ]);
    }

    /**
     * Get tenant subscription details
     */
    public function subscription(Tenant $tenant): JsonResponse
    {
        return ApiResponse::success([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'plan' => [
                'id' => $tenant->plan->id,
                'name' => $tenant->plan->name,
                'slug' => $tenant->plan->slug,
                'price' => $tenant->plan->price,
                'billing_period' => $tenant->plan->billing_period,
                'features' => $tenant->plan->features,
            ],
            'subscription' => [
                'status' => $tenant->subscription_status,
                'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
                'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
                'is_on_trial' => $tenant->isOnTrial(),
                'has_active_subscription' => $tenant->hasActiveSubscription(),
            ],
        ]);
    }

    /**
     * Update tenant details
     */
    public function update(StoreTenantRequest $request, Tenant $tenant): JsonResponse
    {
        try {
            $tenant->update($request->validated());

            return ApiResponse::success([
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                ],
            ], 'Organization updated successfully');

        } catch (\Exception $e) {
            Log::error('Tenant update failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(
                'Failed to update organization',
                null,
                500
            );
        }
    }

    /**
     * Delete tenant
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        try {
            $this->provisioningService->deprovision($tenant);

            return ApiResponse::success(
                null,
                'Organization deleted successfully'
            );

        } catch (\Exception $e) {
            Log::error('Tenant deletion failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error(
                'Failed to delete organization',
                null,
                500
            );
        }
    }

    /**
     * Ensure subdomain is unique by appending numbers if needed
     */
    private function ensureUniqueSubdomain(string $subdomain): string
    {
        $originalSubdomain = $subdomain;
        $counter = 1;

        while (Tenant::where('id', $subdomain)->exists()) {
            $subdomain = $originalSubdomain . '-' . $counter;
            $counter++;
        }

        return $subdomain;
    }

    /**
     * Build tenant URL
     */
    private function buildTenantUrl(string $subdomain): string
    {
        $domain = config('app.domain', 'localhost');
        $protocol = config('app.env') === 'local' ? 'http' : 'https';
        
        // Handle local development with port
        if (config('app.env') === 'local' && str_contains($domain, ':')) {
            return "{$protocol}://{$subdomain}.{$domain}";
        }
        
        // Production
        return "{$protocol}://{$subdomain}.{$domain}";
    }
}