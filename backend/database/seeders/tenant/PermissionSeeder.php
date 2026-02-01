<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Team permissions
            'view teams',
            'create teams',
            'edit teams',
            'delete teams',
            'manage teams',

            // Board permissions
            'view boards',
            'create boards',
            'edit boards',
            'delete boards',
            'manage boards',

            // Task permissions
            'view tasks',
            'create tasks',
            'edit tasks',
            'delete tasks',
            'assign tasks',

            // User permissions
            'view users',
            'invite users',
            'edit users',
            'delete users',

            // Analytics
            'view analytics',

            // Settings
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission,'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $owner = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $owner->givePermissionTo(Permission::all()); // All permissions

        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'view teams', 'create teams', 'edit teams', 'manage teams',
            'view boards', 'create boards', 'edit boards', 'manage boards',
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view users', 'invite users', 'edit users',
            'view analytics',
        ]);

        $member = Role::create(['name' => 'member','guard_name' => 'web']);
        $member->givePermissionTo([
            'view teams',
            'view boards', 'create boards',
            'view tasks', 'create tasks', 'edit tasks',
            'view users',
        ]);

        $viewer = Role::create(['name' => 'viewer','guard_name' => 'web']);
        $viewer->givePermissionTo([
            'view teams',
            'view boards',
            'view tasks',
            'view users',
        ]);
    }
}