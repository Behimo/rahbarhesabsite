<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseLessonRequest;
use App\Http\Requests\Admin\CourseRequest;
use App\Http\Requests\Admin\CourseSectionRequest;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private TaxonomyService $taxonomy) {}

    public function index(): View
    {
        $courses = ShopProduct::query()
            ->where('type', ShopProduct::TYPE_COURSE)
            ->with('course.instructor')
            ->orderBy('sort_order')
            ->get();

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        $instructors = User::query()->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_ADMIN])->get();

        $this->taxonomy->ensureDefaults();

        return view('admin.courses.form', [
            'product' => new ShopProduct(['type' => ShopProduct::TYPE_COURSE, 'is_published' => false]),
            'course' => new Course,
            'instructors' => $instructors,
            'terms' => $this->taxonomy->termsFor('course-category'),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $validated = $request->payload();

        $product = ShopProduct::query()->create($validated['product']);
        $product->course()->create($validated['course']);
        $this->taxonomy->syncTerms($product, $request->validated('term_ids') ?? []);

        return redirect()->route('admin.courses.edit', $product)->with('success', 'دوره ایجاد شد.');
    }

    public function edit(ShopProduct $course): View
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $course->load(['course.sections.lessons']);
        $instructors = User::query()->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_ADMIN])->get();

        $this->taxonomy->ensureDefaults();

        return view('admin.courses.form', [
            'product' => $course,
            'course' => $course->course,
            'instructors' => $instructors,
            'terms' => $this->taxonomy->termsFor('course-category'),
            'selectedTerms' => $course->taxonomyTerms()->pluck('cms_taxonomy_terms.id')->all(),
        ]);
    }

    public function update(CourseRequest $request, ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $validated = $request->payload();
        $course->update($validated['product']);
        $course->course->update($validated['course']);
        $this->taxonomy->syncTerms($course, $request->validated('term_ids') ?? []);

        return back()->with('success', 'دوره به‌روزرسانی شد.');
    }

    public function destroy(ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'دوره حذف شد.');
    }

    public function storeSection(CourseSectionRequest $request, ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $course->course->sections()->create($request->validated());

        return back()->with('success', 'فصل اضافه شد.');
    }

    public function updateSection(CourseSectionRequest $request, ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);

        $section->update($request->validated());

        return back()->with('success', 'فصل به‌روزرسانی شد.');
    }

    public function destroySection(ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);
        $section->delete();

        return back()->with('success', 'فصل حذف شد.');
    }

    public function storeLesson(CourseLessonRequest $request, ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);

        $section->lessons()->create($request->lessonAttributes());

        return back()->with('success', 'درس اضافه شد.');
    }

    public function updateLesson(CourseLessonRequest $request, ShopProduct $course, CourseLesson $lesson): RedirectResponse
    {
        abort_if($lesson->section->course_id !== $course->course->id, 404);

        $lesson->update($request->lessonAttributes());

        return back()->with('success', 'درس به‌روزرسانی شد.');
    }

    public function destroyLesson(ShopProduct $course, CourseLesson $lesson): RedirectResponse
    {
        abort_if($lesson->section->course_id !== $course->course->id, 404);
        $lesson->delete();

        return back()->with('success', 'درس حذف شد.');
    }
}
