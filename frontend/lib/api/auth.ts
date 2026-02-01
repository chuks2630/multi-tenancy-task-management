import apiClient from './client';
import { LoginCredentials, RegisterData, AuthResponse, User } from '@/lib/types';

export const authApi = {
  // Login
  login: async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/auth/login', credentials);
    return response.data;
  },

  // Register
  register: async (data: RegisterData): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/auth/register', data);
    return response.data;
  },

  // Get current user with permissions
  me: async (): Promise<User> => {
    const response = await apiClient.get<{ 
      success: boolean; 
      data: { 
        user: User;
        permissions?: string[];
        roles?: Array<{ name: string }>;
      } 
    }>('/auth/me');
    
    const userData = response.data.data;
    
    return {
      ...userData.user,
      permissions: userData.permissions || [],
      roles: userData.roles || [],
    };
  },

  // Logout
  logout: async (): Promise<void> => {
    await apiClient.post('/auth/logout');
  },

  // Refresh token
  refresh: async (): Promise<AuthResponse> => {
    const response = await apiClient.post<AuthResponse>('/auth/refresh');
    return response.data;
  },
};