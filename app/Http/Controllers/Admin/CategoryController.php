<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Rules\EnglishSlug;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->has('type')) {
            return redirect()->route('admin.categories.index', ['type' => Category::TYPE_POST]);
        }

        $type = $this->type($request);

        return view('admin.categories.index', [
            'type' => $type,
            'categories' => $this->categories->flat($type),
        ]);
    }

    public function create(Request $request): View
    {
        $type = $this->type($request);
        $parentId = $request->integer('parent_id') ?: null;
        $parent = $parentId
            ? Category::query()->ofType($type)->whereKey($parentId)->first()
            : null;

        $category = new Category([
            'type' => $type,
            'parent_id' => $parent?->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return view('admin.categories.form', [
            'category' => $category,
            'type' => $type,
            'parents' => $this->parentOptions($type),
        ]);
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'type' => $category->type,
            'parents' => $this->parentOptions($category->type, $category),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $this->type($request);
        $validated = $this->validateCategory($request, $type);
        $validated['type'] = $type;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? ((int) Category::query()->where('type', $type)->max('sort_order') + 1);

        Category::query()->create($validated);

        return redirect()
            ->route('admin.categories.index', ['type' => $type])
            ->with('success', 'دسته‌بندی ایجاد شد.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category->type, $category);
        $validated['type'] = $category->type;
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index', ['type' => $category->type])
            ->with('success', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $type = $category->type;
        $category->delete();

        return redirect()
            ->route('admin.categories.index', ['type' => $type])
            ->with('success', 'دسته‌بندی حذف شد. زیردسته‌ها به سطح بالاتر منتقل شدند.');
    }

    /** @return Collection<int, Category> */
    private function parentOptions(string $type, ?Category $category = null): Collection
    {
        $excluded = collect();

        if ($category?->exists) {
            $excluded = $category->descendants()->pluck('id')->push($category->id);
        }

        return $this->categories->flat($type)
            ->reject(fn (Category $item) => $excluded->contains($item->id))
            ->values();
    }

    private function type(Request $request): string
    {
        return $request->string('type')->toString() === Category::TYPE_PRODUCT
            ? Category::TYPE_PRODUCT
            : Category::TYPE_POST;
    }

    /** @return array<string, mixed> */
    private function validateCategory(Request $request, string $type, ?Category $category = null): array
    {
        $parentId = $request->input('parent_id') ?: null;
        $request->merge(['parent_id' => $parentId]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:80',
                new EnglishSlug,
                Rule::unique('categories', 'slug')
                    ->ignore($category?->id)
                    ->where(function ($query) use ($type, $parentId) {
                        $query->where('type', $type)->whereNull('deleted_at');
                        $parentId
                            ? $query->where('parent_id', $parentId)
                            : $query->whereNull('parent_id');
                    }),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('type', $type)->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($category && $validated['parent_id']) {
            $parentId = (int) $validated['parent_id'];
            $invalid = $parentId === $category->id
                || $category->descendants()->whereKey($parentId)->exists();

            if ($invalid) {
                throw ValidationException::withMessages([
                    'parent_id' => 'دسته نمی‌تواند زیرمجموعهٔ خودش باشد.',
                ]);
            }
        }

        $parentSlug = $validated['parent_id']
            ? Category::query()->whereKey($validated['parent_id'])->value('full_slug')
            : null;
        $fullSlug = $parentSlug ? $parentSlug.'/'.$validated['slug'] : $validated['slug'];

        $fullTaken = Category::withTrashed()
            ->where('type', $type)
            ->where('full_slug', $fullSlug)
            ->when($category, fn ($query) => $query->where('id', '!=', $category->id))
            ->exists();

        if ($fullTaken) {
            throw ValidationException::withMessages([
                'slug' => 'این مسیر دسته قبلاً استفاده شده است.',
            ]);
        }

        $validated['parent_id'] = $validated['parent_id'] ?: null;

        return $validated;
    }
}
