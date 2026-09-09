<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            CmsPage::query()->published()->orderBy('sort_order')->paginate(20)
        );
    }

    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::query()->published()->where('slug', $slug)->firstOrFail();

        return response()->json($page);
    }
}
