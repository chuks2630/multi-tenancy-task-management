'use client';

import { useAuth } from '@/lib/auth/auth-context';
import { Permission } from '@/lib/types';

export function usePermissions() {
  const { user } = useAuth();

  const hasPermission = (permission: Permission): boolean => {
    if (!user) return false;

    // Owner has all permissions
    if (user.role === 'owner') return true;

    // Check if user has the specific permission
    return user.permissions?.includes(permission) || false;
  };

  const hasAnyPermission = (permissions: Permission[]): boolean => {
    return permissions.some((permission) => hasPermission(permission));
  };

  const hasAllPermissions = (permissions: Permission[]): boolean => {
    return permissions.every((permission) => hasPermission(permission));
  };

  const hasRole = (role: string | string[]): boolean => {
    if (!user) return false;

    const roles = Array.isArray(role) ? role : [role];
    return roles.includes(user.role);
  };

  const isOwner = (): boolean => {
    return user?.role === 'owner';
  };

  const isAdmin = (): boolean => {
    return user?.role === 'admin' || user?.role === 'owner';
  };

  const canManage = (resource: 'teams' | 'boards' | 'tasks' | 'users' | 'settings'): boolean => {
    return hasPermission(`manage ${resource}` as Permission);
  };

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    isOwner,
    isAdmin,
    canManage,
    user,
  };
}