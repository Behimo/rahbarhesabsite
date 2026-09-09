<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsPost;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            CmsPost::query()->published()->orderByDesc('published_at')->paginate(20)
        );
    }

    public function show(string $slug): JsonResponse
    {
        $post = CmsPost::query()->published()->where('slug', $slug)->firstOrFail();

        return response()->json($post);
    }
}
