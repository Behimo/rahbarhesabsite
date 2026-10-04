<?php

namespace Tests;

use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function makeAdmin(array $attributes = []): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create($attributes);
        $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

        return $admin;
    }
}
