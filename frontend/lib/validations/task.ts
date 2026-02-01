import { z } from 'zod';

export const createTaskSchema = z.object({
  board_id: z.number().min(1, 'Board is required'),
  title: z.string().min(1, 'Task title is required').max(255, 'Title is too long'),
  description: z.string().max(5000, 'Description is too long').optional(),
  status: z.enum(['todo', 'in_progress', 'done']).optional(),
  priority: z.enum(['low', 'medium', 'high', 'urgent']).optional(),
  assigned_to: z.number().optional().nullable(),
  due_date: z.string().optional().nullable(),
});

export const updateTaskSchema = createTaskSchema.partial();

export type CreateTaskFormData = z.infer<typeof createTaskSchema>;
export type UpdateTaskFormData = z.infer<typeof updateTaskSchema>;