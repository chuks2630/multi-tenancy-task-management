import { Check, Zap } from 'lucide-react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Plan } from '@/lib/api/billing';

interface PlanCardProps {
  plan: Plan;
  currentPlan?: Plan;
  onSelect: (plan: Plan) => void;
  isLoading?: boolean;
  popular?: boolean;
}

export function PlanCard({ plan, currentPlan, onSelect, isLoading, popular }: PlanCardProps) {
  const isCurrent = currentPlan?.id === plan.id;
  const isUpgrade = currentPlan && plan.price > currentPlan.price;
  const isDowngrade = currentPlan && plan.price < currentPlan.price;

  return (
    <Card className={`relative ${popular ? 'border-primary shadow-lg' : ''}`}>
      {popular && (
        <div className="absolute -top-4 left-0 right-0 flex justify-center">
          <Badge className="bg-primary">
            <Zap className="h-3 w-3 mr-1" />
            Most Popular
          </Badge>
        </div>
      )}

      <CardHeader className={popular ? 'pt-6' : ''}>
        <CardTitle className="flex items-center justify-between">
          {plan.name}
          {isCurrent && (
            <Badge variant="secondary">Current Plan</Badge>
          )}
        </CardTitle>
        <CardDescription>{plan.description}</CardDescription>
      </CardHeader>

      <CardContent className="space-y-6">
        {/* Price */}
        <div>
          <div className="flex items-baseline gap-1">
            <span className="text-4xl font-bold">${plan.price}</span>
            <span className="text-muted-foreground">/{plan.billing_period === 'monthly' ? 'mo' : 'yr'}</span>
          </div>
          {plan.trial_days > 0 && (
            <p className="text-sm text-muted-foreground mt-1">
              {plan.trial_days}-day free trial
            </p>
          )}
        </div>

        {/* Features */}
        <ul className="space-y-2">
          <li className="flex items-start gap-2">
            <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
            <span className="text-sm">
              {plan.features.max_teams === -1 ? 'Unlimited' : plan.features.max_teams} teams
            </span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
            <span className="text-sm">
              {plan.features.max_boards === -1 ? 'Unlimited' : plan.features.max_boards} boards
            </span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
            <span className="text-sm">
              {plan.features.max_tasks === -1 ? 'Unlimited' : plan.features.max_tasks} tasks
            </span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
            <span className="text-sm">
              {plan.features.max_users === -1 ? 'Unlimited' : plan.features.max_users} team members
            </span>
          </li>
          {plan.features.analytics && (
            <li className="flex items-start gap-2">
              <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
              <span className="text-sm">Advanced analytics</span>
            </li>
          )}
          {plan.features.priority_support && (
            <li className="flex items-start gap-2">
              <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
              <span className="text-sm">Priority support</span>
            </li>
          )}
          {plan.features.custom_branding && (
            <li className="flex items-start gap-2">
              <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
              <span className="text-sm">Custom branding</span>
            </li>
          )}
        </ul>
      </CardContent>

      <CardFooter>
        <Button
          className="w-full"
          variant={isCurrent ? 'outline' : popular ? 'default' : 'outline'}
          onClick={() => onSelect(plan)}
          disabled={isLoading || isCurrent}
        >
          {isCurrent
            ? 'Current Plan'
            : isUpgrade
            ? 'Upgrade'
            : isDowngrade
            ? 'Downgrade'
            : 'Select Plan'}
        </Button>
      </CardFooter>
    </Card>
  );
}