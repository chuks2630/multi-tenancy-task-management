import apiClient from './client';
import { Board, ApiResponse, PaginatedResponse } from '@/lib/types';

export const boardsApi = {
  // Get all boards
  getAll: async (params?: {
    search?: string;
    team_id?: number;
    per_page?: number;
  }): Promise<PaginatedResponse<Board>> => {
    const response = await apiClient.get<PaginatedResponse<Board>>('/boards', { params });
    return response.data;
  },

  // Get single board with tasks
  getById: async (id: number): Promise<Board> => {
    const response = await apiClient.get<ApiResponse<Board>>(`/boards/${id}`);
    return response.data.data!;
  },

  // Create board
  create: async (data: {
    name: string;
    description?: string;
    team_id?: number;
    is_private?: boolean;
    color?: string;
  }): Promise<Board> => {
    const response = await apiClient.post<ApiResponse<Board>>('/boards', data);
    return response.data.data!;
  },

  // Update board
  update: async (id: number, data: Partial<Board>): Promise<Board> => {
    const response = await apiClient.put<ApiResponse<Board>>(`/boards/${id}`, data);
    return response.data.data!;
  },

  // Delete board
  delete: async (id: number): Promise<void> => {
    await apiClient.delete(`/boards/${id}`);
  },

  // Get board statistics
  getStatistics: async (id: number): Promise<any> => {
    const response = await apiClient.get<ApiResponse>(`/boards/${id}/statistics`);
    return response.data.data;
  },
};