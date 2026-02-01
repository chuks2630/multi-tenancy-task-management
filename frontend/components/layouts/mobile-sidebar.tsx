'use client';

import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Sidebar } from './sidebar';

interface MobileSidebarProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  tenantName?: string;
}

export function MobileSidebar({ open, onOpenChange, tenantName }: MobileSidebarProps) {
  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="left" className="w-72 p-0">
        <Sidebar tenantName={tenantName} />
      </SheetContent>
    </Sheet>
  );
}