<?php

namespace Tests\Feature;

use App\Models\CafeQuestion;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use App\Notifications\QuestionAnsweredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_all_panel_pages(): void
    {
        foreach ([
            'panel.dashboard',
            'panel.courses',
            'panel.orders',
            'panel.questions',
            'panel.notifications',
            'panel.profile',
        ] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_user_can_open_panel_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertOk();
    }

    public function test_panel_courses_only_shows_current_users_enrollments(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownProduct = $this->courseProduct('own-course');
        $otherProduct = $this->courseProduct('other-course');
        $this->enroll($user, $ownProduct->course);
        $this->enroll($other, $otherProduct->course);

        $this->actingAs($user)
            ->get(route('panel.courses'))
            ->assertOk()
            ->assertSee($ownProduct->title)
            ->assertDontSee($otherProduct->title);
    }

    public function test_panel_orders_only_shows_current_users_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ownOrder = $this->paidOrder($user, 'OWN-PANEL-1');
        $foreignOrder = $this->paidOrder($other, 'FOREIGN-PANEL-1');

        $this->actingAs($user)
            ->get(route('panel.orders'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($foreignOrder->order_number);
    }

    public function test_panel_questions_only_lists_current_users_questions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = CafeQuestion::query()->create([
            'user_id' => $user->id,
            'title' => 'پرسش اختصاصی من',
            'slug' => 'my-q-'.uniqid(),
            'body' => 'متن کافی برای پرسش آزمایشی کاربر جاری.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);
        $foreign = CafeQuestion::query()->create([
            'user_id' => $other->id,
            'title' => 'پرسش کاربر دیگر',
            'slug' => 'other-q-'.uniqid(),
            'body' => 'متن کافی برای پرسش آزمایشی کاربر دیگر.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get(route('panel.questions'))
            ->assertOk()
            ->assertSee($own->title)
            ->assertDontSee($foreign->title);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $question = CafeQuestion::query()->create([
            'user_id' => $user->id,
            'title' => 'پرسش برای اعلان',
            'slug' => 'notif-q-'.uniqid(),
            'body' => 'متن کافی برای پرسش آزمایشی اعلان.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);

        $user->notify(new QuestionAnsweredNotification($question));
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->from(route('panel.notifications'))
            ->post(route('panel.notifications.read-all'))
            ->assertRedirect(route('panel.notifications'))
            ->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $question = CafeQuestion::query()->create([
            'user_id' => $user->id,
            'title' => 'پرسش خواندن اعلان',
            'slug' => 'read-q-'.uniqid(),
            'body' => 'متن کافی برای پرسش آزمایشی خواندن اعلان.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);
        $user->notify(new QuestionAnsweredNotification($question));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('panel.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $question = CafeQuestion::query()->create([
            'user_id' => $owner->id,
            'title' => 'پرسش اعلان دیگران',
            'slug' => 'other-notif-'.uniqid(),
            'body' => 'متن کافی برای پرسش آزمایشی اعلان دیگران.',
            'status' => CafeQuestion::STATUS_PENDING,
        ]);
        $owner->notify(new QuestionAnsweredNotification($question));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($attacker)
            ->get(route('panel.notifications.read', $notification->id))
            ->assertNotFound();
    }

    private function courseProduct(string $slug): ShopProduct
    {
        $product = ShopProduct::factory()->course()->create([
            'slug' => $slug,
            'title' => 'Course '.$slug,
        ]);
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);

        return $product->fresh('course');
    }

    private function enroll(User $user, Course $course): void
    {
        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
    }

    private function paidOrder(User $user, string $orderNumber): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'status' => Order::STATUS_PAID,
            'subtotal' => 50_000,
            'discount' => 0,
            'total' => 50_000,
            'paid_at' => now(),
        ]);
    }
}
