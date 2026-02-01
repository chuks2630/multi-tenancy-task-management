'use client';

import { ReactNode } from 'react';
import { usePermissions } from '@/lib/hooks/use-permissions';
import { Permission } from '@/lib/types';

interface CannotProps {
  permission?: Permission | Permission[];
  role?: string | string[];
  children: ReactNode;
}

/**
 * Inverse of Can component - renders when user DOESN'T have permission
 * 
 * @example
 * <Cannot permission="manage settings">
 *   <p>Contact admin to change settings</p>
 * </Cannot>
 */
export function Cannot({ permission, role, children }: CannotProps) {
  const { hasPermission, hasAnyPermission, hasRole } = usePermissions();

  let allowed = true;

  if (permission) {
    if (Array.isArray(permission)) {
      allowed = !hasAnyPermission(permission);
    } else {
      allowed = !hasPermission(permission);
    }
  }

  if (role) {
    allowed = allowed && !hasRole(role);
  }

  return allowed ? <>{children}</> : null;
}