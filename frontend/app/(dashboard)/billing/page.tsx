'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { CreditCard, ExternalLink, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PlanCard } from '@/components/billing/plan-card';
import { UsageStatsDisplay } from '@/components/billing/usage-stats';
import { billingApi, Plan } from '@/lib/api/billing';
import { Can } from '@/components/auth/can';
import { usePermissions } from '@/lib/hooks/use-permissions';

export default function BillingPage() {
  const queryClient = useQueryClient();
  const { user, isOwner } = usePermissions();
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null);

  // Fetch plans
  const { data: plans, isLoading: plansLoading } = useQuery({
    queryKey: ['plans'],
    queryFn: () => billingApi.getPlans(),
  });

  // Fetch subscription
  const { data: subscription, isLoading: subscriptionLoading } = useQuery({
    queryKey: ['subscription'],
    queryFn: () => billingApi.getSubscription(),
  });

  // Fetch usage
  const { data: usage, isLoading: usageLoading } = useQuery({
    queryKey: ['usage'],
    queryFn: () => billingApi.getUsage(),
  });

  // Create checkout session
  const checkoutMutation = useMutation({
    mutationFn: (planId: number) => billingApi.createCheckoutSession(planId),
    onSuccess: (data) => {
      window.location.href = data.url;
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to create checkout session');
    },
  });

  // Create portal session
  const portalMutation = useMutation({
    mutationFn: () => billingApi.createPortalSession(),
    onSuccess: (data) => {
      window.open(data.url, '_blank');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to open billing portal');
    },
  });

  const handleSelectPlan = (plan: Plan) => {
    if (!isOwner()) {
      toast.error('Only the organization owner can change plans');
      return;
    }

    if (confirm(`Upgrade to ${plan.name} plan for $${plan.price}/${plan.billing_period === 'monthly' ? 'month' : 'year'}?`)) {
      checkoutMutation.mutate(plan.id);
    }
  };

  const handleManageBilling = () => {
    portalMutation.mutate();
  };

  const currentPlan = subscription?.current_plan;

  if (!isOwner()) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Billing</h1>
          <p className="text-muted-foreground mt-2">
            Manage your subscription and billing
          </p>
        </div>

        <Card>
          <CardContent className="pt-6">
            <p className="text-center text-muted-foreground">
              Only the organization owner can manage billing.
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Billing</h1>
          <p className="text-muted-foreground mt-2">
            Manage your subscription and billing
          </p>
        </div>
        {subscription?.has_active_subscription && (
          <Button
            variant="outline"
            onClick={handleManageBilling}
            disabled={portalMutation.isPending}
          >
            {portalMutation.isPending ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <CreditCard className="mr-2 h-4 w-4" />
            )}
            Manage Billing
            <ExternalLink className="ml-2 h-3 w-3" />
          </Button>
        )}
      </div>

      <Tabs defaultValue="overview" className="space-y-6">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="plans">Plans</TabsTrigger>
          <TabsTrigger value="usage">Usage</TabsTrigger>
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-6">
          {subscriptionLoading ? (
            <Skeleton className="h-48" />
          ) : (
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Current Plan</CardTitle>
                    <CardDescription>Your active subscription</CardDescription>
                  </div>
                  {subscription?.is_on_trial && (
                    <Badge variant="secondary">Trial</Badge>
                  )}
                </div>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-2xl font-bold">{currentPlan?.name}</p>
                    <p className="text-sm text-muted-foreground">
                      ${currentPlan?.price}/{currentPlan?.billing_period === 'monthly' ? 'month' : 'year'}
                    </p>
                  </div>
                  <Badge
                    variant={subscription?.status === 'active' ? 'default' : 'secondary'}
                  >
                    {subscription?.status}
                  </Badge>
                </div>

                {subscription?.trial_ends_at && (
                  <p className="text-sm text-muted-foreground">
                    Trial ends: {new Date(subscription.trial_ends_at).toLocaleDateString()}
                  </p>
                )}

                {subscription?.subscription_ends_at && !subscription?.is_on_trial && (
                  <p className="text-sm text-muted-foreground">
                    Renews: {new Date(subscription.subscription_ends_at).toLocaleDateString()}
                  </p>
                )}
              </CardContent>
            </Card>
          )}

          {/* Usage Stats */}
          {usageLoading ? (
            <Skeleton className="h-96" />
          ) : usage ? (
            <UsageStatsDisplay usage={usage} />
          ) : null}
        </TabsContent>

        {/* Plans Tab */}
        <TabsContent value="plans">
          {plansLoading ? (
            <div className="grid gap-6 md:grid-cols-3">
              {[...Array(3)].map((_, i) => (
                <Skeleton key={i} className="h-96" />
              ))}
            </div>
          ) : (
            <div className="grid gap-6 md:grid-cols-3">
              {plans?.map((plan, index) => (
                <PlanCard
                  key={plan.id}
                  plan={plan}
                  currentPlan={currentPlan}
                  onSelect={handleSelectPlan}
                  isLoading={checkoutMutation.isPending}
                  popular={index === 1} // Middle plan is popular
                />
              ))}
            </div>
          )}
        </TabsContent>

        {/* Usage Tab */}
        <TabsContent value="usage">
          {usageLoading ? (
            <Skeleton className="h-96" />
          ) : usage ? (
            <UsageStatsDisplay usage={usage} />
          ) : null}
        </TabsContent>
      </Tabs>
    </div>
  );
}