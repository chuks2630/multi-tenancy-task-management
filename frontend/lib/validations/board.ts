import { z } from 'zod';

export const createBoardSchema = z.object({
  name: z.string().min(1, 'Board name is required').max(255, 'Board name is too long'),
  description: z.string().max(1000, 'Description is too long').optional(),
  team_id: z.number().optional(),
  is_private: z.boolean().optional(),
  color: z.string().regex(/^#[0-9A-Fa-f]{6}$/, 'Invalid color format').optional(),
});

export const updateBoardSchema = createBoardSchema.partial();

export type CreateBoardFormData = z.infer<typeof createBoardSchema>;
export type UpdateBoardFormData = z.infer<typeof updateBoardSchema>;