<?php

namespace Tests;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantProvisioningService; 
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TenantTestCase extends BaseTestCase
{

    protected Tenant $tenant;
    protected array $ownerData;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::where(['slug' => 'free'])->first();

        $this->ownerData = [
            'name'     => 'Test Owner',
            'email'    => 'owner@taskmanager.test',
            'password' => 'password',
        ];

        $this->tenant = app(TenantProvisioningService::class)->provision([
            'name'      => 'Task Corp',
            'subdomain' => 'task',
            'plan_id'   => $plan->id,
            'owner'     => $this->ownerData,
        ]);

        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }
}