import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { StatusDistribution } from '@/lib/api/analytics';

interface StatusDistributionChartProps {
  data: StatusDistribution[];
}

const STATUS_COLORS = {
  todo: '#94a3b8',
  in_progress: '#3b82f6',
  done: '#10b981',
};

export function StatusDistributionChart({ data }: StatusDistributionChartProps) {
  const chartData = data.map((item) => ({
    name: item.status.replace('_', ' ').toUpperCase(),
    Tasks: item.count,
    fill: STATUS_COLORS[item.status as keyof typeof STATUS_COLORS] || '#6b7280',
  }));

  return (
    <Card>
      <CardHeader>
        <CardTitle>Status Distribution</CardTitle>
        <CardDescription>Tasks by current status</CardDescription>
      </CardHeader>
      <CardContent>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip />
            <Bar dataKey="Tasks" />
          </BarChart>
        </ResponsiveContainer>
      </CardContent>
    </Card>
  );
}