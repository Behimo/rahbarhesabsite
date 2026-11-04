<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CourseDetailResource;
use App\Http\Resources\Api\V1\CourseResource;
use App\Services\Api\CourseCatalog;
use App\Services\Auth\AppUserResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(private CourseCatalog $catalog, private AppUserResolver $users) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return CourseResource::collection(
            $this->catalog->paginate($request, $this->users->optional($request))
        );
    }

    public function show(Request $request, string $slug): CourseDetailResource
    {
        return new CourseDetailResource(
            $this->catalog->findPublished($slug, $this->users->optional($request))
        );
    }
}
