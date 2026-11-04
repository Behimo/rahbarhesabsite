<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use App\Services\Payments\PaymentReturnRedirector;

class LibraryAndReturnTest extends MobileApiTestCase
{
    public function test_only_the_owner_can_read_an_order_or_a_license(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = ShopProduct::factory()->course()->create();
        $course = Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
            'spotplayer_course_id' => 'sp-1',
        ]);
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'order_number' => 'ORD-APP-1',
            'status' => Order::STATUS_PAID,
            'channel' => Order::CHANNEL_APP,
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'currency' => 'IRT',
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'shop_product_id' => $product->id,
            'title' => $product->title,
            'price' => 1000,
            'quantity' => 1,
            'metadata' => ['course_ids' => [$course->id]],
        ]);
        CourseEnrollment::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
        SpotplayerLicense::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'license_key' => 'SPOT-SECRET-KEY',
            'spot_url' => 'https://app.spotplayer.ir/watch',
            'status' => SpotplayerLicense::STATUS_ISSUED,
            'issued_at' => now(),
        ]);

        $this->withHeaders($this->appHeaders($owner))
            ->getJson('/api/v1/app/orders/ORD-APP-1')
            ->assertOk()
            ->assertJsonMissing(['SPOT-SECRET-KEY']);

        $this->withHeaders($this->appHeaders($intruder))
            ->getJson('/api/v1/app/orders/ORD-APP-1')
            ->assertNotFound();

        $this->withHeaders($this->appHeaders($owner))
            ->getJson('/api/v1/app/me/courses')
            ->assertOk()
            ->assertJsonMissing(['SPOT-SECRET-KEY'])
            ->assertJsonPath('data.0.license_status', SpotplayerLicense::STATUS_ISSUED);

        $this->withHeaders($this->appHeaders($intruder))
            ->postJson('/api/v1/app/me/courses/'.$course->id.'/license')
            ->assertNotFound();

        $this->withHeaders($this->appHeaders($owner))
            ->postJson('/api/v1/app/me/courses/'.$course->id.'/license')
            ->assertOk()
            ->assertJsonPath('data.license_key', 'SPOT-SECRET-KEY')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_a_pending_license_returns_accepted_without_a_key(): void
    {
        $owner = User::factory()->create();
        $product = ShopProduct::factory()->course()->create();
        $course = Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
            'spotplayer_course_id' => 'sp-2',
        ]);
        CourseEnrollment::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);
        SpotplayerLicense::query()->create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'status' => SpotplayerLicense::STATUS_PENDING,
        ]);

        $this->withHeaders($this->appHeaders($owner))
            ->postJson('/api/v1/app/me/courses/'.$course->id.'/license')
            ->assertStatus(202)
            ->assertJsonPath('code', 'license_pending')
            ->assertJsonMissing(['license_key']);
    }

    public function test_app_payment_callback_returns_to_the_app_without_a_license(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-APP-CB',
            'status' => Order::STATUS_PENDING,
            'channel' => Order::CHANNEL_APP,
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'currency' => 'IRT',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => 'zibal',
            'authority' => 'track-1',
            'amount' => 1000,
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $this->mock(PaymentGatewayManager::class, function ($mock) use ($payment) {
            $mock->shouldReceive('knows')->once()->andReturn(true);
            $mock->shouldReceive('verifyCallback')->once()->andReturn($payment);
        });

        $this->get('/checkout/callback?gateway=zibal&trackId=track-1&success=1')
            ->assertRedirect(route('checkout.app-return', [
                'order' => 'ORD-APP-CB',
                'status' => 'success',
            ]));

        $this->get(route('checkout.app-return', ['order' => 'ORD-APP-CB', 'status' => 'success']))
            ->assertOk()
            ->assertSee('ORD-APP-CB')
            ->assertSee('rahbarhesab://payment?order=ORD-APP-CB&status=success')
            ->assertDontSee('license');

        $failed = app(PaymentReturnRedirector::class)->afterCallback($order, false);
        $this->assertStringContainsString('status=failed', $failed->getTargetUrl());
    }
    public function test_bundle_license_status_considers_every_course(): void
    {
        $order = new Order;
        $item = new \App\Models\OrderItem(['metadata' => ['course_ids' => [10, 20]]]);
        $item->setRelation('order', $order);
        foreach (['pending', 'failed', 'revoked', 'issued'] as $status) {
            $order->setRelation('appLicenses', collect([
                10 => new SpotplayerLicense(['status' => 'issued']),
                20 => new SpotplayerLicense(['status' => $status]),
            ]));
            $data = (new \App\Http\Resources\Api\V1\OrderItemResource($item))->resolve();
            $this->assertSame($status, $data['license_status']);
        }
        $order->setRelation('appLicenses', collect([10 => new SpotplayerLicense(['status' => 'issued'])]));
        $this->assertSame('pending', (new \App\Http\Resources\Api\V1\OrderItemResource($item))->resolve()['license_status']);
    }

}
