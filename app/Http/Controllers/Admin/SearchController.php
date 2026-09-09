<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $pages = $posts = $courses = collect();

        if ($q !== '') {
            $pages = CmsPage::query()->where('title', 'like', "%{$q}%")->limit(10)->get();
            $posts = CmsPost::query()->where('title', 'like', "%{$q}%")->limit(10)->get();
            $courses = ShopProduct::query()->where('title', 'like', "%{$q}%")->where('type', 'course')->limit(10)->get();
        }

        return view('admin.search.index', compact('q', 'pages', 'posts', 'courses'));
    }
}
