<?php

namespace Tests\Feature;

use App\Models\AccountingLaw;
use App\Models\CafeQuestion;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\CmsMedia;
use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Models\CmsPopup;
use App\Models\CmsPost;
use App\Models\CmsProduct;
use App\Models\CmsRedirect;
use App\Models\CmsSidebar;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\Order;
use App\Models\Role;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Models\Tag;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Smoke matrix for named GET routes (and a few critical auth redirects).
 * Deeper behavior lives in domain Feature tests; this catches missing/403/500 regressions.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFixtures();
    }

    public function test_every_named_get_route_returns_an_expected_status(): void
    {
        $admin = $this->fixtures['admin'];
        $user = $this->fixtures['user'];
        $editor = $this->fixtures['editor'];

        $failures = [];

        foreach ($this->namedGetRoutes() as $route) {
            $name = $route->getName();
            if ($this->shouldSkip($name)) {
                continue;
            }

            $parameters = $this->parametersFor($route);
            if ($parameters === null) {
                $failures[] = "{$name}: missing fixture parameters";

                continue;
            }

            try {
                $url = route($name, $parameters, false);
            } catch (\Throwable $e) {
                $failures[] = "{$name}: cannot build URL ({$e->getMessage()})";

                continue;
            }

            $actor = $this->actorFor($name);
            $expected = $this->expectedStatuses($name, $actor);

            $pending = match ($actor) {
                'admin' => $this->actingAs($admin),
                'user' => $this->actingAs($user),
                'editor' => $this->actingAs($editor),
                default => $this,
            };

            $response = $pending->get($url);
            $status = $response->getStatusCode();

            if (! in_array($status, $expected, true)) {
                $failures[] = sprintf(
                    '%s as %s => %d (expected %s) [%s]',
                    $name,
                    $actor,
                    $status,
                    implode('/', $expected),
                    $url
                );
            }
        }

        $this->assertSame([], $failures, "Route smoke failures:\n".implode("\n", $failures));
    }

    public function test_named_route_inventory_is_not_empty(): void
    {
        $count = count($this->namedGetRoutes());
        $this->assertGreaterThan(80, $count, 'Expected a substantial named GET route inventory.');
    }

    /** @return list<LaravelRoute> */
    private function namedGetRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! is_string($name) || $name === '') {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    private function shouldSkip(string $name): bool
    {
        $prefixes = [
            'storage.',
            'ignition.',
            'debugbar.',
            'livewire.',
            'sanctum.',
            'telescope.',
            'horizon.',
            'pulse.',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        // Closure redirects under a name prefix leave trailing dots (e.g. "courses.").
        if (str_ends_with($name, '.')) {
            return true;
        }

        // Generated with optional missing query params / file downloads needing signed URLs.
        return in_array($name, [
            'courses.lesson.file',
            'courses.lesson.download',
            'checkout.callback',
            'admin.pages.revisions.restore',
            'admin.posts.revisions.restore',
            'panel.notifications.read',
            'admin.export', // JSON download covered in RbacRegressionTest
        ], true);
    }

    private function actorFor(string $name): string
    {
        if (str_starts_with($name, 'admin.')) {
            if (in_array($name, ['admin.login'], true)) {
                return 'guest';
            }

            return 'admin';
        }

        if (str_starts_with($name, 'panel.')
            || str_starts_with($name, 'checkout.')
            || in_array($name, [
                'courses.enroll-free',
                'courses.learn',
                'cafe-soal.ask',
            ], true)
        ) {
            return 'user';
        }

        return 'guest';
    }

    /** @return list<int> */
    private function expectedStatuses(string $name, string $actor): array
    {
        if ($name === 'login' || $name === 'admin.login') {
            return [200, 302];
        }

        if (str_starts_with($name, 'admin.') && $actor === 'guest') {
            return [302];
        }

        if (str_starts_with($name, 'panel.') && $actor === 'guest') {
            return [302];
        }

        // Auth-gated course/checkout pages for guests.
        if ($actor === 'guest' && in_array($name, [
            'courses.enroll-free',
            'courses.learn',
            'checkout.index',
            'checkout.success',
            'checkout.retry',
            'cafe-soal.ask',
        ], true)) {
            return [302];
        }

        // Public content may 404 if catch-all slug misses; seeded fixtures target 200.
        if (str_starts_with($name, 'api.')) {
            return [200, 401, 403];
        }

        // SEO redirects and auth pages may use permanent redirects.
        return [200, 301, 302];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parametersFor(LaravelRoute $route): ?array
    {
        $params = [];

        foreach ($route->parameterNames() as $parameter) {
            $value = $this->fixtureValue($parameter, $route->getName() ?? '');
            if ($value === null) {
                return null;
            }
            $params[$parameter] = $value;
        }

        return $params;
    }

    private function fixtureValue(string $parameter, string $routeName): mixed
    {
        $map = [
            'page' => fn () => str_starts_with($routeName, 'admin.pages')
                ? $this->fixtures['cms_page']
                : 1,
            'post' => fn () => $this->fixtures['post'],
            'law' => fn () => $this->fixtures['law'],
            'course' => fn () => str_starts_with($routeName, 'admin.courses')
                ? $this->fixtures['shop_course']
                : $this->fixtures['course_slug'],
            'slug' => fn () => $this->slugFor($routeName),
            'lessonSlug' => fn () => $this->fixtures['lesson_slug'],
            'category' => fn () => match (true) {
                str_starts_with($routeName, 'admin.categories') => $this->fixtures['category'],
                str_starts_with($routeName, 'courses.category') => $this->fixtures['product_category_slug'],
                default => $this->fixtures['category_slug'],
            },
            'tag' => fn () => str_starts_with($routeName, 'admin.tags')
                ? $this->fixtures['tag']
                : $this->fixtures['tag_slug'],
            'product' => fn () => str_starts_with($routeName, 'admin.products')
                ? $this->fixtures['cms_product']
                : (str_starts_with($routeName, 'products.')
                    ? $this->fixtures['course_slug']
                    : $this->fixtures['shop_course']),
            'item' => fn () => $this->fixtures['cart_item_id'],
            'order' => fn () => $this->fixtures['order'],
            'license' => fn () => $this->fixtures['license'],
            'coupon' => fn () => $this->fixtures['coupon'],
            'menu' => fn () => $this->fixtures['menu'],
            'medium' => fn () => $this->fixtures['media'],
            'message' => fn () => $this->fixtures['message'],
            'question' => fn () => $this->fixtures['question'],
            'answer' => fn () => $this->fixtures['answer_id'],
            'redirect' => fn () => $this->fixtures['redirect'],
            'popup' => fn () => $this->fixtures['popup'],
            'sidebar' => fn () => $this->fixtures['sidebar'],
            'user' => fn () => $this->fixtures['user'],
            'role' => fn () => $this->fixtures['role'],
            'section' => fn () => $this->fixtures['section'],
            'lesson' => fn () => $this->fixtures['lesson'],
            'evolution' => fn () => $this->fixtures['evolution_id'],
            'revision' => fn () => $this->fixtures['revision_id'],
            'id' => fn () => $this->idFor($routeName),
            'path' => fn () => 'style.css',
        ];

        if (! isset($map[$parameter])) {
            // Numeric pagination segments often named oddly; default to 1.
            if (in_array($parameter, ['page'], true)) {
                return 1;
            }

            return null;
        }

        return $map[$parameter]();
    }

    private function slugFor(string $routeName): string
    {
        return match (true) {
            str_contains($routeName, 'courses') || str_contains($routeName, 'product') => $this->fixtures['course_slug'],
            str_contains($routeName, 'laws') => $this->fixtures['law_slug'],
            str_contains($routeName, 'cafe-soal') => $this->fixtures['question_slug'],
            str_contains($routeName, 'blog') => $this->fixtures['post_slug'],
            default => $this->fixtures['page_slug'],
        };
    }

    private function idFor(string $routeName): int
    {
        return match (true) {
            str_contains($routeName, 'posts') => $this->fixtures['trashed_post_id'],
            str_contains($routeName, 'laws') => $this->fixtures['trashed_law_id'],
            default => $this->fixtures['post']->id,
        };
    }

    private function seedFixtures(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->syncRoles([AccessCatalog::ROLE_ADMIN]);

        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        $category = Category::factory()->post()->create(['slug' => 'cat-smoke']);
        $productCategory = Category::factory()->product()->create(['slug' => 'product-cat-smoke']);
        $tag = Tag::query()->create(['name' => 'Smoke Tag', 'slug' => 'smoke-tag']);

        $post = CmsPost::factory()->published()->create([
            'slug' => 'smoke-post',
            'title' => 'Smoke Post',
        ]);
        $post->syncCategories([$category->id], $category->id);
        $post->syncTags([$tag->id]);

        $trashedPost = CmsPost::factory()->published()->create(['slug' => 'trashed-post']);
        $trashedPost->delete();

        $page = CmsPage::query()->create([
            'slug' => 'smoke-page',
            'title' => 'Smoke Page',
            'is_published' => true,
            'status' => 'published',
            'is_system' => false,
            'published_at' => now(),
        ]);

        $law = AccountingLaw::factory()->published()->create(['slug' => 'smoke-law']);
        $trashedLaw = AccountingLaw::factory()->published()->create(['slug' => 'trashed-law']);
        $trashedLaw->delete();

        $shopCourse = ShopProduct::factory()->course()->create([
            'slug' => 'smoke-course',
            'title' => 'Smoke Course',
            'price' => 100_000,
            'is_published' => true,
        ]);
        $shopCourse->syncCategories([$productCategory->id], $productCategory->id);
        $course = Course::factory()->create([
            'shop_product_id' => $shopCourse->id,
            'instructor_id' => null,
        ]);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'title' => 'Section 1',
            'sort_order' => 1,
        ]);
        $lesson = CourseLesson::factory()->preview()->create([
            'section_id' => $section->id,
            'slug' => 'smoke-lesson',
            'title' => 'Smoke Lesson',
            'sort_order' => 1,
        ]);

        CourseEnrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => CourseEnrollment::STATUS_ACTIVE,
            'source' => CourseEnrollment::SOURCE_PURCHASE,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'SMOKE-ORD-1',
            'status' => Order::STATUS_PAID,
            'subtotal' => 100_000,
            'discount' => 0,
            'total' => 100_000,
            'paid_at' => now(),
        ]);

        $license = SpotplayerLicense::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'order_id' => $order->id,
            'license_key' => 'SMOKE-KEY',
            'status' => SpotplayerLicense::STATUS_ISSUED,
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'SMOKE10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $menu = CmsMenu::query()->create([
            'name' => 'Smoke Menu',
            'slug' => 'smoke-menu',
            'location' => 'header',
        ]);

        $media = CmsMedia::query()->create([
            'disk' => 'public',
            'path' => 'cms/smoke.jpg',
            'filename' => 'smoke.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
        ]);

        $message = ContactMessage::query()->create([
            'name' => 'Smoke',
            'email' => 'smoke@example.com',
            'subject' => 'Hello',
            'message' => 'Smoke contact message body.',
            'is_read' => false,
        ]);

        $question = CafeQuestion::query()->create([
            'user_id' => $user->id,
            'title' => 'Smoke cafe question title',
            'slug' => 'smoke-cafe-question',
            'body' => 'Body long enough for cafe question smoke fixture.',
            'status' => CafeQuestion::STATUS_ANSWERED,
            'answered_at' => now(),
        ]);
        $answer = $question->answers()->create([
            'user_id' => $admin->id,
            'body' => 'Official smoke answer with enough detail.',
            'is_official' => true,
        ]);

        $redirect = CmsRedirect::query()->create([
            'from_path' => '/smoke-from',
            'to_path' => '/smoke-to',
            'status_code' => 301,
            'is_active' => true,
            'from_query' => '',
            'match_query' => false,
        ]);

        $popup = CmsPopup::query()->create([
            'name' => 'Smoke Popup',
            'title' => 'Smoke Popup Title',
            'body' => 'Smoke popup body',
            'is_active' => true,
            'target_mode' => 'pages',
            'pages' => ['home'],
            'audience' => 'all',
            'frequency' => 'session',
            'delay_seconds' => 0,
        ]);

        $sidebar = CmsSidebar::query()->create([
            'name' => 'Smoke Sidebar',
            'title' => 'Smoke Sidebar Title',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['home'],
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_LATEST,
            'limit' => 5,
        ]);

        $cmsProduct = CmsProduct::query()->create([
            'slug' => 'cms-smoke-product',
            'title' => 'CMS Smoke Product',
            'is_published' => true,
        ]);

        $role = Role::query()->where('name', AccessCatalog::ROLE_EDITOR)->firstOrFail();

        // Ensure cart route params exist when needed, then clear auth for guest smoke cases.
        $this->actingAs($user)->post(route('cart.add', $shopCourse));
        $cartItemId = CartItem::query()->where('user_id', $user->id)->value('id');
        $this->app['auth']->logout();
        $this->app['session']->flush();

        $this->fixtures = [
            'admin' => $admin,
            'editor' => $editor,
            'user' => $user->fresh(),
            'post' => $post,
            'post_slug' => $post->slug,
            'trashed_post_id' => $trashedPost->id,
            'cms_page' => $page,
            'page_slug' => $page->slug,
            'law' => $law,
            'law_slug' => $law->slug,
            'trashed_law_id' => $trashedLaw->id,
            'shop_course' => $shopCourse,
            'course_slug' => $shopCourse->slug,
            'section' => $section,
            'lesson' => $lesson,
            'lesson_slug' => $lesson->slug,
            'category' => $category,
            'category_slug' => $category->slug,
            'product_category_slug' => $productCategory->slug,
            'tag' => $tag,
            'tag_slug' => $tag->slug,
            'order' => $order,
            'license' => $license,
            'coupon' => $coupon,
            'menu' => $menu,
            'media' => $media,
            'message' => $message,
            'question' => $question,
            'question_slug' => $question->slug,
            'answer_id' => $answer->id,
            'redirect' => $redirect,
            'popup' => $popup,
            'sidebar' => $sidebar,
            'cms_product' => $cmsProduct,
            'role' => $role,
            'cart_item_id' => $cartItemId,
            'evolution_id' => 1,
            'revision_id' => 1,
        ];
    }
}
