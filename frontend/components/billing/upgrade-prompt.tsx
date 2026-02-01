'use client';

import { ArrowUpCircle, X } from 'lucide-react';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

interface UpgradePromptProps {
  feature: string;
  currentLimit: number;
  dismissible?: boolean;
}

export function UpgradePrompt({ feature, currentLimit, dismissible = true }: UpgradePromptProps) {
  const [dismissed, setDismissed] = useState(false);
  const router = useRouter();

  if (dismissed) return null;

  return (
    <Alert className="border-orange-200 bg-orange-50">
      <ArrowUpCircle className="h-4 w-4 text-orange-600" />
      <AlertTitle className="flex items-center justify-between">
        <span className="text-orange-900">Upgrade Required</span>
        {dismissible && (
          <button
            onClick={() => setDismissed(true)}
            className="text-orange-600 hover:text-orange-700"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </AlertTitle>
      <AlertDescription className="text-orange-800">
        <p className="mb-3">
          You've reached your plan limit of {currentLimit} {feature}. 
          Upgrade to continue creating more.
        </p>
        <Button
          size="sm"
          onClick={() => router.push('/billing')}
          className="bg-orange-600 hover:bg-orange-700"
        >
          View Plans
        </Button>
      </AlertDescription>
    </Alert>
  );
}