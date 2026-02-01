<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * Get current subscription details
     */
    public function show(Tenant $tenant)
    {
        $tenant->load('plan', 'subscriptionLogs');
        
        return response()->json([
            'tenant' => $tenant,
            'subscription' => [
                'plan' => $tenant->plan,
                'status' => $tenant->subscription_status,
                'trial_ends_at' => $tenant->trial_ends_at,
                'subscription_ends_at' => $tenant->subscription_ends_at,
                'is_on_trial' => $tenant->isOnTrial(),
                'is_active' => $tenant->subscriptionIsActive(),
            ],
        ]);
    }

    /**
     * Create Stripe checkout session for subscription
     */
    public function createCheckoutSession(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        if ($plan->isFree()) {
            return response()->json([
                'message' => 'Free plan does not require checkout',
            ], 422);
        }

        try {
            $session = $this->stripeService->createCheckoutSession($tenant, $plan);

            return response()->json([
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create Stripe portal session (for managing subscription)
     */
    public function createPortalSession(Tenant $tenant)
    {
        if (!$tenant->stripe_customer_id) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 422);
        }

        try {
            $session = $this->stripeService->createPortalSession($tenant);

            return response()->json([
                'portal_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create portal session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upgrade/Downgrade subscription
     */
    public function changePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($validated['plan_id']);
        $oldPlan = $tenant->plan;

        try {
            DB::beginTransaction();

            // Update subscription in Stripe if paid plan
            if (!$newPlan->isFree() && $tenant->stripe_subscription_id) {
                $this->stripeService->updateSubscription($tenant, $newPlan);
            }

            // Update tenant plan
            $tenant->update(['plan_id' => $newPlan->id]);

            // Log the change
            $tenant->subscriptionLogs()->create([
                'plan_id' => $newPlan->id,
                'event_type' => $this->determineEventType($oldPlan, $newPlan),
                'metadata' => [
                    'old_plan' => $oldPlan->name,
                    'new_plan' => $newPlan->name,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Plan changed successfully',
                'tenant' => $tenant->fresh('plan'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to change plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Tenant $tenant)
    {
        if (!$tenant->stripe_subscription_id) {
            return response()->json([
                'message' => 'No active subscription to cancel',
            ], 422);
        }

        try {
            $this->stripeService->cancelSubscription($tenant);

            $tenant->update([
                'subscription_status' => 'canceled',
            ]);

            $tenant->subscriptionLogs()->create([
                'plan_id' => $tenant->plan_id,
                'event_type' => 'canceled',
                'metadata' => ['canceled_at' => now()],
            ]);

            return response()->json([
                'message' => 'Subscription canceled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function determineEventType(?Plan $oldPlan, Plan $newPlan): string
    {
        if (!$oldPlan) {
            return 'created';
        }

        if ($newPlan->price > $oldPlan->price) {
            return 'upgraded';
        }

        if ($newPlan->price < $oldPlan->price) {
            return 'downgraded';
        }

        return 'changed';
    }
}