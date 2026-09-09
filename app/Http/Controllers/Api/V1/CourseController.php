<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ShopProduct::query()->published()->where('type', 'course')->with('course')->paginate(20)
        );
    }

    public function show(string $slug): JsonResponse
    {
        $course = ShopProduct::query()
            ->published()
            ->where('slug', $slug)
            ->where('type', 'course')
            ->with(['course.sections.lessons'])
            ->firstOrFail();

        return response()->json($course);
    }
}
