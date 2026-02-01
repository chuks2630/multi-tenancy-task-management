import Link from 'next/link';
import { MoreVertical, Users, CheckSquare } from 'lucide-react';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Board } from '@/lib/types';
import { Can } from '@/components/auth/can';

interface BoardCardProps {
  board: Board;
  onEdit: (board: Board) => void;
  onDelete: (board: Board) => void;
}

export function BoardCard({ board, onEdit, onDelete }: BoardCardProps) {
  return (
    <Card className="group hover:shadow-md transition-shadow">
      <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-3">
        <div className="flex items-center gap-2 flex-1 min-w-0">
          <div
            className="h-3 w-3 rounded-full flex-shrink-0"
            style={{ backgroundColor: board.color }}
          />
          <Link
            href={`/boards/${board.id}`}
            className="font-semibold text-base hover:underline truncate"
          >
            {board.name}
          </Link>
        </div>
        <Can permission={['edit boards', 'manage boards']}>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <MoreVertical className="h-4 w-4" />
                </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                <Can permission={['edit boards', 'manage boards']}>
                    <DropdownMenuItem onClick={() => onEdit(board)}>
                    Edit
                    </DropdownMenuItem>
                </Can>
                <Can permission={['delete boards', 'manage boards']}>
                    <DropdownMenuItem
                    onClick={() => onDelete(board)}
                    className="text-red-600"
                    >
                    Delete
                    </DropdownMenuItem>
                </Can>
                </DropdownMenuContent>
            </DropdownMenu>
        </Can>
      </CardHeader>
      <CardContent>
        {board.description && (
          <p className="text-sm text-muted-foreground line-clamp-2">
            {board.description}
          </p>
        )}
      </CardContent>
      <CardFooter className="flex items-center gap-4 text-xs text-muted-foreground">
        <div className="flex items-center gap-1">
          <CheckSquare className="h-3 w-3" />
          <span>{board.tasks_count || 0} tasks</span>
        </div>
        {board.team && (
          <div className="flex items-center gap-1">
            <Users className="h-3 w-3" />
            <span>{board.team.name}</span>
          </div>
        )}
      </CardFooter>
    </Card>
  );
}