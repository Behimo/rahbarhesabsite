<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_contact_fields_are_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'first_name' => '',
                'last_name' => '',
                'phone' => '',
                'message' => '',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'message']);

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_phone_longer_than_twenty_characters_is_rejected(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'first_name' => 'سارا',
                'last_name' => 'رضایی',
                'phone' => str_repeat('1', 21),
                'message' => 'سلام، برای مشاوره تماس بگیرید.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHasErrors(['phone']);

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_valid_contact_message_is_stored(): void
    {
        $this->from(route('contact'))
            ->post(route('contact.store'), [
                'first_name' => 'سارا',
                'last_name' => 'رضایی',
                'phone' => '09120000000',
                'message' => 'سلام، برای مشاوره تماس بگیرید.',
            ])
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success', 'درخواست شما ثبت شد. کارشناسان مجموعه در ساعات کاری با شما تماس می‌گیرند.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'سارا رضایی',
            'phone' => '09120000000',
            'email' => '',
            'message' => 'سلام، برای مشاوره تماس بگیرید.',
            'is_read' => false,
        ]);
    }
}
