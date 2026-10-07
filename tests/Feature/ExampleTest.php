<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_local_laragon_project_host_uses_the_default_database(): void
    {
        config([
            'tenancy.default_hosts' => ['localhost', 'debra-solon-master.test'],
            'tenancy.allowed_hosts' => ['localhost', 'debra-solon-master.test'],
        ]);

        $this->withServerVariables(['HTTP_HOST' => 'debra-solon-master.test'])
            ->get('/')
            ->assertOk();
    }
}
