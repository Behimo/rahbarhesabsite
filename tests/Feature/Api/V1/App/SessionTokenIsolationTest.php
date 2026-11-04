<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\User;

class SessionTokenIsolationTest extends MobileApiTestCase
{
    public function test_a_website_session_cannot_call_the_app_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders($this->appHeaders())
            ->getJson('/api/v1/app/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_an_app_token_cannot_open_the_website_panel(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->appHeaders($user))
            ->get('/panel')
            ->assertRedirect(route('login'));
    }
}
