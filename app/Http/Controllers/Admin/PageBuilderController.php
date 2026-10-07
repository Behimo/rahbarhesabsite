<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BuilderSaveRequest;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Services\BlockRegistry;
use App\Services\BlockRenderer;
use App\Services\BuilderCanvasRenderer;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageBuilderController extends Controller
{
    public function __construct(
        private BlockRegistry $registry,
        private BlockRenderer $renderer,
        private BuilderCanvasRenderer $canvas,
        private CacheService $cache,
    ) {}

    public function edit(CmsPage $page): View
    {
        $blocks = $this->registry->all();
        $builderContent = $this->canvas->normalize($page->builder_content ?? ['blocks' => []], $blocks);

        return view('admin.pages.builder', [
            'page' => $page,
            'blocks' => $blocks,
            'builderContent' => $builderContent,
            'canvasHtml' => $this->canvas->render($builderContent, $blocks),
        ]);
    }

    public function save(BuilderSaveRequest $request, CmsPage $page): JsonResponse|RedirectResponse
    {
        $builderContent = $request->validated('builder_content');
        CmsPageRevision::query()->create([
            'page_id' => $page->id,
            'admin_id' => auth()->id(),
            'content' => $page->content,
            'builder_content' => $page->builder_content,
            'note' => $request->validated('note'),
        ]);

        $page->update([
            'builder_enabled' => true,
            'builder_content' => $builderContent,
        ]);

        $this->cache->flushContent();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('admin.pages.builder', $page)
            ->with('success', 'صفحه‌ساز ذخیره شد.');
    }

    public function preview(Request $request, CmsPage $page): JsonResponse
    {
        $content = $request->input('builder_content', $page->builder_content ?? ['blocks' => []]);
        $html = $this->renderer->render($content);

        return response()->json(['html' => $html]);
    }

    public function revisions(CmsPage $page): View
    {
        return view('admin.pages.revisions', [
            'page' => $page,
            'revisions' => $page->revisions()->with('admin')->latest()->limit(20)->get(),
        ]);
    }

    public function restore(CmsPage $page, CmsPageRevision $revision): JsonResponse|RedirectResponse
    {
        abort_unless($revision->page_id === $page->id, 404);

        $page->update([
            'builder_content' => $revision->builder_content,
            'content' => $revision->content,
        ]);

        $this->cache->flushContent();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.pages.builder', $page)->with('success', 'نسخه بازگردانی شد.');
    }
}
