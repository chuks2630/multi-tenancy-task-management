<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Plan;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Subscription;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create or get Stripe customer
     */
    public function getOrCreateCustomer(Tenant $tenant): string
    {
        if ($tenant->stripe_customer_id) {
            return $tenant->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'name' => $tenant->name,
            'email' => $this->getTenantOwnerEmail($tenant),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ],
        ]);

        $tenant->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Create checkout session
     */
   public function createCheckoutSession(
    Tenant $tenant, 
    Plan $plan, 
    string $successUrl, 
    string $cancelUrl
): Session {
    // Get or create Stripe customer
    $customerId = $this->getOrCreateCustomer($tenant);

    if (!$plan->stripe_price_id) {
        throw new \Exception("Plan '{$plan->name}' does not have a Stripe price ID configured.");
    }

    try {
        $session = $this->stripe->checkout->sessions->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $plan->stripe_price_id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                ],
                // 'trial_period_days' => $plan->trial_days > 0 ? $plan->trial_days : null,
            ],
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'required',
        ]);

        return $session;

    } catch (\Stripe\Exception\ApiErrorException $e) {
        \Log::error('Stripe API error creating checkout session', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'error' => $e->getMessage(),
        ]);
        
        throw new \Exception('Stripe checkout error: ' . $e->getMessage());
    }
}

    /**
     * Create billing portal session
     */
    public function createPortalSession(Tenant $tenant): PortalSession
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => config('app.url') . '/dashboard',
        ]);
    }

    /**
     * Update subscription
     */
    public function updateSubscription(Tenant $tenant, Plan $newPlan): Subscription
    {
        $subscription = $this->stripe->subscriptions->retrieve($tenant->stripe_subscription_id);

        return $this->stripe->subscriptions->update($tenant->stripe_subscription_id, [
            'items' => [[
                'id' => $subscription->items->data[0]->id,
                'price' => $newPlan->stripe_price_id,
            ]],
            'proration_behavior' => 'always_invoice',
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $newPlan->id,
            ],
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Tenant $tenant): Subscription
    {
        return $this->stripe->subscriptions->cancel($tenant->stripe_subscription_id, [
            'prorate' => true,
        ]);
    }

    /**
     * Get subscription
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        return $this->stripe->subscriptions->retrieve($subscriptionId);
    }

    /**
     * Get upcoming invoice
     */
    public function getUpcomingInvoice(Tenant $tenant)
    {
        try {
            return $this->stripe->invoices->upcoming([
                'customer' => $tenant->stripe_customer_id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not retrieve upcoming invoice', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check feature usage against limits
     */
    public function checkFeatureLimit(Tenant $tenant, string $feature): bool
    {
        $plan = $tenant->plan;
        if (!$plan) {
            return false;
        }

        $limit = $plan->getFeatureLimit($feature);
        
        // Null means unlimited
        if ($limit === null) {
            return true;
        }

        // Get current usage (implement based on your needs)
        $usage = $this->getCurrentUsage($tenant, $feature);

        return $usage < $limit;
    }

    /**
     * Get current usage for a feature
     */
    public function getCurrentUsage(Tenant $tenant, string $feature): int
    {
        return $tenant->run(function () use ($feature) {
            return match($feature) {
                'max_teams' => \App\Models\Tenant\Team::count(),
                'max_users' => \App\Models\Tenant\User::count(),
                'max_boards' => \App\Models\Tenant\Board::count(),
                'max_tasks' => \App\Models\Tenant\Task::count(),
                default => 0,
            };
        });
    }

    public function getUsage(Tenant $tenant): array
{
    return $tenant->run(function () {
        return [
            'teams' => \App\Models\Tenant\Team::count(),
            'users' => \App\Models\Tenant\User::count(),
            'boards' => \App\Models\Tenant\Board::count(),
            'tasks' => \App\Models\Tenant\Task::count(),
        ];
    });
}

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );
    }

    /**
     * Get tenant owner email
     */
    private function getTenantOwnerEmail(Tenant $tenant): string
    {
        return $tenant->run(function () {
            $owner = \App\Models\Tenant\User::where('role', 'owner')->first();
            return $owner->email ?? 'noreply@' . config('app.domain');
        });
    }
}