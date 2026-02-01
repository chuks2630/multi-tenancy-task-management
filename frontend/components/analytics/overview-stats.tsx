import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { AnalyticsOverview } from '@/lib/api/analytics';

interface OverviewStatsProps {
  data: AnalyticsOverview;
}

export function OverviewStats({ data }: OverviewStatsProps) {
  const stats = [
    {
      title: 'Total Tasks',
      value: data.total_tasks,
      change: data.tasks_created_this_week,
      changeLabel: 'created this week',
      trend: data.tasks_created_this_week > 0 ? 'up' : 'neutral',
    },
    {
      title: 'Completed Tasks',
      value: data.completed_tasks,
      change: data.tasks_completed_this_week,
      changeLabel: 'this week',
      trend: data.tasks_completed_this_week > 0 ? 'up' : 'neutral',
    },
    {
      title: 'Completion Rate',
      value: `${data.completion_rate.toFixed(1)}%`,
      change: null,
      changeLabel: 'of all tasks',
      trend: data.completion_rate >= 70 ? 'up' : data.completion_rate >= 50 ? 'neutral' : 'down',
    },
    {
      title: 'Productivity Score',
      value: data.productivity_score,
      change: null,
      changeLabel: 'overall performance',
      trend: data.productivity_score >= 80 ? 'up' : data.productivity_score >= 60 ? 'neutral' : 'down',
    },
    {
      title: 'Active Boards',
      value: data.active_boards,
      change: null,
      changeLabel: 'boards in progress',
      trend: 'neutral',
    },
    {
      title: 'Active Users',
      value: data.active_users,
      change: null,
      changeLabel: 'team members',
      trend: 'neutral',
    },
  ];

  const getTrendIcon = (trend: string) => {
    switch (trend) {
      case 'up':
        return <TrendingUp className="h-4 w-4 text-green-600" />;
      case 'down':
        return <TrendingDown className="h-4 w-4 text-red-600" />;
      default:
        return <Minus className="h-4 w-4 text-gray-400" />;
    }
  };

  const getTrendColor = (trend: string) => {
    switch (trend) {
      case 'up':
        return 'text-green-600';
      case 'down':
        return 'text-red-600';
      default:
        return 'text-gray-600';
    }
  };

  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      {stats.map((stat) => (
        <Card key={stat.title}>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
            {getTrendIcon(stat.trend)}
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stat.value}</div>
            <p className="text-xs text-muted-foreground">
              {stat.change !== null && (
                <span className={getTrendColor(stat.trend)}>
                  {stat.change > 0 ? '+' : ''}{stat.change}{' '}
                </span>
              )}
              {stat.changeLabel}
            </p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}