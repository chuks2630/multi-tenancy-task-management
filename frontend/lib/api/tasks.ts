import apiClient from './client';
import { Task, ApiResponse, PaginatedResponse } from '@/lib/types';

export const tasksApi = {
  // Get all tasks
  getAll: async (params?: {
    board_id?: number;
    status?: string;
    priority?: string;
    assigned_to?: number;
    search?: string;
    per_page?: number;
  }): Promise<PaginatedResponse<Task>> => {
    const response = await apiClient.get<PaginatedResponse<Task>>('/tasks', { params });
    return response.data;
  },

  // Get single task
  getById: async (id: number): Promise<Task> => {
    const response = await apiClient.get<ApiResponse<Task>>(`/tasks/${id}`);
    return response.data.data!;
  },

  // Create task
  create: async (data: {
    board_id: number;
    title: string;
    description?: string;
    status?: 'todo' | 'in_progress' | 'done';
    priority?: 'low' | 'medium' | 'high' | 'urgent';
    assigned_to?: number;
    due_date?: string;
  }): Promise<Task> => {
    const response = await apiClient.post<ApiResponse<Task>>('/tasks', data);
    return response.data.data!;
  },

  // Update task
  update: async (id: number, data: Partial<Task>): Promise<Task> => {
    const response = await apiClient.put<ApiResponse<Task>>(`/tasks/${id}`, data);
    return response.data.data!;
  },

  // Delete task
  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/tasks/${id}`);
  },

  // Update task positions (for drag & drop)
  updatePositions: async (tasks: Array<{ id: number; position: number; status: string }>): Promise<void> => {
    await apiClient.post('/tasks/positions', { tasks });
  },

  // Get my tasks
  getMyTasks: async (status?: string): Promise<Task[]> => {
    const response = await apiClient.get<ApiResponse<{ tasks: Task[] }>>('/tasks/my-tasks', {
      params: { status },
    });
    return response.data.data!.tasks;
  },
};