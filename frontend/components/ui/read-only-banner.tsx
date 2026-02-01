'use client';

import { AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { usePermissions } from '@/lib/hooks/use-permissions';

export function ReadOnlyBanner() {
  const { hasRole } = usePermissions();

  if (!hasRole('viewer')) return null;

  return (
    <Alert className="mb-6">
      <AlertCircle className="h-4 w-4" />
      <AlertDescription>
        You have <strong>view-only</strong> access. Contact your administrator to request additional permissions.
      </AlertDescription>
    </Alert>
  );
}