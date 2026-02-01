'use client';

import { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  DndContext,
  DragEndEvent,
  DragOverEvent,
  DragOverlay,
  DragStartEvent,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import { ArrowLeft, Settings } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { KanbanColumn } from '@/components/boards/kanban-column';
import { TaskCard } from '@/components/tasks/task-card';
import { TaskDialog } from '@/components/tasks/task-dialog';
import { boardsApi } from '@/lib/api/boards';
import { tasksApi } from '@/lib/api/tasks';
import { Task, TaskStatus } from '@/lib/types';
import { CreateTaskFormData } from '@/lib/validations/task';
import { ReadOnlyBanner } from '@/components/ui/read-only-banner';

export default function BoardPage() {
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const boardId = parseInt(params.id as string);

  const [taskDialogOpen, setTaskDialogOpen] = useState(false);
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [defaultStatus, setDefaultStatus] = useState<TaskStatus>('todo');
  const [activeTask, setActiveTask] = useState<Task | null>(null);

  // Fetch board with tasks
  const { data: board, isLoading } = useQuery({
    queryKey: ['boards', boardId],
    queryFn: () => boardsApi.getById(boardId),
  });

  // Fetch tasks for this board
  const { data: tasksData } = useQuery({
    queryKey: ['tasks', boardId],
    queryFn: () => tasksApi.getAll({ board_id: boardId, per_page: 1000 }),
  });

  const tasks = tasksData?.data || [];

  // Group tasks by status
  const tasksByStatus = {
    todo: tasks.filter((t) => t.status === 'todo'),
    in_progress: tasks.filter((t) => t.status === 'in_progress'),
    done: tasks.filter((t) => t.status === 'done'),
  };

  // Create task mutation
  const createMutation = useMutation({
    mutationFn: (data: CreateTaskFormData) => tasksApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', boardId] });
      queryClient.invalidateQueries({ queryKey: ['boards', boardId] });
      setTaskDialogOpen(false);
      toast.success('Task created successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to create task');
    },
  });

  // Update task mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<Task> }) =>
      tasksApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', boardId] });
      queryClient.invalidateQueries({ queryKey: ['boards', boardId] });
      setTaskDialogOpen(false);
      setSelectedTask(null);
      toast.success('Task updated successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to update task');
    },
  });

  // Delete task mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => tasksApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', boardId] });
      queryClient.invalidateQueries({ queryKey: ['boards', boardId] });
      toast.success('Task deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to delete task');
    },
  });

  // Update positions mutation
  const updatePositionsMutation = useMutation({
    mutationFn: (data: Array<{ id: number; position: number; status: string }>) =>
      tasksApi.updatePositions(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', boardId] });
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to update task positions');
      queryClient.invalidateQueries({ queryKey: ['tasks', boardId] });
    },
  });

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    })
  );

  const handleDragStart = (event: DragStartEvent) => {
    const { active } = event;
    const task = tasks.find((t) => t.id === active.id);
    setActiveTask(task || null);
  };

  const handleDragOver = (event: DragOverEvent) => {
    const { active, over } = event;
    if (!over) return;

    const activeId = active.id;
    const overId = over.id;

    if (activeId === overId) return;

    const activeTask = tasks.find((t) => t.id === activeId);
    const overTask = tasks.find((t) => t.id === overId);

    if (!activeTask) return;

    // Moving to a different column
    const activeStatus = activeTask.status;
    const overStatus = overTask ? overTask.status : (overId as TaskStatus);

    if (activeStatus !== overStatus) {
      // Update task status immediately (optimistic update)
      queryClient.setQueryData(['tasks', boardId], (old: any) => {
        if (!old) return old;
        return {
          ...old,
          data: old.data.map((t: Task) =>
            t.id === activeId ? { ...t, status: overStatus } : t
          ),
        };
      });
    }
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    setActiveTask(null);

    if (!over) return;

    const activeId = active.id as number;
    const overId = over.id;

    const activeTask = tasks.find((t) => t.id === activeId);
    if (!activeTask) return;

    // Determine new status
    const overTask = tasks.find((t) => t.id === overId);
    const newStatus = overTask ? overTask.status : (overId as TaskStatus);

    // Get tasks in the new column
    const columnTasks = tasks.filter((t) => t.status === newStatus);
    const oldIndex = columnTasks.findIndex((t) => t.id === activeId);
    const newIndex = overTask
      ? columnTasks.findIndex((t) => t.id === overId)
      : columnTasks.length;

    if (oldIndex !== -1) {
      const reorderedTasks = arrayMove(columnTasks, oldIndex, newIndex);

      // Update positions
      const updates = reorderedTasks.map((task, index) => ({
        id: task.id,
        position: index,
        status: newStatus,
      }));

      updatePositionsMutation.mutate(updates);
    } else {
      // Task moved to new column
      updateMutation.mutate({
        id: activeId,
        data: { status: newStatus, position: columnTasks.length },
      });
    }
  };

  const handleCreateTask = async (data: CreateTaskFormData) => {
    await createMutation.mutateAsync(data);
  };

  const handleUpdateTask = async (data: CreateTaskFormData) => {
    if (selectedTask) {
      await updateMutation.mutateAsync({ id: selectedTask.id, data });
    }
  };

  const handleTaskClick = (task: Task) => {
    setSelectedTask(task);
    setTaskDialogOpen(true);
  };

  const handleTaskEdit = (task: Task) => {
    setSelectedTask(task);
    setTaskDialogOpen(true);
  };

  const handleTaskDelete = (task: Task) => {
    if (confirm(`Are you sure you want to delete "${task.title}"?`)) {
      deleteMutation.mutate(task.id);
    }
  };

  const handleAddTask = (status: TaskStatus) => {
    setSelectedTask(null);
    setDefaultStatus(status);
    setTaskDialogOpen(true);
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-12 w-64" />
        <div className="grid grid-cols-3 gap-4 h-[600px]">
          <Skeleton className="h-full" />
          <Skeleton className="h-full" />
          <Skeleton className="h-full" />
        </div>
      </div>
    );
  }

  if (!board) {
    return (
      <div className="text-center py-12">
        <p className="text-muted-foreground">Board not found</p>
        <Button onClick={() => router.push('/boards')} className="mt-4">
          Back to Boards
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
        <ReadOnlyBanner />
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push('/boards')}
          >
            <ArrowLeft className="h-5 w-5" />
          </Button>
          <div className="flex items-center gap-3">
            <div
              className="h-4 w-4 rounded-full"
              style={{ backgroundColor: board.color }}
            />
            <div>
              <h1 className="text-2xl font-bold">{board.name}</h1>
              {board.description && (
                <p className="text-sm text-muted-foreground">
                  {board.description}
                </p>
              )}
            </div>
          </div>
        </div>
        <Button variant="outline" size="icon">
          <Settings className="h-4 w-4" />
        </Button>
      </div>

      {/* Kanban Board */}
      <DndContext
        sensors={sensors}
        onDragStart={handleDragStart}
        onDragOver={handleDragOver}
        onDragEnd={handleDragEnd}
      >
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 h-[calc(100vh-250px)]">
          <KanbanColumn
            status="todo"
            title="To Do"
            tasks={tasksByStatus.todo}
            onTaskClick={handleTaskClick}
            onTaskEdit={handleTaskEdit}
            onTaskDelete={handleTaskDelete}
            onAddTask={handleAddTask}
          />
          <KanbanColumn
            status="in_progress"
            title="In Progress"
            tasks={tasksByStatus.in_progress}
            onTaskClick={handleTaskClick}
            onTaskEdit={handleTaskEdit}
            onTaskDelete={handleTaskDelete}
            onAddTask={handleAddTask}
          />
          <KanbanColumn
            status="done"
            title="Done"
            tasks={tasksByStatus.done}
            onTaskClick={handleTaskClick}
            onTaskEdit={handleTaskEdit}
            onTaskDelete={handleTaskDelete}
            onAddTask={handleAddTask}
          />
        </div>

        <DragOverlay>
          {activeTask ? (
            <div className="opacity-50">
              <TaskCard
                task={activeTask}
                onClick={() => {}}
                onEdit={() => {}}
                onDelete={() => {}}
              />
            </div>
          ) : null}
        </DragOverlay>
      </DndContext>

      {/* Task Dialog */}
      <TaskDialog
        open={taskDialogOpen}
        onOpenChange={setTaskDialogOpen}
        task={selectedTask}
        boardId={boardId}
        defaultStatus={defaultStatus}
        onSubmit={selectedTask ? handleUpdateTask : handleCreateTask}
        isLoading={createMutation.isPending || updateMutation.isPending}
      />
    </div>
  );
}