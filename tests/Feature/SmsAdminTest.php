<?php

namespace Tests\Feature;

use App\Jobs\SendOtpSmsJob;
use App\Models\CmsSetting;
use App\Models\Role;
use App\Models\User;
use App\Services\Sms\SmsConfigurationException;
use App\Services\Sms\SmsDriverRegistry;
use App\Services\Sms\SmsManager;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeOtpSmsDriver;
use Tests\TestCase;

class SmsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_links_to_sms_driver_settings(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.sms.index').'"', false)
            ->assertSee('درایورهای پیامک');
    }

    public function test_settings_page_has_a_sms_management_shortcut(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.sms.index').'" class="btn btn-outline-primary w-100">مدیریت پیامک</a>', false);
    }

    public function test_admin_saves_an_encrypted_key_and_never_sees_it_in_the_form(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->put(route('admin.sms.update'), $this->form())->assertRedirect()->assertSessionHasNoErrors();

        $ciphertext = CmsSetting::query()->where('key', 'sms.secret.smsir.api_key')->value('value');
        $this->assertNotSame('synthetic-test-key', $ciphertext);
        $this->assertSame('synthetic-test-key', Crypt::decryptString($ciphertext));
        $this->assertStringNotContainsString('synthetic-test-key', CmsSetting::query()->where('key', 'sms.driver_state')->value('value'));

        $this->get(route('admin.sms.index'))
            ->assertOk()
            ->assertSee('کلید ذخیره شده است')
            ->assertDontSee('synthetic-test-key')
            ->assertSee('value=""', false);
    }

    public function test_blank_key_retains_it_and_replacement_is_encrypted(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form())->assertSessionHasNoErrors();

        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['api_key' => '', 'template_id' => 732, 'code_parameter' => 'OtpCode'],
        ]]))->assertSessionHasNoErrors();

        $registry = app(SmsDriverRegistry::class);
        $this->assertSame('synthetic-test-key', $registry->configuration('smsir')['api_key']);
        $this->assertSame(732, $registry->configuration('smsir')['template_id']);

        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['api_key' => 'synthetic-replacement', 'template_id' => 732, 'code_parameter' => 'OtpCode'],
        ]]))->assertSessionHasNoErrors();
        $this->assertSame('synthetic-replacement', $registry->configuration('smsir')['api_key']);
    }

    public function test_invalid_update_does_not_flash_the_key_or_change_settings(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form())->assertSessionHasNoErrors();

        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['api_key' => 'synthetic-new-secret', 'template_id' => 'otp', 'code_parameter' => 'OtpCode'],
        ]]))->assertSessionHasErrors('settings.smsir.template_id');

        $this->assertNull(session('_old_input.settings.smsir.api_key'));
        $this->assertSame('otp', session('_old_input.settings.smsir.template_id'));
        $this->assertSame('OtpCode', session('_old_input.settings.smsir.code_parameter'));
        $this->assertSame('synthetic-test-key', app(SmsDriverRegistry::class)->configuration('smsir')['api_key']);
        $this->get(route('admin.sms.index'))->assertDontSee('synthetic-new-secret');
    }

    public function test_incomplete_enabled_driver_and_unknown_fields_are_rejected(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['api_key' => '', 'template_id' => '', 'code_parameter' => ''],
        ]]))->assertSessionHasErrors('settings.smsir.api_key');

        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['api_key' => 'synthetic-test-key', 'template_id' => 731, 'code_parameter' => 'OtpCode', 'unexpected' => 'bad'],
        ]]))->assertSessionHasErrors('settings.smsir');

        $this->assertDatabaseMissing('cms_settings', ['key' => 'sms.driver_state']);
    }

    public function test_template_parameter_is_case_sensitive_and_distinct_from_the_api_key_title(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['template_id' => 249450, 'code_parameter' => 'CODE'],
        ]]))->assertSessionHasNoErrors();

        $this->assertSame('CODE', app(SmsDriverRegistry::class)->configuration('smsir')['code_parameter']);
        $this->get(route('admin.sms.index'))->assertOk()
            ->assertSee('value="CODE"', false)
            ->assertSee('#CODE#')
            ->assertDontSee('شناسه عددی قالب otp');
    }

    public function test_invalid_parameter_retains_nonsecret_input_but_never_credentials(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->from(route('admin.sms.index'))->put(route('admin.sms.update'), $this->form(['settings' => [
            'smsir' => ['template_id' => 249450, 'code_parameter' => '#CODE#'],
            'unknown' => ['api_key' => 'synthetic-unknown-secret'],
        ]]))->assertSessionHasErrors('settings.smsir.code_parameter');

        $this->assertNull(session('_old_input.settings.smsir.api_key'));
        $this->assertNull(session('_old_input.settings.unknown'));
        $this->get(route('admin.sms.index'))->assertOk()
            ->assertSee('value="249450"', false)
            ->assertSee('value="#CODE#"', false)
            ->assertDontSee('synthetic-test-key')
            ->assertDontSee('synthetic-unknown-secret');
    }

    public function test_cache_repopulated_before_commit_is_invalidated_after_commit(): void
    {
        $registry = app(SmsDriverRegistry::class);
        $registry->save('smsir', ['smsir' => true], $this->form()['settings'], []);
        $previousState = CmsSetting::get('sms.driver_state');
        $previousKey = CmsSetting::get('sms.secret.smsir.api_key');

        DB::transaction(function () use ($registry, $previousState, $previousKey): void {
            $registry->save('smsir', ['smsir' => true], [
                'smsir' => ['api_key' => 'synthetic-replacement', 'template_id' => 249450, 'code_parameter' => 'CODE'],
            ], []);

            // Simulate another worker caching committed, old values during the write.
            Cache::put('cms_setting.sms.driver_state', $previousState, 3600);
            Cache::put('cms_setting.sms.secret.smsir.api_key', $previousKey, 3600);
        });

        $this->assertFalse(Cache::has('cms_setting.sms.driver_state'));
        $this->assertFalse(Cache::has('cms_setting.sms.secret.smsir.api_key'));
        $this->assertSame('CODE', $registry->configuration('smsir')['code_parameter']);
        $this->assertSame('synthetic-replacement', $registry->configuration('smsir')['api_key']);
    }

    public function test_clear_is_allowed_when_disabled_but_not_when_enabled(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form())->assertSessionHasNoErrors();

        $this->put(route('admin.sms.update'), $this->form([
            'settings' => ['smsir' => ['api_key' => '', 'template_id' => 731, 'code_parameter' => 'OtpCode']],
            'clear' => ['smsir' => ['api_key' => '1']],
        ]))->assertSessionHasErrors('settings.smsir.api_key');
        $this->assertSame('synthetic-test-key', app(SmsDriverRegistry::class)->configuration('smsir')['api_key']);

        $this->put(route('admin.sms.update'), $this->form([
            'default' => '',
            'enabled' => ['smsir' => '0'],
            'settings' => ['smsir' => ['api_key' => '', 'template_id' => 731, 'code_parameter' => 'OtpCode']],
            'clear' => ['smsir' => ['api_key' => '1']],
        ]))->assertSessionHasNoErrors();

        $this->assertNull(CmsSetting::query()->where('key', 'sms.secret.smsir.api_key')->value('value'));
        $this->expectException(SmsConfigurationException::class);
        app(SmsManager::class)->ensureConfigured();
    }

    public function test_admin_disable_overrides_environment_fallback_and_prevents_new_otps(): void
    {
        config()->set('sms.default', 'fake');
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form([
            'default' => '',
            'enabled' => ['smsir' => '0', 'fake' => '0'],
        ]))->assertSessionHasNoErrors();

        Queue::fake();
        auth()->logout();
        $this->from(route('login'))->post(route('login.otp'), ['phone' => '09121234567'])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('otp_verifications', 0);
        Queue::assertNotPushed(SendOtpSmsJob::class);
    }

    public function test_enabled_driver_may_be_left_unselected_but_selection_requires_enablement(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form(['default' => '']))->assertSessionHasNoErrors();

        $this->assertNull(app(SmsDriverRegistry::class)->selectedName());
        $this->assertTrue(app(SmsDriverRegistry::class)->isEnabled('smsir'));
        $this->expectException(SmsConfigurationException::class);
        app(SmsManager::class)->ensureConfigured();
    }

    public function test_selecting_a_disabled_driver_is_rejected_without_changing_selection(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form())->assertSessionHasNoErrors();

        $this->put(route('admin.sms.update'), $this->form([
            'enabled' => ['smsir' => '0'],
        ]))->assertSessionHasErrors('default');

        $this->assertSame('smsir', app(SmsDriverRegistry::class)->selectedName());
        $this->assertTrue(app(SmsDriverRegistry::class)->isEnabled('smsir'));
    }

    public function test_multiple_drivers_can_be_enabled_while_only_one_sends(): void
    {
        $this->actingAs($this->makeAdmin());
        $this->put(route('admin.sms.update'), $this->form([
            'default' => 'fake',
            'enabled' => ['smsir' => '1', 'fake' => '1'],
        ]))->assertSessionHasNoErrors();

        Http::preventStrayRequests();
        app(SmsManager::class)->sendOtp('+989121234567', '123456');
        $this->assertCount(1, app(FakeOtpSmsDriver::class)->sent);
        Http::assertNothingSent();

        $this->put(route('admin.sms.update'), $this->form([
            'settings' => ['smsir' => ['api_key' => '', 'template_id' => 731, 'code_parameter' => 'OtpCode']],
            'enabled' => ['smsir' => '1', 'fake' => '1'],
        ]))->assertSessionHasNoErrors();
        Http::fake(['api.sms.ir/v1/send/verify' => Http::response(['status' => 1], 200)]);

        app(SmsManager::class)->sendOtp('+989121234568', '654321');
        $this->assertCount(1, app(FakeOtpSmsDriver::class)->sent);
        Http::assertSentCount(1);
    }

    public function test_non_settings_user_cannot_view_or_update_sms_drivers(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole(AccessCatalog::ROLE_EDITOR);

        $this->actingAs($staff)->get(route('admin.sms.index'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.sms.update'), $this->form())->assertForbidden();
        $this->assertDatabaseMissing('cms_settings', ['key' => 'sms.driver_state']);
    }

    public function test_view_only_settings_user_cannot_write_credentials(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $role = Role::query()->create(['name' => 'sms_viewer', 'label' => 'SMS viewer', 'guard_name' => AccessCatalog::guard(), 'is_system' => false]);
        $role->syncPermissions([
            AccessCatalog::ACCESS_ADMIN,
            AccessCatalog::permission('settings', AccessCatalog::VIEW),
        ]);
        $viewer = User::factory()->create();
        $viewer->syncRoles(['sms_viewer']);

        $this->actingAs($viewer)->get(route('admin.sms.index'))->assertOk()->assertDontSee('ذخیره تنظیمات پیامک');
        $this->actingAs($viewer)->put(route('admin.sms.update'), $this->form())->assertForbidden();
    }

    private function form(array $changes = []): array
    {
        return array_replace_recursive([
            'default' => 'smsir',
            'enabled' => ['smsir' => '1'],
            'settings' => ['smsir' => [
                'api_key' => 'synthetic-test-key',
                'template_id' => 731,
                'code_parameter' => 'OtpCode',
            ]],
        ], $changes);
    }
}
