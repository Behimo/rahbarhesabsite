<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RedirectImportRequest;
use App\Http\Requests\Admin\RedirectRequest;
use App\Models\CmsRedirect;
use App\Services\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(): View
    {
        $redirects = CmsRedirect::query()->latest()->paginate(30);

        return view('admin.redirects.index', compact('redirects'));
    }

    public function store(RedirectRequest $request): RedirectResponse
    {
        $data = $request->redirectAttributes();
        CmsRedirect::query()->create($data);
        $this->forgetLookup($data['from_path'], (string) ($data['from_query'] ?? ''));

        return back()->with('success', 'ریدایرکت افزوده شد.');
    }

    public function update(RedirectRequest $request, CmsRedirect $redirect): RedirectResponse
    {
        $oldPath = $redirect->from_path;
        $oldQuery = (string) ($redirect->from_query ?? '');
        $data = $request->redirectAttributes();
        $redirect->update($data);
        $this->forgetLookup($oldPath, $oldQuery);
        $this->forgetLookup($data['from_path'], (string) ($data['from_query'] ?? ''));

        return back()->with('success', 'ریدایرکت به‌روزرسانی شد.');
    }

    public function destroy(CmsRedirect $redirect): RedirectResponse
    {
        $path = $redirect->from_path;
        $query = (string) ($redirect->from_query ?? '');
        $redirect->delete();
        $this->forgetLookup($path, $query);

        return back()->with('success', 'ریدایرکت حذف شد.');
    }

    public function import(RedirectImportRequest $request): RedirectResponse
    {
        $result = app(\App\Services\RankMathRedirectImporter::class)->import((string) $request->input('rows'));

        app(CacheService::class)->flushContent();

        $message = "{$result['imported']} ریدایرکت وارد شد";
        if ($result['skipped'] > 0) {
            $message .= "، {$result['skipped']} رد شد";
        }
        if ($result['chains'] !== []) {
            $message .= '. هشدار زنجیره: '.implode(' | ', array_slice($result['chains'], 0, 3));
        }

        return back()->with('success', $message);
    }

    private function forgetLookup(string $path, string $query = ''): void
    {
        Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey($path));
        if ($query !== '') {
            Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey('q:'.$path.'?'.$query));
            Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey($path.'?'.$query));
        } else {
            Cache::forget(\App\Http\Middleware\HandleCmsRedirects::cacheKey('q:'.$path));
        }
    }
}
