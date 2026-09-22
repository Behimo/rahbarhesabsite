<?php

namespace App\Http\Controllers\Site;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\ShopProduct;
use App\Models\SpotplayerLicense;
use App\Services\OrderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use App\Services\SpotPlayerService;
use App\Services\TaxonomyService;
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

    public function index(Request $request, TaxonomyService $taxonomy): View
    {
        $type = $request->query('type');

        $courses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with(['course.instructor', 'taxonomyTerms'])
            ->when($request->query('category'), function ($q, $category) {
                $q->whereHas('taxonomyTerms', fn ($t) => $t->where('slug', $category));
            })
            ->when($request->query('q'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('subtitle', 'like', "%{$search}%");
                });
            })
            ->when($type === 'free', fn ($q) => $q->whereRaw('COALESCE(sale_price, price) = 0'))
            ->when($type === 'paid', fn ($q) => $q->whereRaw('COALESCE(sale_price, price) > 0'))
            ->orderBy('sort_order')
            ->get();

        $categories = $taxonomy->termsFor('course-category');

        $seo = $this->seo->meta([
            'title' => 'دوره‌های آموزشی حسابداری | '.config('cms.site_name_fa'),
            'description' => 'دوره‌های کاربردی حسابداری و مالیات — آموزش عملی ویژه بازار کار ایران.',
            'keywords' => 'دوره حسابداری, آموزش مالیات, اظهارنامه, راهبر حساب',
        ]);

        return $this->render('pages.courses.index', compact('courses', 'categories', 'seo'));
    }

    public function show(string $slug): View
    {
        $product = ShopProduct::query()
            ->published()
            ->where('slug', $slug)
            ->where('type', ShopProduct::TYPE_COURSE)
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

        $seo = $this->seo->meta([
            'title' => $product->meta_title ?: ($product->title.' | '.config('cms.site_name_fa')),
            'description' => $product->meta_description ?: $product->description,
            'keywords' => $product->meta_keywords,
            'og_image' => $product->featured_image,
        ]);

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
                    ['name' => 'خانه', 'url' => route('home')],
                    ['name' => 'دوره‌ها', 'url' => route('courses.index')],
                    ['name' => $product->title, 'url' => route('courses.show', $product->slug)],
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

        $this->orders->enrollUser(auth()->user(), $product->course, null, \App\Models\CourseEnrollment::SOURCE_FREE);

        $message = $product->course?->spotplayer_course_id
            ? 'ثبت‌نام انجام شد. دسترسی اسپات‌پلیر به‌زودی آماده می‌شود.'
            : 'ثبت‌نام در دوره انجام شد.';

        return redirect()->route('courses.show', $slug)->with('success', $message);
    }
}
