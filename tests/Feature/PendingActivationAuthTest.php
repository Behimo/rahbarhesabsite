<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingActivationAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_activation_password_login_redirects_to_activate(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => 'SecretPass1',
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => null,
            'phone' => null,
            'is_wp_password' => false,
        ]);

        $response = $this->post(route('login.password'), [
            'login' => 'pending@example.com',
            'password' => 'SecretPass1',
        ]);

        $response->assertRedirect(route('login.activate'));
        $this->assertGuest();
        $this->assertSame($user->id, session('activate_user_id'));
    }

    public function test_pending_activation_user_cannot_use_panel_session(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => null,
        ]);

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertForbidden();
    }
}
