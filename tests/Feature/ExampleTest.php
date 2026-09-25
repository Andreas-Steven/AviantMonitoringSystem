<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_dashboard_requires_authentication_outside_local_preview(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_local_preview_redirects_direct_login_but_keeps_legacy_login_flow_available(): void
    {
        $this->app['env'] = 'local';
        config([
            'app.env' => 'local',
            'app.local_dashboard_bypass' => true,
        ]);

        $this->get('/login')->assertRedirect(route('production.dashboard'));

        $token = 'local-preview-csrf-token';
        $this->withSession(['_token' => $token])
            ->post('/login', ['_token' => $token])
            ->assertRedirect(route('production.dashboard'));

        $this->get('/master/employees')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
        $this->get('/')->assertOk();
    }

    public function test_login_shows_a_clear_message_when_legacy_auth_tables_are_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('app_users')
            ->andReturn(false);

        $this->from('/login')->post('/login', [
            'email' => 'admin@local.test',
            'password' => 'local-test-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }
}
