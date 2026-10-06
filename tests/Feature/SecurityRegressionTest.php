<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultUsersSeeder;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\RahbarHesabSeeder;
use Database\Seeders\Support\SampleInstructor;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $payload = [
            'email' => 'nobody@rahbarhesab.ir',
            'password' => 'wrong-password',
        ];

        foreach (range(1, 5) as $i) {
            $this->post(route('admin.login'), $payload)
                ->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login'), $payload)
            ->assertStatus(429);
    }

    public function test_user_password_login_is_rate_limited(): void
    {
        $payload = [
            'login' => 'nobody@rahbarhesab.ir',
            'password' => 'wrong-password',
        ];

        foreach (range(1, 5) as $i) {
            $this->post(route('login.password'), $payload);
        }

        $this->post(route('login.password'), $payload)->assertStatus(429);
    }

    public function test_rate_limiter_keys_exist_for_throttled_routes(): void
    {
        foreach (['login', 'otp-send', 'otp-verify', 'contact', 'api'] as $limiter) {
            $this->assertTrue(
                RateLimiter::limiter($limiter) !== null,
                "Rate limiter [{$limiter}] is not registered."
            );
        }
    }

    public function test_contact_form_is_rate_limited(): void
    {
        $payload = [
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'phone' => '09120000000',
            'message' => 'پیام آزمایشی برای بررسی محدودیت نرخ.',
        ];

        foreach (range(1, 3) as $i) {
            $this->post(route('contact.store'), $payload);
        }

        $this->post(route('contact.store'), $payload)->assertStatus(429);
    }

    public function test_svg_upload_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        Storage::fake('public');

        $svg = UploadedFile::fake()->createWithContent(
            'payload.svg',
            '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $this->actingAs($admin)
            ->post(route('admin.media.store'), ['file' => $svg])
            ->assertSessionHasErrors('file');
    }

    public function test_jpg_upload_is_accepted(): void
    {
        $admin = $this->makeAdmin();

        Storage::fake('public');

        $image = UploadedFile::fake()->image('photo.jpg', 10, 10);

        $this->actingAs($admin)
            ->post(route('admin.media.store'), ['file' => $image])
            ->assertSessionDoesntHaveErrors('file');
    }

    public function test_json_ld_output_never_breaks_out_of_script_tag(): void
    {
        $schema = [
            '@context' => 'https://schema.org',
            'headline' => '</script><script>alert(document.cookie)</script>',
        ];

        $html = view('layouts.site', [
            'structuredData' => [$schema],
            'seo' => ['title' => 'آزمایش'],
            'navLinks' => [],
            'contact' => [],
        ])->render();

        $this->assertStringNotContainsString('</script><script>', $html);
        $this->assertStringContainsString('\u003C', $html);

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertSame($schema['headline'], json_decode($matches[1], true)['headline']);
    }

    public function test_default_seeder_skips_weak_demo_accounts_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['cms.admin_password' => 'a-strong-admin-password']);

        $this->runSeeder(new DefaultUsersSeeder);

        $this->assertDatabaseHas('users', [
            'email' => config('cms.admin_email'),
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'editor@rahbarhesab.ir']);
        $this->assertDatabaseMissing('users', ['email' => 'demo@example.com']);
    }

    public function test_default_seeder_refuses_to_seed_admin_without_a_password_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['cms.admin_password' => null]);

        try {
            $this->runSeeder(new DefaultUsersSeeder);
            $this->fail('Seeder should stop when the production admin password is empty.');
        } catch (RuntimeException) {
            $this->assertDatabaseMissing('users', ['email' => config('cms.admin_email')]);
        }
    }

    public function test_default_seeder_rejects_a_short_admin_password_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['cms.admin_password' => 'secret']);

        try {
            $this->runSeeder(new DefaultUsersSeeder);
            $this->fail('Seeder should stop when the production admin password is too short.');
        } catch (RuntimeException) {
            $this->assertDatabaseMissing('users', ['email' => config('cms.admin_email')]);
        }
    }

    public function test_production_seeder_does_not_overwrite_an_existing_admin_password(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['cms.admin_password' => 'first-password-1234']);

        $this->runSeeder(new DefaultUsersSeeder);

        config(['cms.admin_password' => 'second-password-5678']);
        $this->runSeeder(new DefaultUsersSeeder);

        $user = User::query()->where('email', config('cms.admin_email'))->firstOrFail();

        $this->assertTrue($user->passwordMatches('first-password-1234'));
    }

    public function test_production_content_seed_does_not_create_a_sample_instructor(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->runSeeder(new RahbarHesabSeeder);
        $this->runSeeder(new DemoContentSeeder);

        $this->assertDatabaseMissing('users', ['email' => SampleInstructor::EMAIL]);
        $this->assertDatabaseHas('shop_products', ['slug' => 'ezdevaj-maliati-hoghooghi']);
    }

    public function test_production_seed_does_not_reset_an_existing_instructor_password(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $instructor = User::factory()->create([
            'email' => SampleInstructor::EMAIL,
            'phone' => SampleInstructor::PHONE,
            'mobile' => SampleInstructor::PHONE,
            'password' => 'a-real-instructor-password',
        ]);

        $this->runSeeder(new RahbarHesabSeeder);

        $this->assertTrue($instructor->fresh()->passwordMatches('a-real-instructor-password'));
    }

    /**
     * @param  array<int, \Illuminate\Cache\RateLimiting\Limit>  $limits
     */
    protected function phoneLimitKey(array $limits): ?string
    {
        foreach ($limits as $limit) {
            if (str_starts_with((string) $limit->key, 'otp-send:phone:')) {
                return $limit->key;
            }
        }

        return null;
    }

    protected function runSeeder(Seeder $seeder): void
    {
        $seeder->setContainer($this->app);
        $seeder->__invoke();
    }

    public function test_ensure_admin_does_not_overwrite_an_existing_password(): void
    {
        config(['cms.admin_password' => 'first-password-1234']);

        $this->artisan('cms:ensure-admin')->assertSuccessful();

        $email = config('cms.admin_email');
        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertTrue($user->passwordMatches('first-password-1234'));

        config(['cms.admin_password' => 'second-password-5678']);

        $this->artisan('cms:ensure-admin')->assertSuccessful();

        $this->assertTrue(
            $user->fresh()->passwordMatches('first-password-1234'),
            'cms:ensure-admin must not reset an existing password without --reset-password.'
        );

        $this->artisan('cms:ensure-admin --reset-password')->assertSuccessful();

        $this->assertTrue($user->fresh()->passwordMatches('second-password-5678'));
    }

    public function test_ensure_admin_rejects_short_passwords(): void
    {
        config(['cms.admin_password' => 'short']);

        $this->artisan('cms:ensure-admin')->assertFailed();
    }

    public function test_ensure_admin_fails_when_password_missing_and_user_absent(): void
    {
        config(['cms.admin_password' => null]);

        $this->artisan('cms:ensure-admin')->assertFailed();
    }

    public function test_ensure_admin_keeps_the_password_when_env_password_is_short(): void
    {
        config(['cms.admin_password' => 'first-password-1234']);
        $this->artisan('cms:ensure-admin')->assertSuccessful();

        config(['cms.admin_password' => 'short']);
        $this->artisan('cms:ensure-admin')->assertSuccessful();

        $user = User::query()->where('email', config('cms.admin_email'))->firstOrFail();

        $this->assertTrue($user->passwordMatches('first-password-1234'));
    }

    public function test_ensure_admin_refuses_to_reset_with_a_short_password(): void
    {
        config(['cms.admin_password' => 'first-password-1234']);
        $this->artisan('cms:ensure-admin')->assertSuccessful();

        config(['cms.admin_password' => 'short']);
        $this->artisan('cms:ensure-admin --reset-password')->assertFailed();

        $user = User::query()->where('email', config('cms.admin_email'))->firstOrFail();

        $this->assertTrue($user->passwordMatches('first-password-1234'));
    }

    public function test_non_string_login_field_does_not_crash_the_rate_limiter(): void
    {
        $this->from(route('admin.login'))
            ->post(route('admin.login'), [
                'email' => ['not-a-string'],
                'password' => 'wrong-password',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_spoofed_forwarded_for_does_not_reset_the_contact_limit(): void
    {
        $payload = [
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'phone' => '09120000000',
            'message' => 'پیام آزمایشی برای بررسی محدودیت نرخ.',
        ];

        foreach (range(1, 3) as $i) {
            $this->post(route('contact.store'), $payload);
        }

        $this->withHeader('X-Forwarded-For', '203.0.113.50')
            ->post(route('contact.store'), $payload)
            ->assertStatus(429);
    }

    public function test_otp_rate_limit_normalizes_phone_and_ignores_non_strings(): void
    {
        $limiter = RateLimiter::limiter('otp-send');

        $this->assertNotNull($limiter);

        $local = $limiter(Request::create('/login/otp', 'POST', ['phone' => '09121234567']));
        $international = $limiter(Request::create('/login/otp', 'POST', ['phone' => '+989121234567']));
        $array = $limiter(Request::create('/login/otp', 'POST', ['phone' => ['09121234567']]));

        $this->assertSame('otp-send:phone:09121234567', $this->phoneLimitKey($local));
        $this->assertSame($this->phoneLimitKey($local), $this->phoneLimitKey($international));
        $this->assertNull($this->phoneLimitKey($array));
    }

    public function test_api_accepts_only_the_configured_token(): void
    {
        config(['cms.api_token' => 'correct-token-value']);

        $this->getJson('/api/v1/pages')->assertUnauthorized();
        $this->getJson('/api/v1/pages', ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
        $this->getJson('/api/v1/pages', ['Authorization' => 'Bearer correct-token-value'])->assertOk();

        config(['cms.api_token' => '']);

        $this->getJson('/api/v1/pages', ['Authorization' => 'Bearer '])->assertUnauthorized();
    }

    public function test_api_token_guessing_is_rate_limited(): void
    {
        config(['cms.api_token' => 'correct-token-value']);

        foreach (range(1, 60) as $i) {
            $this->getJson('/api/v1/pages', ['Authorization' => 'Bearer wrong-token'])
                ->assertUnauthorized();
        }

        $this->getJson('/api/v1/pages', ['Authorization' => 'Bearer wrong-token'])
            ->assertStatus(429);
    }
}
