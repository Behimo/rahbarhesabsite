<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\User;
use App\Rules\EnglishSlug;
use App\Services\CategoryService;
use App\Support\AccessCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private CategoryService $categories) {}

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
        $instructors = User::query()->role([AccessCatalog::ROLE_INSTRUCTOR, AccessCatalog::ROLE_ADMIN])->orderBy('name')->get();

        return view('admin.courses.form', [
            'product' => new ShopProduct(['type' => ShopProduct::TYPE_COURSE, 'is_published' => false]),
            'course' => new Course,
            'instructors' => $instructors,
            'categories' => $this->categories->flat(Category::TYPE_PRODUCT),
            'selectedCategoryIds' => [],
            'primaryCategoryId' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCourse($request);

        $product = ShopProduct::query()->create($validated['product']);
        $product->course()->create($validated['course']);
        $product->syncCategories($validated['category_ids'], $validated['primary_category_id']);

        return redirect()->route('admin.courses.edit', $product)->with('success', 'دوره ایجاد شد.');
    }

    public function edit(ShopProduct $course): View
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $course->load(['course.sections.lessons']);
        $instructors = User::query()->role([AccessCatalog::ROLE_INSTRUCTOR, AccessCatalog::ROLE_ADMIN])->orderBy('name')->get();

        $selected = $course->categories()->pluck('categories.id')->all();

        return view('admin.courses.form', [
            'product' => $course,
            'course' => $course->course,
            'instructors' => $instructors,
            'categories' => $this->categories->flat(Category::TYPE_PRODUCT),
            'selectedCategoryIds' => $selected,
            'primaryCategoryId' => $course->category?->id,
        ]);
    }

    public function update(Request $request, ShopProduct $course): RedirectResponse
    {
        abort_unless($course->type === ShopProduct::TYPE_COURSE, 404);

        $validated = $this->validateCourse($request, $course);
        $course->update($validated['product']);
        $course->course->update($validated['course']);
        $course->syncCategories($validated['category_ids'], $validated['primary_category_id']);

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
        $slugRule = ['required', 'string', 'max:100', new EnglishSlug];
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
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->where('type', Category::TYPE_PRODUCT)],
            'primary_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', Category::TYPE_PRODUCT)],
        ]);

        $courseData = $request->validate([
            'instructor_id' => ['nullable', 'exists:users,id'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'what_you_learn' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:100'],
        ]);

        $categoryIds = array_map('intval', $productData['category_ids'] ?? []);
        $primaryCategoryId = isset($productData['primary_category_id']) ? (int) $productData['primary_category_id'] : null;
        unset($productData['category_ids'], $productData['primary_category_id']);

        $productData['type'] = ShopProduct::TYPE_COURSE;
        $productData['stock'] = null;
        $productData['is_published'] = $request->boolean('is_published');
        $courseData['what_you_learn'] = array_values(array_filter(array_map('trim', explode("\n", $courseData['what_you_learn'] ?? ''))));
        $courseData['requirements'] = array_values(array_filter(array_map('trim', explode("\n", $courseData['requirements'] ?? ''))));

        return [
            'product' => $productData,
            'course' => $courseData,
            'category_ids' => $categoryIds,
            'primary_category_id' => $primaryCategoryId ?: null,
        ];
    }
}
