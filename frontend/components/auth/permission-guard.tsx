'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Loader2 } from 'lucide-react';
import { usePermissions } from '@/lib/hooks/use-permissions';
import { Permission } from '@/lib/types';
import { toast } from 'sonner';

interface PermissionGuardProps {
  permission?: Permission | Permission[];
  role?: string | string[];
  fallbackUrl?: string;
  children: React.ReactNode;
}

export function PermissionGuard({
  permission,
  role,
  fallbackUrl = '/dashboard',
  children,
}: PermissionGuardProps) {
  const router = useRouter();
  const { hasPermission, hasAnyPermission, hasRole, user, isLoading } = usePermissions();
  const [hasChecked, setHasChecked] = useState(false);

  useEffect(() => {
    // Don't check until auth is loaded
    if (isLoading) return;
    
    // Only check once
    if (hasChecked) return;

    let allowed = true;

    if (permission) {
      if (Array.isArray(permission)) {
        allowed = hasAnyPermission(permission);
      } else {
        allowed = hasPermission(permission);
      }
    }

    if (role && !hasRole(role)) {
      allowed = false;
    }

    if (!allowed) {
      console.warn('Permission denied:', { permission, role, user: user?.role });
      toast.error('You do not have permission to access this page');
      
      // Mark as checked to prevent multiple redirects
      setHasChecked(true);
      
      // Small delay to prevent immediate loop
      setTimeout(() => {
        router.replace(fallbackUrl);
      }, 100);
    } else {
      setHasChecked(true);
    }
  }, [permission, role, hasPermission, hasAnyPermission, hasRole, router, fallbackUrl, isLoading, hasChecked, user]);

  // Show loading state while checking
  if (isLoading || !hasChecked) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="text-center">
          <Loader2 className="h-8 w-8 animate-spin mx-auto mb-4 text-primary" />
          <p className="text-muted-foreground">Verifying permissions...</p>
        </div>
      </div>
    );
  }

  // Check permission again for render
  let allowed = true;
  if (permission) {
    if (Array.isArray(permission)) {
      allowed = hasAnyPermission(permission);
    } else {
      allowed = hasPermission(permission);
    }
  }
  if (role && !hasRole(role)) {
    allowed = false;
  }

  // Don't render if not allowed
  if (!allowed) {
    return null;
  }

  return <>{children}</>;
}