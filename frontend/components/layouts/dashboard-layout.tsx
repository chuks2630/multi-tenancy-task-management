'use client';

import { useState } from 'react';
import { useAuth } from '@/lib/auth/auth-context';
import { Header } from './header';
import { Sidebar } from './sidebar';
import { MobileSidebar } from './mobile-sidebar';
import { useKeyboardShortcuts } from '@/lib/hooks/use-keyboard-shortcuts';

interface DashboardLayoutProps {
  children: React.ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
    useKeyboardShortcuts();
  const { user } = useAuth();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [tenantName] = useState<string>(() => {
    if (typeof window !== 'undefined') {
      const parts = window.location.hostname.split('.');
      if (parts.length > 1 && parts[0] !== 'localhost') {
        return parts[0].charAt(0).toUpperCase() + parts[0].slice(1);
      }
    }
    return '';
  });

  return (
    <div className="flex h-screen overflow-hidden">
      {/* Desktop Sidebar */}
      <aside className="hidden md:flex md:w-64 md:flex-col">
        <Sidebar tenantName={tenantName} />
      </aside>

      {/* Mobile Sidebar */}
      <MobileSidebar
        open={mobileMenuOpen}
        onOpenChange={setMobileMenuOpen}
        tenantName={tenantName}
      />

      {/* Main Content */}
      <div className="flex flex-1 flex-col overflow-hidden">
        <Header onMenuClick={() => setMobileMenuOpen(true)} />
        
        <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
          {children}
        </main>
      </div>
    </div>
  );
}