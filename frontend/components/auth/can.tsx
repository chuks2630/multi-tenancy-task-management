'use client';

import { ReactNode } from 'react';
import { usePermissions } from '@/lib/hooks/use-permissions';
import { Permission } from '@/lib/types';

interface CanProps {
  permission?: Permission | Permission[];
  role?: string | string[];
  fallback?: ReactNode;
  children: ReactNode;
}

/**
 * Component that conditionally renders children based on permissions or roles
 * 
 * @example
 * <Can permission="create boards">
 *   <Button>Create Board</Button>
 * </Can>
 * 
 * @example
 * <Can role="owner">
 *   <AdminPanel />
 * </Can>
 * 
 * @example
 * <Can permission={["edit teams", "manage teams"]} fallback={<ViewOnlyMode />}>
 *   <EditMode />
 * </Can>
 */
export function Can({ permission, role, fallback = null, children }: CanProps) {
  const { hasPermission, hasAnyPermission, hasRole } = usePermissions();

  let allowed = false;

  if (permission) {
    if (Array.isArray(permission)) {
      allowed = hasAnyPermission(permission);
    } else {
      allowed = hasPermission(permission);
    }
  }

  if (role) {
    allowed = allowed || hasRole(role);
  }

  return allowed ? <>{children}</> : <>{fallback}</>;
}