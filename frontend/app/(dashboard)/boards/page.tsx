'use client';

import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Search } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { BoardCard } from '@/components/boards/board-card';
import { BoardDialog } from '@/components/boards/board-dialog';
import { boardsApi } from '@/lib/api/boards';
import { billingApi } from '@/lib/api/billing';
import { Board } from '@/lib/types';
import { CreateBoardFormData } from '@/lib/validations/board';
import { Can } from '@/components/auth/can';
import { usePermissions } from '@/lib/hooks/use-permissions';
import { ReadOnlyBanner } from '@/components/ui/read-only-banner';
import { UpgradePrompt } from '@/components/billing/upgrade-prompt';

export default function BoardsPage() {
  const queryClient = useQueryClient();
  const [search, setSearch] = useState('');
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedBoard, setSelectedBoard] = useState<Board | null>(null);
  const { hasPermission } = usePermissions();

  // Fetch boards
  const { data: boardsData, isLoading } = useQuery({
    queryKey: ['boards', search],
    queryFn: () => boardsApi.getAll({ search, per_page: 100 }),
  });

  // Fetch usage to check limits
  const { data: usage } = useQuery({
    queryKey: ['usage'],
    queryFn: () => billingApi.getUsage(),
  });


  // Create board mutation
  const createMutation = useMutation({
    mutationFn: (data: CreateBoardFormData) => boardsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['boards'] });
      queryClient.invalidateQueries({ queryKey: ['usage'] });
      setDialogOpen(false);
      toast.success('Board created successfully');
    },
    onError: (error: any) => {
      if (error.response?.status === 403) {
        toast.error('You have reached your plan limit for boards');
      } else {
        toast.error(error.message || 'Failed to create board');
      }
    },
  });

  // Update board mutation
  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: CreateBoardFormData }) =>
      boardsApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['boards'] });
      setDialogOpen(false);
      setSelectedBoard(null);
      toast.success('Board updated successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to update board');
    },
  });

  // Delete board mutation
  const deleteMutation = useMutation({
    mutationFn: (id: number) => boardsApi.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['boards'] });
      toast.success('Board deleted successfully');
    },
    onError: (error: any) => {
      toast.error(error.message || 'Failed to delete board');
    },
  });

  const handleCreateBoard = async (data: CreateBoardFormData) => {
    if (selectedBoard) {
      await updateMutation.mutateAsync({ id: selectedBoard.id, data });
    } else {
      await createMutation.mutateAsync(data);
    }
  };

  const handleEditBoard = (board: Board) => {
    setSelectedBoard(board);
    setDialogOpen(true);
  };

  const handleDeleteBoard = (board: Board) => {
    if (confirm(`Are you sure you want to delete "${board.name}"?`)) {
      deleteMutation.mutate(board.id);
    }
  };

  const handleNewBoard = () => {
    // ✅ Check limit before opening dialog
    const hasReachedLimit = usage && 
      usage.boards.limit !== -1 && 
      usage.boards.current >= usage.boards.limit;

    if (hasReachedLimit) {
      toast.error(`You've reached your plan limit of ${usage.boards.limit} boards. Please upgrade to create more.`);
      return;
    }

    setSelectedBoard(null);
    setDialogOpen(true);
  };

  const boards = boardsData?.data || [];

  const isAtBoardLimit = usage && 
    usage.boards.limit !== -1 && 
    usage.boards.current >= usage.boards.limit;

  return (
    <div className="space-y-6">
        <ReadOnlyBanner />
        {isAtBoardLimit && (
        <UpgradePrompt 
          feature="boards" 
          currentLimit={usage.boards.limit}
        />
      )}
      {/* Header */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 className="text-3xl font-bold tracking-tight">Boards</h1>
            <p className="text-muted-foreground mt-2">
            Manage your project boards
            </p>
        </div>
        <Can permission="create boards">
            <Button onClick={handleNewBoard}>
            <Plus className="mr-2 h-4 w-4" />
            New Board
            </Button>
        </Can>
    </div>

      {/* Search */}
      <div className="relative max-w-sm">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          type="search"
          placeholder="Search boards..."
          className="pl-8"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      {/* Boards Grid */}
      {isLoading ? (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[...Array(6)].map((_, i) => (
            <Skeleton key={i} className="h-40" />
          ))}
        </div>
      ) : boards.length === 0 ? (
        <div className="text-center py-12 border-2 border-dashed rounded-lg">
          <p className="text-muted-foreground">
            {search ? 'No boards found' : 'No boards yet'}
          </p>
          <p className="text-sm text-muted-foreground mt-2">
            {search
              ? 'Try a different search term'
              : 'Create your first board to get started!'}
          </p>
          {!search && (
            <Button onClick={handleNewBoard} className="mt-4">
              <Plus className="mr-2 h-4 w-4" />
              Create Board
            </Button>
          )}
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {boards.map((board) => (
            <BoardCard
              key={board.id}
              board={board}
              onEdit={handleEditBoard}
              onDelete={handleDeleteBoard}
            />
          ))}
        </div>
      )}

      {/* Create/Edit Dialog */}
      <BoardDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        board={selectedBoard}
        onSubmit={handleCreateBoard}
        isLoading={createMutation.isPending || updateMutation.isPending}
      />
    </div>
  );
}