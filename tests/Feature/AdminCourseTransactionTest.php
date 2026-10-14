<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminCourseTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_creation_rolls_back_product_when_course_creation_fails(): void
    {
        $admin = $this->makeAdmin(['email' => 'course-transaction-create@test.com']);
        Course::creating(function (): void {
            throw new RuntimeException('Injected course creation failure.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->post(route('admin.courses.store'), $this->coursePayload('atomic-create'));
            $this->fail('The injected exception should abort course creation.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected course creation failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('shop_products', ['slug' => 'atomic-create']);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_course_update_rolls_back_product_when_course_update_fails(): void
    {
        $admin = $this->makeAdmin(['email' => 'course-transaction-update@test.com']);
        $product = ShopProduct::query()->create([
            'slug' => 'atomic-update',
            'title' => 'عنوان اولیه',
            'price' => 1000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => false,
        ]);
        $course = $product->course()->create(['level' => 'beginner']);

        Course::updating(function (): void {
            throw new RuntimeException('Injected course update failure.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->put(
                route('admin.courses.update', $product),
                $this->coursePayload('atomic-update', 'عنوان تغییریافته')
            );
            $this->fail('The injected exception should abort course update.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected course update failure.', $exception->getMessage());
        }

        $this->assertSame('عنوان اولیه', $product->fresh()->title);
        $this->assertSame('beginner', $course->fresh()->level);
    }

    /** @return array<string, mixed> */
    private function coursePayload(string $slug, string $title = 'دورهٔ آزمایشی'): array
    {
        return [
            'slug' => $slug,
            'title' => $title,
            'price' => 1000,
            'level' => 'advanced',
        ];
    }
}
