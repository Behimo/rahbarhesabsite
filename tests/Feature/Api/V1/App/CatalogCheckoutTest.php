<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class CatalogCheckoutTest extends MobileApiTestCase
{
    public function test_catalog_hides_unpublished_courses_and_playback_secrets(): void
    {
        $visible = ShopProduct::factory()->course()->create(['slug' => 'visible-course', 'title' => 'دوره آشکار']);
        Course::factory()->withCurriculum(1, 1)->create([
            'shop_product_id' => $visible->id,
            'instructor_id' => null,
        ]);
        ShopProduct::factory()->course()->create(['slug' => 'hidden-course', 'is_published' => false]);

        $this->withHeaders($this->appHeaders())
            ->getJson('/api/v1/app/courses')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'visible-course'])
            ->assertJsonMissing(['slug' => 'hidden-course']);

        $this->withHeaders($this->appHeaders())
            ->getJson('/api/v1/app/courses/visible-course')
            ->assertOk()
            ->assertJsonMissingPath('data.sections.0.lessons.0.video_url')
            ->assertJsonMissing(['license_key']);
    }

    public function test_checkout_starts_payment_and_replays_the_same_idempotency_key(): void
    {
        Queue::fake();
        Http::fake([
            'https://gateway.zibal.ir/*' => Http::response(['result' => 100, 'trackId' => 998877], 200),
        ]);

        $user = User::factory()->create();
        $product = ShopProduct::factory()->course()->create(['price' => 150000, 'sale_price' => null]);
        Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);

        $headers = $this->appHeaders($user) + ['Idempotency-Key' => 'checkout-key-1'];
        $first = $this->withHeaders($headers)->postJson('/api/v1/app/orders', [
            'product_id' => $product->id,
        ])->assertCreated();

        $first->assertJsonPath('data.order.status', Order::STATUS_PENDING);
        $this->assertNotEmpty($first->json('data.payment_url'));
        $this->assertStringNotContainsString('license_key', $first->getContent());

        $this->withHeaders($headers)->postJson('/api/v1/app/orders', [
            'product_id' => $product->id,
        ])->assertOk();

        $this->assertSame(1, Order::query()->where('user_id', $user->id)->count());
        $this->assertSame(Order::CHANNEL_APP, Order::query()->first()->channel);
    }

    public function test_a_free_course_is_enrolled_without_a_gateway(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $product = ShopProduct::factory()->course()->create(['price' => 0, 'sale_price' => null]);
        $course = Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);

        $this->withHeaders($this->appHeaders($user) + ['Idempotency-Key' => 'free-course-1'])
            ->postJson('/api/v1/app/orders', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.order.status', Order::STATUS_PAID)
            ->assertJsonPath('data.payment_url', null);

        $this->assertTrue($user->fresh()->isEnrolledIn($course));
    }

    public function test_buying_an_owned_course_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = ShopProduct::factory()->course()->create();
        $course = Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);
        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $this->withHeaders($this->appHeaders($user) + ['Idempotency-Key' => 'owned-course-1'])
            ->postJson('/api/v1/app/orders', ['product_id' => $product->id])
            ->assertStatus(409)
            ->assertJsonPath('code', 'course_already_owned');
    }
    public function test_reusing_a_checkout_key_with_changed_inputs_is_rejected(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $product = ShopProduct::factory()->course()->create(['price' => 0, 'sale_price' => null]);
        Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);
        $headers = $this->appHeaders($user) + ['Idempotency-Key' => 'immutable-checkout'];
        $this->withHeaders($headers)->postJson('/api/v1/app/orders', ['product_id' => $product->id])->assertCreated();

        foreach ([['coupon_code' => 'changed'], ['gateway' => 'changed'], ['product_id' => $product->id + 1]] as $change) {
            $this->withHeaders($headers)->postJson('/api/v1/app/orders', array_replace(['product_id' => $product->id], $change))
                ->assertStatus(409)->assertJsonPath('code', 'idempotency_conflict');
        }
        $this->assertSame(1, $user->orders()->count());
    }

    public function test_catalog_loads_ownership_in_batches(): void
    {
        $user = User::factory()->create();
        $owned = null;
        for ($i = 0; $i < 6; $i++) {
            $product = ShopProduct::factory()->course()->create();
            $course = Course::factory()->create(['shop_product_id' => $product->id, 'instructor_id' => null]);
            if ($i === 0) {
                $owned = $product->id;
                $user->enrollments()->create(['course_id' => $course->id, 'enrolled_at' => now(), 'status' => 'active', 'source' => 'purchase']);
            }
        }
        $db = \Illuminate\Support\Facades\DB::connection();
        $db->enableQueryLog();
        $db->flushQueryLog();
        $page = app(\App\Services\Api\CourseCatalog::class)->paginate(\Illuminate\Http\Request::create('/'), $user);
        $queries = collect($db->getQueryLog());
        $db->disableQueryLog();
        $this->assertTrue($page->getCollection()->firstWhere('id', $owned)->is_owned);
        $this->assertSame(1, $queries->filter(fn ($query) => str_contains($query['query'], 'course_enrollments'))->count());
        $this->assertLessThanOrEqual(6, $queries->count());
    }

}
