<?php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class PreviewTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->query('preview_theme');

        if (!is_string($slug) || $slug === '') {
            return $next($request);
        }

        if (!is_file(base_path('themes/' . $slug . '/theme.json'))) {
            abort(404);
        }

        $themeService = app(ThemeService::class);
        $themeService->setPreview($slug);
        $themeService->registerViews();

        return $next($request);
    }
}
