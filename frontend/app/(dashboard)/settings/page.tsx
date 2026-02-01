import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';

export default function SettingsPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Settings</h1>
          <p className="text-muted-foreground mt-2">
            View and manage all your settings
          </p>
        </div>
        <Button>
          <Plus className="mr-2 h-4 w-4" />
          New settings
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>All Settings</CardTitle>
          <CardDescription>Settings across all boards</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="text-center py-12 text-muted-foreground">
            <p>No settings yet.</p>
            <p className="text-sm mt-2">Create a board and add tasks to get started!</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}