<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class TenantProvisioningService
{
    public function provision(array $data): Tenant
    {
        // Extract data
        $name = $data['name'] ?? $data['organization_name'];
        $subdomain = $data['subdomain'];
        $planId = $data['plan_id'] ?? Plan::where('slug', 'free')->first()->id;
        $owner = $data['owner'];

        // Validate plan exists
        $plan = Plan::findOrFail($planId);

        // Create tenant
        $tenant = Tenant::create([
            'id' => $subdomain,
            'name' => $name,
            'plan_id' => $plan->id,
        ]);

        // Create domain mapping
        $tenant->domains()->create([
            'domain' => $subdomain,
        ]);

        // Run tenant-specific setup
        $tenant->run(function () use ($owner, $tenant) {
            
            // ✅ STEP 1: Seed roles and permissions FIRST
            $this->seedRolesAndPermissions();

            //  STEP 2: Create owner user
            $ownerUser = \App\Models\Tenant\User::create([
                'name' => $owner['name'],
                'email' => $owner['email'],
                'password' => Hash::make($owner['password']),
                'role' => 'owner',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            //  STEP 3: Assign owner role (now it exists!)
            $ownerUser->assignRole('owner');

            //  STEP 4: Create default board
            \App\Models\Tenant\Board::create([
                'name' => 'Getting Started',
                'description' => 'Your first board to get started',
                'created_by' => $ownerUser->id,
                'is_private' => false,
                'color' => '#3B82F6',
            ]);
        });

        // Log subscription creation
        \App\Models\SubscriptionLog::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'event_type' => 'created',
            'metadata' => [
                'owner_email' => $owner['email'],
            ],
        ]);

        return $tenant->fresh();
    }

    /**
     * Seed roles and permissions for tenant
     */
    private function seedRolesAndPermissions(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view teams', 'create teams', 'edit teams', 'delete teams', 'manage teams',
            'view boards', 'create boards', 'edit boards', 'delete boards', 'manage boards',
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view users', 'invite users', 'edit users', 'delete users',
            'view analytics',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create roles and assign permissions
        $owner = \Spatie\Permission\Models\Role::create([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);
        $owner->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $admin = \Spatie\Permission\Models\Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin->givePermissionTo([
            'view teams', 'create teams', 'edit teams', 'manage teams',
            'view boards', 'create boards', 'edit boards', 'manage boards',
            'view tasks', 'create tasks', 'edit tasks', 'delete tasks', 'assign tasks',
            'view users', 'invite users', 'edit users',
            'view analytics',
        ]);

        $member = \Spatie\Permission\Models\Role::create([
            'name' => 'member',
            'guard_name' => 'web',
        ]);
        $member->givePermissionTo([
            'view teams',
            'view boards', 'create boards',
            'view tasks', 'create tasks', 'edit tasks',
            'view users',
        ]);

        $viewer = \Spatie\Permission\Models\Role::create([
            'name' => 'viewer',
            'guard_name' => 'web',
        ]);
        $viewer->givePermissionTo([
            'view teams',
            'view boards',
            'view tasks',
            'view users',
        ]);
    }

    /**
     * Deprovision tenant
     */
    public function deprovision(Tenant $tenant): void
    {
        // Cancel Stripe subscription if exists
        if ($tenant->stripe_subscription_id) {
            try {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                $stripe->subscriptions->cancel($tenant->stripe_subscription_id);
            } catch (\Exception $e) {
                Log::error('Failed to cancel Stripe subscription', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete tenant (cascade deletes domain and database)
        $tenant->delete();
    }

    /**
     * Check if subdomain is available
     */
    public function isSubdomainAvailable(string $subdomain): bool
    {
        return !Tenant::where('id', $subdomain)->exists();
    }

    /**
     * Validate tenant health
     */
    public function validateTenantHealth(Tenant $tenant): array
    {
        $issues = [];

        $tenant->run(function () use (&$issues) {
            // Check if owner exists
            if (!\App\Models\Tenant\User::where('role', 'owner')->exists()) {
                $issues[] = 'No owner user found';
            }

            // Check if roles exist
            if (!\Spatie\Permission\Models\Role::where('name', 'owner')->exists()) {
                $issues[] = 'Roles not seeded';
            }

            // Check if permissions exist
            if (\Spatie\Permission\Models\Permission::count() === 0) {
                $issues[] = 'Permissions not seeded';
            }
        });

        return $issues;
    }
}