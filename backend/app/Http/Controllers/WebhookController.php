<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Plan;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * Handle Stripe webhook events
     */
    public function handleStripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->stripeService->verifyWebhookSignature($payload, $signature);
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Log the event
        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Handle event
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
                'invoice.paid' => $this->handleInvoicePaid($event->data->object),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event->data->object),
                default => Log::info('Unhandled webhook event', ['type' => $event->type]),
            };

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle checkout session completed
     */
    private function handleCheckoutCompleted($session): void
    {
        $tenantId = $session->metadata->tenant_id ?? null;
        if (!$tenantId) {
            Log::warning('Checkout completed without tenant_id', ['session' => $session->id]);
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            Log::error('Tenant not found for checkout', ['tenant_id' => $tenantId]);
            return;
        }

        $tenant->update([
            'stripe_customer_id' => $session->customer,
            'stripe_subscription_id' => $session->subscription,
            'subscription_status' => 'active',
        ]);

        Log::info('Checkout completed for tenant', ['tenant_id' => $tenant->id]);
    }

    /**
     * Handle subscription created
     */
    private function handleSubscriptionCreated($subscription): void
    {
        $tenantId = $subscription->metadata->tenant_id ?? null;
        if (!$tenantId) return;

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return;

        $planId = $subscription->metadata->plan_id ?? null;

        $tenant->update([
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => $subscription->status,
            'plan_id' => $planId,
        ]);

        $tenant->subscriptionLogs()->create([
            'plan_id' => $planId ?? $tenant->plan_id,
            'event_type' => 'created',
            'metadata' => [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ],
        ]);

        Log::info('Subscription created', ['tenant_id' => $tenant->id]);
    }

    /**
     * Handle subscription updated
     */
    private function handleSubscriptionUpdated($subscription): void
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();
        if (!$tenant) return;

        $oldStatus = $tenant->subscription_status;
        $newStatus = $subscription->status;

        $tenant->update([
            'subscription_status' => $newStatus,
        ]);

        // Log if status changed
        if ($oldStatus !== $newStatus) {
            $tenant->subscriptionLogs()->create([
                'plan_id' => $tenant->plan_id,
                'event_type' => 'updated',
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ]);

            Log::info('Subscription status changed', [
                'tenant_id' => $tenant->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    /**
     * Handle subscription deleted
     */
    private function handleSubscriptionDeleted($subscription): void
    {
        $tenant = Tenant::where('stripe_subscription_id', $subscription->id)->first();
        if (!$tenant) return;

        // Downgrade to free plan
        $freePlan = Plan::where('slug', 'free')->first();

        $tenant->update([
            'plan_id' => $freePlan?->id,
            'subscription_status' => 'canceled',
            'subscription_ends_at' => now(),
        ]);

        $tenant->subscriptionLogs()->create([
            'plan_id' => $tenant->plan_id,
            'event_type' => 'canceled',
            'metadata' => [
                'subscription_id' => $subscription->id,
                'canceled_at' => now(),
            ],
        ]);

        Log::info('Subscription canceled', ['tenant_id' => $tenant->id]);
    }

    /**
     * Handle invoice paid
     */
    private function handleInvoicePaid($invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (!$tenant) return;

        // Update subscription status if it was past_due
        if ($tenant->subscription_status === 'past_due') {
            $tenant->update(['subscription_status' => 'active']);
        }

        Log::info('Invoice paid', [
            'tenant_id' => $tenant->id,
            'amount' => $invoice->amount_paid / 100,
        ]);
    }

    /**
     * Handle invoice payment failed
     */
    private function handleInvoicePaymentFailed($invoice): void
    {
        $tenant = Tenant::where('stripe_customer_id', $invoice->customer)->first();
        if (!$tenant) return;

        $tenant->update(['subscription_status' => 'past_due']);

        Log::warning('Invoice payment failed', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
        ]);

        // TODO: Send notification to tenant owner
        // event(new PaymentFailedEvent($tenant));
    }
}