<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_price_id' => null,
                'price' => 0,
                'billing_period' => 'monthly',
                'features' => [
                    'max_teams' => 1,
                    'max_users' => 3,
                    'max_boards' => 3,
                    'max_tasks_per_board' => 50,
                    'max_storage_mb' => 100,
                    'analytics' => false,
                    'priority_support' => false,
                    'custom_branding' => false,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro (Monthly)',
                'slug' => 'pro-monthly',
                'stripe_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID'), // Set in .env
                'price' => 29.00,
                'billing_period' => 'monthly',
                'features' => [
                    'max_teams' => null, // unlimited
                    'max_users' => null,
                    'max_boards' => null,
                    'max_tasks_per_board' => null,
                    'max_storage_mb' => 10000, // 10GB
                    'analytics' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'api_access' => true,
                    'webhooks' => true,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Pro (Yearly)',
                'slug' => 'pro-yearly',
                'stripe_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
                'price' => 290.00, // 2 months free
                'billing_period' => 'yearly',
                'features' => [
                    'max_teams' => null,
                    'max_users' => null,
                    'max_boards' => null,
                    'max_tasks_per_board' => null,
                    'max_storage_mb' => 10000,
                    'analytics' => true,
                    'priority_support' => true,
                    'custom_branding' => true,
                    'api_access' => true,
                    'webhooks' => true,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Plans seeded successfully!');
    }
}