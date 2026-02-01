'use client';

import { useCallback } from 'react';

/**
 * Hook to handle redirect after login
 * Returns a function that gets the redirect path and clears it from storage
 */
export function useRedirectAfterLogin() {
  const getRedirectPath = useCallback(() => {
    // Check sessionStorage first
    const sessionPath = sessionStorage.getItem('redirect_after_login');
    
    if (sessionPath) {
      // Clear it
      sessionStorage.removeItem('redirect_after_login');
      
      // Validate the path (prevent open redirect)
      if (sessionPath.startsWith('/') && !sessionPath.startsWith('//')) {
        return sessionPath;
      }
    }

    // Default to dashboard
    return '/dashboard';
  }, []);

  return getRedirectPath;
}