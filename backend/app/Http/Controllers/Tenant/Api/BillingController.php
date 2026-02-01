<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * Get current subscription
     */
    public function subscription(): JsonResponse
    {
        $tenant = tenant();

        return ApiResponse::success([
            'status' => $tenant->subscription_status ?? 'inactive',
            'trial_ends_at' => $tenant->trial_ends_at?->toIso8601String(),
            'subscription_ends_at' => $tenant->subscription_ends_at?->toIso8601String(),
            'is_on_trial' => $tenant->isOnTrial(),
            'has_active_subscription' => $tenant->hasActiveSubscription(),
            'current_plan' => [
                'id' => $tenant->plan->id,
                'name' => $tenant->plan->name,
                'slug' => $tenant->plan->slug,
                'description' => $tenant->plan->description,
                'price' => $tenant->plan->price,
                'billing_period' => $tenant->plan->billing_period,
                'trial_days' => $tenant->plan->trial_days,
                'features' => $tenant->plan->features,
            ],
        ]);
    }

    /**
     * Get usage stats
     */
    public function usage(): JsonResponse
    {
        $usage = $this->stripeService->getUsage(tenant());
        $features = tenant()->plan->features;

        return ApiResponse::success([
            'teams' => [
                'current' => $usage['teams'],
                'limit' => $features['max_teams'] ?? -1,
            ],
            'boards' => [
                'current' => $usage['boards'],
                'limit' => $features['max_boards'] ?? -1,
            ],
            'tasks' => [
                'current' => $usage['tasks'],
                'limit' => $features['max_tasks'] ?? -1,
            ],
            'users' => [
                'current' => $usage['users'],
                'limit' => $features['max_users'] ?? -1,
            ],
        ]);
    }

    /**
     * Create checkout session
     */
        public function checkout(Request $request): JsonResponse
{
    // Validate against central database
    $validated = $request->validate([
        'plan_id' => [
            'required',
            'integer',
            function ($attribute, $value, $fail) {
                $exists = \App\Models\Plan::on('central')
                    ->where('id', $value)
                    ->where('is_active', true)
                    ->exists();
                
                if (!$exists) {
                    $fail('The selected plan is invalid or inactive.');
                }
            },
        ],
    ]);

    $plan = \App\Models\Plan::on('central')->findOrFail($validated['plan_id']);
    $tenant = tenant();

    try {
        $tenantDomain = $tenant->id; 
        $frontendBaseUrl = config('app.frontend_url'); 
        
        $successUrl = "http://{$tenantDomain}.{$frontendBaseUrl}/billing/success?session_id={CHECKOUT_SESSION_ID}";
        $cancelUrl = "http://{$tenantDomain}.{$frontendBaseUrl}/billing/cancel";

        $session = $this->stripeService->createCheckoutSession(
            $tenant,
            $plan,
            $successUrl,
            $cancelUrl
        );

        return ApiResponse::success([
            'checkout_url' => $session->url,
        ]);
    } catch (\Exception $e) {
        \Log::error('Stripe checkout error', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'error' => $e->getMessage(),
        ]);
        
        return ApiResponse::error('Failed to create checkout session: ' . $e->getMessage(), null, 500);
    }
}

    /**
     * Create portal session
     */
    public function portal(): JsonResponse
    {
        $tenant = tenant();

        try {
            $session = $this->stripeService->createPortalSession($tenant);

            return ApiResponse::success([
                'portal_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create portal session: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(): JsonResponse
    {
        $tenant = tenant();

        if (!$tenant->stripe_subscription_id) {
            return ApiResponse::error('No active subscription found', null, 404);
        }

        try {
            $this->stripeService->cancelSubscription($tenant);

            return ApiResponse::success(null, 'Subscription cancelled successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel subscription: ' . $e->getMessage(), null, 500);
        }
    }
}