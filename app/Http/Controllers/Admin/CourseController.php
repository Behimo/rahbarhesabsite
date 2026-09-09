<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\TaxonomyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCourse($request);

        $product = ShopProduct::query()->create($validated['product']);
        $product->course()->create($validated['course']);
        $this->taxonomy->syncTerms($product, $request->input('term_ids', []));

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

    public function update(Request $request, ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $validated = $this->validateCourse($request, $course);
        $course->update($validated['product']);
        $course->course->update($validated['course']);
        $this->taxonomy->syncTerms($course, $request->input('term_ids', []));

        return back()->with('success', 'دوره به‌روزرسانی شد.');
    }

    public function destroy(ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'دوره حذف شد.');
    }

    public function storeSection(Request $request, ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $course->course->sections()->create($validated);

        return back()->with('success', 'فصل اضافه شد.');
    }

    public function updateSection(Request $request, ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $section->update($validated);

        return back()->with('success', 'فصل به‌روزرسانی شد.');
    }

    public function destroySection(ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);
        $section->delete();

        return back()->with('success', 'فصل حذف شد.');
    }

    public function storeLesson(Request $request, ShopProduct $course, CourseSection $section): RedirectResponse
    {
        abort_if($section->course_id !== $course->course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_provider' => ['nullable', 'in:aparat,youtube,vimeo,upload,spotplayer,download'],
            'download_url' => ['nullable', 'string', 'max:500'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
            'spotplayer_item_id' => ['nullable', 'string', 'max:100'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_free_preview'] = $request->boolean('is_free_preview');
        $section->lessons()->create($validated);

        return back()->with('success', 'درس اضافه شد.');
    }

    public function updateLesson(Request $request, ShopProduct $course, CourseLesson $lesson): RedirectResponse
    {
        abort_if($lesson->section->course_id !== $course->course->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'content' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'download_url' => ['nullable', 'string', 'max:500'],
            'video_provider' => ['nullable', 'in:aparat,youtube,vimeo,upload,spotplayer,download'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
            'spotplayer_item_id' => ['nullable', 'string', 'max:100'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_free_preview' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_free_preview'] = $request->boolean('is_free_preview');
        $lesson->update($validated);

        return back()->with('success', 'درس به‌روزرسانی شد.');
    }

    public function destroyLesson(ShopProduct $course, CourseLesson $lesson): RedirectResponse
    {
        abort_if($lesson->section->course_id !== $course->course->id, 404);
        $lesson->delete();

        return back()->with('success', 'درس حذف شد.');
    }

    private function validateCourse(Request $request, ?ShopProduct $product = null): array
    {
        $slugRule = ['required', 'string', 'max:100', 'alpha_dash'];
        $slugRule[] = $product
            ? 'unique:shop_products,slug,'.$product->id
            : 'unique:shop_products,slug';

        $productData = $request->validate([
            'slug' => $slugRule,
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $courseData = $request->validate([
            'instructor_id' => ['nullable', 'exists:users,id'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'what_you_learn' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
        ]);

        $productData['type'] = ShopProduct::TYPE_COURSE;
        $productData['is_published'] = $request->boolean('is_published');
        $courseData['what_you_learn'] = array_values(array_filter(array_map('trim', explode("\n", $courseData['what_you_learn'] ?? ''))));
        $courseData['requirements'] = array_values(array_filter(array_map('trim', explode("\n", $courseData['requirements'] ?? ''))));

        return ['product' => $productData, 'course' => $courseData];
    }
}
