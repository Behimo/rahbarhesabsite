<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_profile(): void
    {
        $this->get(route('panel.profile'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'سارا',
            'last_name' => 'محمدی',
            'name' => 'سارا محمدی',
            'phone' => '09123334444',
            'mobile' => '09123334444',
        ]);

        $this->actingAs($user)
            ->get(route('panel.profile'))
            ->assertOk()
            ->assertSee('پرونده من', false)
            ->assertSee('سارا محمدی', false)
            ->assertSee('09123334444', false);
    }

    public function test_user_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'phone' => '09121111111',
            'mobile' => '09121111111',
        ]);

        $this->actingAs($user)
            ->put(route('panel.profile.update'), [
                'first_name' => 'مریم',
                'last_name' => 'کاظمی',
                'email' => 'maryam@example.com',
                'phone' => '09125556666',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('مریم', $user->first_name);
        $this->assertSame('کاظمی', $user->last_name);
        $this->assertSame('مریم کاظمی', $user->name);
        $this->assertSame('maryam@example.com', $user->email);
        $this->assertSame('09125556666', $user->phone);
        $this->assertSame('09125556666', $user->mobile);
        $this->assertNull($user->mobile_verified_at);
    }

    public function test_profile_rejects_invalid_phone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('panel.profile'))
            ->put(route('panel.profile.update'), [
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'email' => $user->email,
                'phone' => '12345',
            ])
            ->assertRedirect(route('panel.profile'))
            ->assertSessionHasErrors('phone');
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('panel.profile.password'), [
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->passwordMatches('new-secret'));
    }

    public function test_password_update_requires_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('panel.profile'))
            ->put(route('panel.profile.password'), [
                'password' => 'new-secret',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect(route('panel.profile'))
            ->assertSessionHasErrors(['password'], null, 'password');
    }
}
