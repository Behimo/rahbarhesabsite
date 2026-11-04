<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class MobileApiTestCase extends TestCase
{
    use RefreshDatabase;

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        // Sanctum's guard keeps the previous request's user on this long-lived
        // test application. A real PHP request starts clean, so the tests do too.
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cms.app.client_key' => 'test-app-key',
            'cms.app.deep_link' => 'rahbarhesab://payment',
            'cms.app.token_ttl_days' => 30,
            'cms.app.max_tokens' => 5,
        ]);
    }

    protected function appToken(User $user, array $abilities = ['app'], ?\DateTimeInterface $expiresAt = null): string
    {
        return $user->createToken('device', $abilities, $expiresAt ?? now()->addDay())->plainTextToken;
    }

    /** @return array<string, string> */
    protected function appHeaders(?User $user = null, array $abilities = ['app']): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
        ];

        if ($user) {
            $headers['Authorization'] = 'Bearer '.$this->appToken($user, $abilities);
        }

        return $headers;
    }
}
