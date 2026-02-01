import { useDroppable } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { Plus } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { TaskCard } from '@/components/tasks/task-card';
import { Task, TaskStatus } from '@/lib/types';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Can } from '@/components/auth/can';

interface KanbanColumnProps {
  status: TaskStatus;
  title: string;
  tasks: Task[];
  onTaskClick: (task: Task) => void;
  onTaskEdit: (task: Task) => void;
  onTaskDelete: (task: Task) => void;
  onAddTask: (status: TaskStatus) => void;
}

const statusColors = {
  todo: 'border-l-4 border-l-gray-400',
  in_progress: 'border-l-4 border-l-blue-500',
  done: 'border-l-4 border-l-green-500',
};

export function KanbanColumn({
  status,
  title,
  tasks,
  onTaskClick,
  onTaskEdit,
  onTaskDelete,
  onAddTask,
}: KanbanColumnProps) {
  const { setNodeRef } = useDroppable({ id: status });

  return (
    <Card className={`flex flex-col h-full ${statusColors[status]}`}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-sm font-semibold flex items-center gap-2">
            {title}
            <span className="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs">
              {tasks.length}
            </span>
          </CardTitle>
          <Can permission={['create tasks', 'manage boards']}>
            <Button
                variant="ghost"
                size="icon"
                className="h-6 w-6"
                onClick={() => onAddTask(status)}
            >
                <Plus className="h-4 w-4" />
            </Button>
        </Can>
        </div>
      </CardHeader>
      <CardContent className="flex-1 overflow-hidden p-0 px-3 pb-3">
        <ScrollArea className="h-full">
          <SortableContext
            id={status}
            items={tasks.map((t) => t.id)}
            strategy={verticalListSortingStrategy}
          >
            <div ref={setNodeRef} className="space-y-2 min-h-[100px]">
              {tasks.map((task) => (
                <TaskCard
                  key={task.id}
                  task={task}
                  onClick={() => onTaskClick(task)}
                  onEdit={() => onTaskEdit(task)}
                  onDelete={() => onTaskDelete(task)}
                />
              ))}
            </div>
          </SortableContext>
        </ScrollArea>
      </CardContent>
    </Card>
  );
}