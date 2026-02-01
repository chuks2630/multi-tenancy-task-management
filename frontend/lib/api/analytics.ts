import apiClient from './client';
import { ApiResponse } from '@/lib/types';

export interface AnalyticsOverview {
  total_tasks: number;
  completed_tasks: number;
  in_progress_tasks: number;
  completion_rate: number;
  active_boards: number;
  active_users: number;
  tasks_created_this_week: number;
  tasks_completed_this_week: number;
  productivity_score: number;
}

export interface TaskTrend {
  date: string;
  created: number;
  completed: number;
}

export interface BoardActivity {
  board_id: number;
  board_name: string;
  total_tasks: number;
  completed_tasks: number;
  completion_rate: number;
}

export interface UserActivity {
  user_id: number;
  user_name: string;
  tasks_created: number;
  tasks_completed: number;
  boards_managed: number;
}

export interface PriorityDistribution {
  priority: string;
  count: number;
}

export interface StatusDistribution {
  status: string;
  count: number;
}

export const analyticsApi = {
  // Get overview stats
  getOverview: async (params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<AnalyticsOverview> => {
    const response = await apiClient.get<ApiResponse<AnalyticsOverview>>(
      '/analytics/overview',
      { params }
    );
    return response.data.data!;
  },

  // Get task trends
  getTaskTrends: async (params?: {
    start_date?: string;
    end_date?: string;
    interval?: 'daily' | 'weekly' | 'monthly';
  }): Promise<TaskTrend[]> => {
    const response = await apiClient.get<ApiResponse<{ trends: TaskTrend[] }>>(
      '/analytics/task-trends',
      { params }
    );
    return response.data.data!.trends;
  },

  // Get board activity
  getBoardActivity: async (params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<BoardActivity[]> => {
    const response = await apiClient.get<ApiResponse<{ boards: BoardActivity[] }>>(
      '/analytics/board-activity',
      { params }
    );
    return response.data.data!.boards;
  },

  // Get user activity
  getUserActivity: async (params?: {
    start_date?: string;
    end_date?: string;
  }): Promise<UserActivity[]> => {
    const response = await apiClient.get<ApiResponse<{ users: UserActivity[] }>>(
      '/analytics/user-activity',
      { params }
    );
    return response.data.data!.users;
  },

  // Get priority distribution
  getPriorityDistribution: async (): Promise<PriorityDistribution[]> => {
    const response = await apiClient.get<ApiResponse<{ distribution: PriorityDistribution[] }>>(
      '/analytics/priority-distribution'
    );
    return response.data.data!.distribution;
  },

  // Get status distribution
  getStatusDistribution: async (): Promise<StatusDistribution[]> => {
    const response = await apiClient.get<ApiResponse<{ distribution: StatusDistribution[] }>>(
      '/analytics/status-distribution'
    );
    return response.data.data!.distribution;
  },

  // Export analytics data
  exportData: async (params: {
    start_date?: string;
    end_date?: string;
    format: 'csv' | 'json';
  }): Promise<Blob> => {
    const response = await apiClient.get('/analytics/export', {
      params,
      responseType: 'blob',
    });
    return response.data;
  },
};