'use client';

import { useRouter } from 'next/navigation';
import { XCircle } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function BillingCancelPage() {
  const router = useRouter();

  return (
    <div className="flex h-screen items-center justify-center p-6">
      <Card className="max-w-md">
        <CardHeader className="text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
            <XCircle className="h-8 w-8 text-orange-600" />
          </div>
          <CardTitle>Payment Cancelled</CardTitle>
          <CardDescription>
            Your subscription was not activated
          </CardDescription>
        </CardHeader>
        <CardContent className="text-center space-y-4">
          <p className="text-sm text-muted-foreground">
            No charges were made. You can try again anytime.
          </p>
          <div className="space-y-2">
            <Button onClick={() => router.push('/billing')} className="w-full">
              Back to Billing
            </Button>
            <Button 
              onClick={() => router.push('/dashboard')} 
              variant="outline"
              className="w-full"
            >
              Go to Dashboard
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}