<?php

namespace App\Http\Controllers\Site;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Models\ShopProduct;
use App\Services\OrderService;
use App\Services\SeoService;
use App\Services\SiteDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends SiteController
{
    public function __construct(
        SiteDataService $siteData,
        SeoService $seo,
        private OrderService $orders,
    ) {
        parent::__construct($siteData, $seo);
    }

    public function index(): View
    {
        $courses = ShopProduct::query()
            ->published()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with('course.instructor')
            ->orderBy('sort_order')
            ->get();

        $seo = $this->seo->meta([
            'title' => 'دوره‌های آموزشی',
            'description' => 'دوره‌های آموزشی آنلاین',
        ]);

        return $this->render('pages.courses.index', compact('courses', 'seo'));
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
        $isEnrolled = auth()->check() && auth()->user()->isEnrolledIn($course);

        $seo = $this->seo->meta([
            'title' => $product->meta_title ?: $product->title,
            'description' => $product->meta_description ?: $product->description,
        ]);

        return $this->render('pages.courses.show', compact('product', 'course', 'isEnrolled', 'seo'));
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

        if (! $user || ! $user->isEnrolledIn($course)) {
            return redirect()->route('courses.show', $slug)
                ->with('error', 'برای مشاهده درس‌ها باید در دوره ثبت‌نام کنید.');
        }

        $allLessons = $course->sections->flatMap->lessons;
        $currentLesson = $lessonSlug
            ? $allLessons->firstWhere('slug', $lessonSlug)
            : $allLessons->first();

        if (! $currentLesson) {
            return redirect()->route('courses.learn', $slug);
        }

        $progress = LessonProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $allLessons->pluck('id'))
            ->get()
            ->keyBy('lesson_id');

        return $this->render('pages.courses.learn', compact('product', 'course', 'currentLesson', 'allLessons', 'progress'));
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

        $this->orders->enrollUser(auth()->user(), $product->course);

        return redirect()->route('courses.learn', $slug)->with('success', 'ثبت‌نام در دوره انجام شد.');
    }
}
