'use client';

import { Shield, Info } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { usePermissions } from '@/lib/hooks/use-permissions';

const rolePermissions = {
  owner: {
    description: 'Full control over the organization',
    permissions: [
      'All permissions',
      'Manage billing and subscription',
      'Delete organization',
      'Assign roles to users',
    ],
    color: 'bg-purple-100 text-purple-800',
  },
  admin: {
    description: 'Manage teams, boards, and users',
    permissions: [
      'Create and manage teams',
      'Create and manage boards',
      'Manage tasks',
      'Invite and manage users',
      'View analytics',
    ],
    color: 'bg-blue-100 text-blue-800',
  },
  member: {
    description: 'Create and collaborate on tasks',
    permissions: [
      'View teams',
      'Create and edit boards',
      'Create and edit tasks',
      'View users',
    ],
    color: 'bg-green-100 text-green-800',
  },
  viewer: {
    description: 'Read-only access',
    permissions: [
      'View teams',
      'View boards',
      'View tasks',
      'View users',
    ],
    color: 'bg-gray-100 text-gray-800',
  },
};

export default function PermissionsPage() {
  const { user } = usePermissions();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
          <Shield className="h-8 w-8" />
          Permissions & Roles
        </h1>
        <p className="text-muted-foreground mt-2">
          Understand what each role can do in your workspace
        </p>
      </div>

      {/* Current Role */}
      <Card className="border-primary">
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Your Role</CardTitle>
              <CardDescription>Your current permissions in this workspace</CardDescription>
            </div>
            <Badge className={rolePermissions[user?.role as keyof typeof rolePermissions]?.color || ''}>
              {user?.role}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            {rolePermissions[user?.role as keyof typeof rolePermissions]?.description}
          </p>
        </CardContent>
      </Card>

      {/* All Roles */}
      <div className="grid gap-4 md:grid-cols-2">
        {Object.entries(rolePermissions).map(([role, details]) => (
          <Card key={role}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="capitalize">{role}</CardTitle>
                <Badge className={details.color}>
                  {role}
                </Badge>
              </div>
              <CardDescription>{details.description}</CardDescription>
            </CardHeader>
            <CardContent>
              <ul className="space-y-2">
                {details.permissions.map((permission, index) => (
                  <li key={index} className="flex items-start gap-2 text-sm">
                    <div className="h-5 w-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                      <div className="h-2 w-2 rounded-full bg-green-600" />
                    </div>
                    <span>{permission}</span>
                  </li>
                ))}
              </ul>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Info Card */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Info className="h-5 w-5" />
            Need Different Permissions?
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          <p className="text-sm text-muted-foreground">
            If you need additional permissions or want to change your role, contact your workspace owner or administrator.
          </p>
          <p className="text-sm text-muted-foreground">
            Only owners and administrators can modify user roles and permissions.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}