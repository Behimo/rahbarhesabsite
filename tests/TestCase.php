<?php

namespace Tests;

use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeOtpSmsDriver;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config()->set('sms.default', 'fake');
        config()->set('sms.drivers.fake', [
            'class' => FakeOtpSmsDriver::class,
            'label' => 'Fake SMS',
            'fields' => [],
        ]);
        $this->app->instance(FakeOtpSmsDriver::class, new FakeOtpSmsDriver);
    }

    protected function makeAdmin(array $attributes = []): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create($attributes);
        $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

        return $admin;
    }
}
