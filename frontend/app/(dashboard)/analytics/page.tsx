'use client';

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Download, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { PermissionGuard } from '@/components/auth/permission-guard';
import { DateRangePicker } from '@/components/analytics/date-range-picker';
import { OverviewStats } from '@/components/analytics/overview-stats';
import { TaskTrendsChart } from '@/components/analytics/task-trends-chart';
import { BoardActivityTable } from '@/components/analytics/board-activity-table';
import { UserActivityTable } from '@/components/analytics/user-activity-table';
import { PriorityDistributionChart } from '@/components/analytics/priority-distribution-chart';
import { StatusDistributionChart } from '@/components/analytics/status-distribution-chart';
import { analyticsApi } from '@/lib/api/analytics';
import { format, subDays } from 'date-fns';

function AnalyticsContent() {
  const [dateRange, setDateRange] = useState({
    start_date: format(subDays(new Date(), 30), 'yyyy-MM-dd'),
    end_date: format(new Date(), 'yyyy-MM-dd'),
  });
  const [isExporting, setIsExporting] = useState(false);

  // Fetch overview
  const { data: overview, isLoading: overviewLoading } = useQuery({
    queryKey: ['analytics', 'overview', dateRange],
    queryFn: () => analyticsApi.getOverview(dateRange),
  });

  // Fetch task trends
  const { data: taskTrends, isLoading: trendsLoading } = useQuery({
    queryKey: ['analytics', 'task-trends', dateRange],
    queryFn: () => analyticsApi.getTaskTrends({ ...dateRange, interval: 'daily' }),
  });

  // Fetch board activity
  const { data: boardActivity, isLoading: boardsLoading } = useQuery({
    queryKey: ['analytics', 'board-activity', dateRange],
    queryFn: () => analyticsApi.getBoardActivity(dateRange),
  });

  // Fetch user activity
  const { data: userActivity, isLoading: usersLoading } = useQuery({
    queryKey: ['analytics', 'user-activity', dateRange],
    queryFn: () => analyticsApi.getUserActivity(dateRange),
  });

  // Fetch priority distribution
  const { data: priorityDistribution, isLoading: priorityLoading } = useQuery({
    queryKey: ['analytics', 'priority-distribution'],
    queryFn: () => analyticsApi.getPriorityDistribution(),
  });

  // Fetch status distribution
  const { data: statusDistribution, isLoading: statusLoading } = useQuery({
    queryKey: ['analytics', 'status-distribution'],
    queryFn: () => analyticsApi.getStatusDistribution(),
  });

  const handleDateRangeChange = (startDate: string, endDate: string) => {
    setDateRange({
      start_date: startDate,
      end_date: endDate,
    });
  };

  const handleExport = async (format: 'csv' | 'json') => {
    try {
      setIsExporting(true);
      const blob = await analyticsApi.exportData({
        ...dateRange,
        format,
      });

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `analytics-${format}-${dateRange.start_date}-to-${dateRange.end_date}.${format}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      toast.success(`Analytics exported as ${format.toUpperCase()}`);
    } catch (error: any) {
      toast.error(error.message || 'Failed to export analytics');
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Analytics</h1>
          <p className="text-muted-foreground mt-2">
            Track your team's performance and productivity
          </p>
        </div>
        <div className="flex items-center gap-2">
          <DateRangePicker onRangeChange={handleDateRangeChange} />
          <Button
            variant="outline"
            onClick={() => handleExport('csv')}
            disabled={isExporting}
          >
            {isExporting ? (
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
              <Download className="mr-2 h-4 w-4" />
            )}
            Export CSV
          </Button>
        </div>
      </div>

      {/* Overview Stats */}
      {overviewLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[...Array(6)].map((_, i) => (
            <Skeleton key={i} className="h-32" />
          ))}
        </div>
      ) : overview ? (
        <OverviewStats data={overview} />
      ) : null}

      {/* Task Trends Chart */}
      {trendsLoading ? (
        <Skeleton className="h-96" />
      ) : taskTrends && taskTrends.length > 0 ? (
        <TaskTrendsChart data={taskTrends} />
      ) : (
        <div className="text-center py-12 border-2 border-dashed rounded-lg">
          <p className="text-muted-foreground">No task trend data available</p>
        </div>
      )}

      {/* Distribution Charts */}
      <div className="grid gap-6 md:grid-cols-2">
        {statusLoading ? (
          <Skeleton className="h-96" />
        ) : statusDistribution && statusDistribution.length > 0 ? (
          <StatusDistributionChart data={statusDistribution} />
        ) : null}

        {priorityLoading ? (
          <Skeleton className="h-96" />
        ) : priorityDistribution && priorityDistribution.length > 0 ? (
          <PriorityDistributionChart data={priorityDistribution} />
        ) : null}
      </div>

      {/* Board Activity Table */}
      {boardsLoading ? (
        <Skeleton className="h-96" />
      ) : boardActivity ? (
        <BoardActivityTable data={boardActivity} />
      ) : null}

      {/* User Activity Table */}
      {usersLoading ? (
        <Skeleton className="h-96" />
      ) : userActivity ? (
        <UserActivityTable data={userActivity} />
      ) : null}
    </div>
  );
}

export default function AnalyticsPage() {
  return (
    <PermissionGuard permission="view analytics">
      <AnalyticsContent />
    </PermissionGuard>
  );
}