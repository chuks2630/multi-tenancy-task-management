<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TenantTestCase;

class TenantTeamTest extends TenantTestCase
{
    use RefreshDatabase;
    protected $user;
        protected function setUp(): void
        {
            parent::setUp();
            // get owner
            $this->user = \App\Models\Tenant\User::first();

        }
    /**
     * A basic feature test example.
     */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }

    public function test_create_team(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/tenant/teams', [
            'name' => 'Development Team',
            'description' => 'Handles all development tasks',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'description',
                         'created_by',
                         'created_at',
                         'updated_at',
                     ],
                     'message',
                 ]);
    }
}
