import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import { BoardActivity } from '@/lib/api/analytics';

interface BoardActivityTableProps {
  data: BoardActivity[];
}

export function BoardActivityTable({ data }: BoardActivityTableProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Board Performance</CardTitle>
        <CardDescription>Activity and completion rates by board</CardDescription>
      </CardHeader>
      <CardContent>
        {data.length === 0 ? (
          <div className="text-center py-8 text-muted-foreground">
            <p>No board activity to display</p>
          </div>
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Board Name</TableHead>
                <TableHead className="text-right">Total Tasks</TableHead>
                <TableHead className="text-right">Completed</TableHead>
                <TableHead>Progress</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.map((board) => (
                <TableRow key={board.board_id}>
                  <TableCell className="font-medium">{board.board_name}</TableCell>
                  <TableCell className="text-right">{board.total_tasks}</TableCell>
                  <TableCell className="text-right">{board.completed_tasks}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Progress
                        value={board.completion_rate}
                        className="h-2 w-full max-w-[200px]"
                      />
                      <span className="text-sm text-muted-foreground whitespace-nowrap">
                        {board.completion_rate.toFixed(0)}%
                      </span>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}