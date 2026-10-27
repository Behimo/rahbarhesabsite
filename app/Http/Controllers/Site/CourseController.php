<?php

namespace App\Http\Controllers\Site;

use App\Models\Category;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Services\CategoryService;
use App\Services\OrderService;
use App\Services\PermalinkService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Services\SpotPlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CourseController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private OrderService $orders,
        private SpotPlayerService $spotPlayer,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function index(Request $request, CategoryService $categories): View
    {
        $permalinks = app(PermalinkService::class);

        $page = $request->route('page');
        $page = $page !== null ? max(1, (int) $page) : null;
        $category = $request->route('category');

        if ($page !== null) {
            $request->merge(['page' => $page]);
        }

        $type = $request->query('type');
        $categoryPath = is_string($category) && $category !== ''
            ? trim($category, '/')
            : $request->string('category')->toString();
        $categoryModel = $categoryPath !== ''
            ? $categories->findByPath(Category::TYPE_PRODUCT, $categoryPath)
            : null;

        abort_if($categoryPath !== '' && ! $categoryModel, 404);

        $categoryIds = $categoryModel ? $categoryModel->descendantsAndSelf()->pluck('id') : collect();

        $courses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.instructor', 'categories'])
            ->when($categoryModel, fn ($q) => $q->whereHas(
                'categories',
                fn ($related) => $related->whereIn('categories.id', $categoryIds)
            ))
            ->when($request->query('q'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('subtitle', 'like', "%{$search}%");
                });
            })
            ->when($type === 'free', fn ($q) => $q->whereRaw('COALESCE(sale_price, price) = 0'))
            ->when($type === 'paid', fn ($q) => $q->whereRaw('COALESCE(sale_price, price) > 0'))
            ->orderBy('sort_order')
            ->paginate(config('seo.per_page.products'))->withQueryString();

        abort_if($courses->currentPage() > $courses->lastPage(), 404);

        $categoryList = $categories->flat(Category::TYPE_PRODUCT, true);

        $canonicalPath = $categoryModel
            ? $permalinks->category($categoryModel, $request->integer('page') ?: null)
            : $permalinks->productIndex($request->integer('page') ?: null);

        $seo = [
            'title' => $categoryModel
                ? ($categoryModel->meta_title ?: $this->seo->wpTitle($categoryModel->name.' Archives', $courses->currentPage(), $courses->lastPage()))
                : $this->seo->wpTitle('دوره های آموزش حسابداری', $courses->currentPage(), $courses->lastPage()),
            'description' => $categoryModel
                ? ($categoryModel->meta_description ?: ($categoryModel->description ?: 'دوره‌های کاربردی حسابداری و مالیات — آموزش عملی ویژه بازار کار ایران.'))
                : 'دوره های آموزش حسابداری ارائه شده توسط تیم آموزش حسابداری راهبر حساب بر اساس جدیدترین تغییرات مالی و مالیاتی بوده و دارای پشتیبانی 7/24 میباشد.',
            'keywords' => 'دوره حسابداری, آموزش مالیات, اظهارنامه, راهبر حساب',
            'canonical' => $permalinks->absolute($canonicalPath),
            'robots' => $categoryModel?->robots,
        ];

        return $this->render('pages.courses.index', [
            'courses' => $courses,
            'categories' => $categoryList,
            'activeCategory' => $categoryModel?->full_slug ?: $categoryModel?->slug,
            'seo' => $seo,
            'structuredData' => [
                $this->seo->breadcrumbSchema([
                    ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
                    ['name' => 'دوره‌ها', 'url' => route('courses.index')],
                    ...($categoryModel ? [['name' => $categoryModel->name, 'url' => $permalinks->absolute($permalinks->category($categoryModel))]] : []),
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $product = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('slug', rawurldecode($slug))
                    ->orWhere('slug', rawurlencode($slug));
            })
            ->with(['course.sections.lessons', 'course.instructor'])
            ->firstOrFail();

        $course = $product->course;
        $user = auth()->user();
        $isEnrolled = $user && $user->isEnrolledIn($course);

        $license = null;
        $spotUrl = null;

        if ($isEnrolled && $course) {
            $license = SpotplayerLicense::query()
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($license?->status === SpotplayerLicense::STATUS_ISSUED) {
                $spotUrl = $license->spot_url ?: $this->spotPlayer->embedUrl($course, $user);
            }
        }

        $permalinks = app(PermalinkService::class);

        $seo = [
            'title' => $product->meta_title ?: $this->seo->wpTitle($product->title),
            'description' => $product->meta_description ?: $this->seo->excerptDescription($product->description),
            'keywords' => $product->meta_keywords ?? null,
            'og_image' => $product->og_image ?: $product->featured_image,
            'canonical' => $product->canonical_url ?: $permalinks->productAbsolute($product),
            'robots' => $product->robots ?? null,
        ];

        return $this->render('pages.courses.show', [
            'product' => $product,
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'license' => $license,
            'spotUrl' => $spotUrl,
            'seo' => $seo,
            'structuredData' => [
                $this->seo->courseSchema($product, $course),
                $this->seo->breadcrumbSchema([
                    ['name' => config('seo.defaults.home_label'), 'url' => route('home')],
                    ['name' => 'دوره‌ها', 'url' => route('courses.index')],
                    ['name' => $product->title, 'url' => $permalinks->productAbsolute($product)],
                ]),
            ],
        ]);
    }

    public function refreshLicense(string $slug): RedirectResponse
    {
        $product = ShopProduct::query()
            ->where('slug', $slug)
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with('course')
            ->firstOrFail();

        $course = $product->course;
        $user = auth()->user();

        if (! $user || ! $course || ! $user->isEnrolledIn($course)) {
            abort(403);
        }

        if (! $course->spotplayer_course_id) {
            return back()->with('error', 'این دوره به اسپات‌پلیر متصل نیست. با پشتیبانی تماس بگیرید.');
        }

        $license = $this->spotPlayer->issueLicense($user, $course);

        if ($license->status === SpotplayerLicense::STATUS_ISSUED) {
            return back()->with('success', 'دسترسی اسپات‌پلیر آماده شد.');
        }

        if ($license->status === SpotplayerLicense::STATUS_PENDING) {
            return back()->with('success', 'دسترسی در حال آماده‌سازی است. چند لحظه دیگر دوباره امتحان کنید.');
        }

        return back()->with('error', 'صدور لایسنس ناموفق بود. لطفاً با پشتیبانی تماس بگیرید.');
    }

    public function learn(string $slug, ?string $lessonSlug = null): View|RedirectResponse
    {
        $product = ShopProduct::query()
            ->where('slug', $slug)
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.sections.lessons'])
            ->firstOrFail();

        $course = $product->course;
        $user = auth()->user();

        $allLessons = $course->sections->flatMap->lessons;
        $currentLesson = $lessonSlug
            ? $allLessons->firstWhere('slug', $lessonSlug)
            : $allLessons->first();

        if (! $currentLesson) {
            return redirect()->route('courses.learn', $slug);
        }

        $canAccess = $user && $user->isEnrolledIn($course);
        if (! $canAccess && ! $currentLesson->is_free_preview) {
            return redirect()->route('courses.show', $slug)
                ->with('error', 'برای مشاهده درس‌ها باید در دوره ثبت‌نام کنید.');
        }

        $progress = collect();
        if ($user) {
            $progress = LessonProgress::query()
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $allLessons->pluck('id'))
                ->get()
                ->keyBy('lesson_id');
        }

        $spotplayerUrl = ($user && $currentLesson->video_provider === 'spotplayer')
            ? $this->spotPlayer->embedUrl($course, $user, $currentLesson->spotplayer_item_id)
            : null;

        return $this->render('pages.courses.learn', compact(
            'product', 'course', 'currentLesson', 'allLessons', 'progress', 'spotplayerUrl', 'canAccess'
        ));
    }

    public function previewLesson(string $slug, string $lessonSlug): View|RedirectResponse
    {
        $product = ShopProduct::query()
            ->where('slug', $slug)
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.sections.lessons'])
            ->firstOrFail();

        $lesson = $product->course->sections->flatMap->lessons->firstWhere('slug', $lessonSlug);

        if (! $lesson?->is_free_preview) {
            abort(403);
        }

        return $this->render('pages.courses.preview', [
            'product' => $product,
            'lesson' => $lesson,
        ]);
    }

    public function downloadLesson(string $slug, string $lessonSlug): RedirectResponse
    {
        $product = ShopProduct::query()->where('slug', $slug)->with('course.sections.lessons')->firstOrFail();
        $course = $product->course;
        $user = auth()->user();

        if (! $user || ! $user->isEnrolledIn($course)) {
            abort(403);
        }

        $lesson = $course->sections->flatMap->lessons->firstWhere('slug', $lessonSlug);

        if (! $lesson?->download_url) {
            abort(404);
        }

        $signedUrl = URL::temporarySignedRoute(
            'courses.lesson.file',
            now()->addMinutes(30),
            ['slug' => $slug, 'lessonSlug' => $lessonSlug]
        );

        return redirect()->to($signedUrl);
    }

    public function serveLessonFile(Request $request, string $slug, string $lessonSlug): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $product = ShopProduct::query()->where('slug', $slug)->with('course.sections.lessons')->firstOrFail();
        $lesson = $product->course->sections->flatMap->lessons->firstWhere('slug', $lessonSlug);

        if (! $lesson?->download_url) {
            abort(404);
        }

        return redirect()->away($lesson->download_url);
    }

    public function completeLesson(Request $request, string $slug, string $lessonSlug): RedirectResponse
    {
        $product = ShopProduct::query()->where('slug', $slug)->with('course')->firstOrFail();
        $course = $product->course;
        $user = auth()->user();

        if (! $user || ! $user->isEnrolledIn($course)) {
            abort(403);
        }

        $lesson = CourseLesson::query()
            ->whereIn('section_id', $course->sections()->pluck('id'))
            ->where('slug', $lessonSlug)
            ->firstOrFail();

        LessonProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );

        $this->orders->updateCourseProgress($user, $course);

        return back()->with('success', 'درس تکمیل شد.');
    }

    public function enrollFree(string $slug): RedirectResponse
    {
        $product = ShopProduct::query()
            ->where('slug', $slug)
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with('course')
            ->firstOrFail();

        if (! $product->isFree()) {
            return redirect()->route('cart.add', $product);
        }

        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'برای ثبت‌نام در دوره رایگان وارد شوید.');
        }

        $this->orders->enrollUser(auth()->user(), $product->course, null, CourseEnrollment::SOURCE_FREE);

        $message = $product->course?->spotplayer_course_id
            ? 'ثبت‌نام انجام شد. دسترسی اسپات‌پلیر به‌زودی آماده می‌شود.'
            : 'ثبت‌نام در دوره انجام شد.';

        return redirect()->route('courses.learn', $slug)->with('success', $message);
    }
}
