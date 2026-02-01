'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { User, LoginCredentials, AuthResponse } from '@/lib/types';
import apiClient from '@/lib/api/client';
import { authApi } from '@/lib/api/auth';
import { clearAuthData } from '@/lib/utils/auth';

interface AuthContextType {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: () => void;
  setUser: (user: User) => void;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Load user from localStorage on mount
  useEffect(() => {
    const loadUser = async () => {
      setIsLoading(true);
      
      const storedToken = localStorage.getItem('auth_token');
      const storedUser = localStorage.getItem('user');

      if (storedToken && storedUser) {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
        
        // Fetch fresh user data with permissions
        try {
          await refreshUserData();
        } catch (error) {
          console.error('Failed to refresh user data:', error);
          // If refresh fails, clear everything
          handleLogout();
        }
      }

      setIsLoading(false);
    };

    loadUser();
  }, []);

  // Refresh user data from server
  const refreshUserData = async () => {
    try {
      const freshUser = await authApi.me();
      setUser(freshUser);
      localStorage.setItem('user', JSON.stringify(freshUser));
    } catch (error) {
      console.error('Failed to refresh user data:', error);
      throw error;
    }
  };

  // Login function
  const login = async (credentials: LoginCredentials) => {
    const response = await apiClient.post<AuthResponse>('/auth/login', credentials);
    
    const { user, token } = response.data.data;
    
    setUser(user);
    setToken(token);
    
    localStorage.setItem('auth_token', token);
    localStorage.setItem('user', JSON.stringify(user));
    
    // Fetch fresh user data with permissions
    await refreshUserData();
  };

  // ✅ Fixed logout function
  const handleLogout = () => {
  console.log('Logging out...');
  
  // Clear state
  setUser(null);
  setToken(null);
  
  // Clear all storage
  clearAuthData();
  
  console.log('Logout complete');
};

  return (
    <AuthContext.Provider
      value={{
        user,
        token,
        isLoading,
        isAuthenticated: !!user && !!token,
        login,
        logout: handleLogout,
        setUser,
        refreshUser: refreshUserData,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  
  return context;
}