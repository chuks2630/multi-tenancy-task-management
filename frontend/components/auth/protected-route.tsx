'use client';

import { useEffect } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { Loader2 } from 'lucide-react';
import { useAuth } from '@/lib/auth/auth-context';

interface ProtectedRouteProps {
  children: React.ReactNode;
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const router = useRouter();
  const pathname = usePathname();
  const { isAuthenticated, isLoading } = useAuth();

  useEffect(() => {
    // Don't redirect while loading
    if (isLoading) return;

    // ✅ Only redirect if NOT authenticated
    if (!isAuthenticated) {
      console.log('Not authenticated, redirecting to login...');
      
      // Store intended destination
      if (pathname !== '/login' && pathname !== '/register') {
        sessionStorage.setItem('redirect_after_login', pathname);
      }
      
      // Use replace to prevent back button issues
      router.replace('/login');
    }
  }, [isAuthenticated, isLoading, router, pathname]);

  // ✅ Show loading state
  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  // ✅ Don't render if not authenticated
  if (!isAuthenticated) {
    return (
      <div className="flex h-screen items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return <>{children}</>;
}