'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { 
  LayoutDashboard, 
  KanbanSquare, 
  CheckSquare, 
  Users, 
  BarChart3, 
  CreditCard, 
  Settings,
  Building2,
  Shield
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { NavItem } from '@/lib/types/navigation';
import { Can } from '@/components/auth/can';
import { usePermissions } from '@/lib/hooks/use-permissions';

interface SidebarProps {
  tenantName?: string;
}

const mainNavigation: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard,
  },
  {
    title: 'Boards',
    href: '/boards',
    icon: KanbanSquare,
  },
  {
    title: 'Tasks',
    href: '/tasks',
    icon: CheckSquare,
  },
  {
    title: 'Teams',
    href: '/teams',
    icon: Users,
  },
  {
    title: 'Analytics',
    href: '/analytics',
    icon: BarChart3,
  },
];

const settingsNavigation: NavItem[] = [
  {
    title: 'Billing',
    href: '/billing',
    icon: CreditCard,
  },
  {
    title: 'Settings',
    href: '/settings',
    icon: Settings,
  },
];

export function Sidebar({ tenantName }: SidebarProps) {
  const pathname = usePathname();
  const { isOwner, isAdmin } = usePermissions();

  return (
    <div className="flex h-full flex-col border-r bg-gray-50/40">
      {/* Logo/Brand */}
      <div className="flex h-16 items-center border-b px-6">
        <Link href="/dashboard" className="flex items-center gap-2 font-semibold">
          <Building2 className="h-6 w-6 text-primary" />
          <div className="flex flex-col">
            <span className="text-sm font-bold">{tenantName || 'Task Manager'}</span>
            <span className="text-xs text-muted-foreground">Workspace</span>
          </div>
        </Link>
      </div>

      {/* Navigation */}
      <ScrollArea className="flex-1 px-3 py-4">
        <nav className="space-y-6">
          {/* Main Navigation */}
          <div className="space-y-1">
            {mainNavigation.map((item) => {
              const isActive = pathname === item.href || pathname.startsWith(item.href + '/');
              const Icon = item.icon;

              // Check permissions for specific routes
              if (item.href === '/analytics') {
                return (
                  <Can key={item.href} permission="view analytics">
                    <Link
                      href={item.href}
                      className={cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-gray-100',
                        isActive
                          ? 'bg-gray-100 text-gray-900'
                          : 'text-gray-600 hover:text-gray-900'
                      )}
                    >
                      <Icon className="h-4 w-4" />
                      <span>{item.title}</span>
                      {item.badge && (
                        <span className="ml-auto flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
                          {item.badge}
                        </span>
                      )}
                    </Link>
                  </Can>
                );
              }

              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-gray-100',
                    isActive
                      ? 'bg-gray-100 text-gray-900'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  <Icon className="h-4 w-4" />
                  <span>{item.title}</span>
                  {item.badge && (
                    <span className="ml-auto flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] text-primary-foreground">
                      {item.badge}
                    </span>
                  )}
                </Link>
              );
            })}
          </div>

          {/* Admin Section */}
          {isAdmin() && (
            <div className="space-y-1">
              <div className="px-3 py-2">
                <h2 className="mb-2 text-xs font-semibold uppercase tracking-tight text-gray-500 flex items-center gap-2">
                  <Shield className="h-3 w-3" />
                  Admin
                </h2>
              </div>
              <Can role={['owner', 'admin']}>
                <Link
                  href="/admin/users"
                  className={cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-gray-100',
                    pathname === '/admin/users'
                      ? 'bg-gray-100 text-gray-900'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  <Users className="h-4 w-4" />
                  <span>User Management</span>
                </Link>
              </Can>
            </div>
          )}

          {/* Settings Section */}
          <div className="space-y-1">
            <div className="px-3 py-2">
              <h2 className="mb-2 text-xs font-semibold uppercase tracking-tight text-gray-500">
                Settings
              </h2>
            </div>
            {settingsNavigation.map((item) => {
              const isActive = pathname === item.href || pathname.startsWith(item.href + '/');
              const Icon = item.icon;

              // Only owner/admin can see billing
              if (item.href === '/billing') {
                return (
                  <Can key={item.href} role={['owner', 'admin']}>
                    <Link
                      href={item.href}
                      className={cn(
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-gray-100',
                        isActive
                          ? 'bg-gray-100 text-gray-900'
                          : 'text-gray-600 hover:text-gray-900'
                      )}
                    >
                      <Icon className="h-4 w-4" />
                      <span>{item.title}</span>
                    </Link>
                  </Can>
                );
              }

              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all hover:bg-gray-100',
                    isActive
                      ? 'bg-gray-100 text-gray-900'
                      : 'text-gray-600 hover:text-gray-900'
                  )}
                >
                  <Icon className="h-4 w-4" />
                  <span>{item.title}</span>
                </Link>
              );
            })}
          </div>
        </nav>
      </ScrollArea>

      {/* Footer with role badge */}
      <div className="border-t p-4">
        {isOwner() && (
          <Badge variant="secondary" className="w-full justify-center mb-2">
            <Shield className="h-3 w-3 mr-1" />
            Owner
          </Badge>
        )}
        <div className="rounded-lg bg-blue-50 p-3">
          <p className="text-xs font-medium text-blue-900">
            💡 Tip: Use keyboard shortcuts
          </p>
          <p className="mt-1 text-xs text-blue-700">
            Press <kbd className="px-1 rounded bg-blue-100">Ctrl+K</kbd> to search
          </p>
        </div>
      </div>
    </div>
  );
}