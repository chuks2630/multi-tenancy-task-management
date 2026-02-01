import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { UsageStats } from '@/lib/api/billing';

interface UsageStatsProps {
  usage: UsageStats;
}

export function UsageStatsDisplay({ usage }: UsageStatsProps) {
  const stats = [
    {
      label: 'Teams',
      current: usage.teams.current,
      limit: usage.teams.limit,
      color: 'bg-blue-500',
    },
    {
      label: 'Boards',
      current: usage.boards.current,
      limit: usage.boards.limit,
      color: 'bg-green-500',
    },
    {
      label: 'Tasks',
      current: usage.tasks.current,
      limit: usage.tasks.limit,
      color: 'bg-purple-500',
    },
    {
      label: 'Team Members',
      current: usage.users.current,
      limit: usage.users.limit,
      color: 'bg-orange-500',
    },
  ];

  const hasReachedLimit = stats.some((stat) => stat.current >= stat.limit && stat.limit !== -1);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Usage</CardTitle>
        <CardDescription>Current usage across your organization</CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {hasReachedLimit && (
          <Alert>
            <AlertCircle className="h-4 w-4" />
            <AlertDescription>
              You've reached the limit on some features. Upgrade your plan to continue.
            </AlertDescription>
          </Alert>
        )}

        {stats.map((stat) => {
          const percentage = stat.limit === -1 ? 0 : (stat.current / stat.limit) * 100;
          const isAtLimit = stat.current >= stat.limit && stat.limit !== -1;

          return (
            <div key={stat.label} className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="font-medium">{stat.label}</span>
                <span className={isAtLimit ? 'text-red-600 font-medium' : 'text-muted-foreground'}>
                  {stat.current} / {stat.limit === -1 ? '∞' : stat.limit}
                </span>
              </div>
              <Progress
                value={percentage}
                className="h-2"
                indicatorClassName={isAtLimit ? 'bg-red-500' : stat.color}
              />
            </div>
          );
        })}
      </CardContent>
    </Card>
  );
}