<?php

namespace App\Services\Api;

use App\Models\CourseEnrollment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CourseCatalog
{
    public function paginate(Request $request, ?User $viewer): LengthAwarePaginator
    {
        $search = trim((string) $request->query('search', ''));
        $search = str_replace(['%', '_'], ['\%', '\_'], $search);
        $category = trim((string) $request->query('category', ''));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $products = ShopProduct::query()
            ->published()
            ->whereIn('type', [ShopProduct::TYPE_COURSE, ShopProduct::TYPE_BUNDLE])
            ->with(['course.instructor'])
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($category !== '', function ($query) use ($category) {
                $query->whereHas('categories', fn ($categories) => $categories->where('slug', $category));
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);

        $this->markOwnership($products->getCollection(), $viewer);

        return $products;
    }

    public function findPublished(string $slug, ?User $viewer): ShopProduct
    {
        $product = ShopProduct::query()
            ->published()
            ->where('slug', $slug)
            ->whereIn('type', [ShopProduct::TYPE_COURSE, ShopProduct::TYPE_BUNDLE])
            ->with(['course.instructor', 'course.sections.lessons'])
            ->firstOrFail();

        $this->markOwnership(collect([$product]), $viewer);

        return $product;
    }

    private function markOwnership(Collection $products, ?User $viewer): void
    {
        if (! $viewer || $products->isEmpty()) {
            $products->each(fn (ShopProduct $product) => $product->setAttribute('is_owned', false));

            return;
        }

        (new \Illuminate\Database\Eloquent\Collection($products->all()))->loadMissing(['course', 'bundledCourses']);
        $courseIds = $products->flatMap(fn (ShopProduct $product) => $product->relatedCourses()->pluck('id'))->unique();
        $owned = $viewer->enrollments()
            ->whereIn('course_id', $courseIds)
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', CourseEnrollment::STATUS_ACTIVE))
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->pluck('course_id')->flip();

        foreach ($products as $product) {
            $courses = $product->relatedCourses();
            $product->setAttribute('is_owned', $courses->isNotEmpty() && $courses->every(fn ($course) => $owned->has($course->id)));
        }
    }
}
