<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'pages' => CmsPage::query()->count(),
                'posts' => CmsPost::query()->count(),
                'products' => \App\Models\CmsProduct::query()->count(),
                'courses' => \App\Models\ShopProduct::query()->where('type', 'course')->count(),
                'orders' => \App\Models\Order::query()->count(),
                'users' => \App\Models\User::query()->count(),
                'messages' => \App\Models\ContactMessage::query()->where('is_read', false)->count(),
                'total_messages' => \App\Models\ContactMessage::query()->count(),
            ],
            'recentMessages' => \App\Models\ContactMessage::query()->latest()->take(5)->get(),
            'recentPosts' => CmsPost::query()->latest()->take(5)->get(),
        ]);
    }
}
