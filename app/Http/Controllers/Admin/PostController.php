<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\CmsCategory;
use App\Models\CmsPost;
use App\Models\CmsPostRevision;
use App\Services\SiteDataService;
use App\Services\TaxonomyService;
use App\Services\ThemeService;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PostController extends Controller
{
    public function __construct(
        private TaxonomyService $taxonomy,
        private SiteDataService $siteData,
        private ThemeService $theme,
    ) {}

    public function index(Request $request): View
    {
        $this->taxonomy->ensureDefaults();

        $trashed = $request->boolean('trashed');

        $posts = CmsPost::query()
            ->with(['category', 'taxonomyTerms'])
            ->when($trashed, fn ($q) => $q->onlyTrashed())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('excerpt', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'draft') {
                    $q->where(function ($inner) {
                        $inner->where('is_published', false)->orWhere('status', CmsPost::STATUS_DRAFT);
                    });
                } elseif ($status === 'scheduled') {
                    $q->where('is_published', true)
                        ->where('published_at', '>', now());
                } elseif ($status === 'published') {
                    $q->published();
                }
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'categories' => CmsCategory::query()->orderBy('sort_order')->get(),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'category' => $request->input('category'),
                'status' => $request->string('status')->toString(),
                'trashed' => $trashed,
            ],
            'trashCount' => CmsPost::onlyTrashed()->count(),
        ]);
    }

    public function create(): View
    {
        $this->taxonomy->ensureDefaults();

        return view('admin.posts.form', $this->formData(new CmsPost([
            'author' => config('cms.site_name_fa', 'راهبر حساب'),
            'is_published' => false,
            'status' => CmsPost::STATUS_DRAFT,
        ])));
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = CmsPost::query()->create($request->postAttributes());
        $this->taxonomy->syncTerms($post, $request->validated('term_ids') ?? []);
        $this->storeRevision($post, 'ایجاد اولیه');

        return redirect()->route('admin.posts.edit', $post)->with('success', 'مقاله ایجاد شد.');
    }

    public function edit(CmsPost $post): View
    {
        $this->taxonomy->ensureDefaults();
        $post->load('taxonomyTerms');

        return view('admin.posts.form', $this->formData($post));
    }

    public function update(PostRequest $request, CmsPost $post): RedirectResponse
    {
        $post->update($request->postAttributes());
        $this->taxonomy->syncTerms($post, $request->validated('term_ids') ?? []);
        $this->storeRevision($post->fresh(), 'ذخیره تغییرات');

        return redirect()->route('admin.posts.edit', $post)->with('success', 'مقاله به‌روزرسانی شد.');
    }

    public function destroy(CmsPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'مقاله به سطل زباله منتقل شد.');
    }

    public function restore(int $id): RedirectResponse
    {
        $post = CmsPost::onlyTrashed()->findOrFail($id);
        $post->restore();

        return redirect()->route('admin.posts.edit', $post)->with('success', 'مقاله بازیابی شد.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $post = CmsPost::onlyTrashed()->findOrFail($id);
        $post->forceDelete();

        return redirect()->route('admin.posts.index', ['trashed' => 1])->with('success', 'مقاله برای همیشه حذف شد.');
    }

    public function duplicate(CmsPost $post): RedirectResponse
    {
        $clone = $post->replicate([
            'views', 'published_at', 'slug',
        ]);

        $clone->title = $post->title.' (کپی)';
        $clone->slug = PersianSlug::unique(
            $post->slug.'-copy',
            fn (string $slug) => CmsPost::withTrashed()->where('slug', $slug)->exists()
        );
        $clone->is_published = false;
        $clone->status = CmsPost::STATUS_DRAFT;
        $clone->published_at = null;
        $clone->views = 0;
        $clone->save();

        $clone->taxonomyTerms()->sync($post->taxonomyTerms()->pluck('cms_taxonomy_terms.id')->all());
        $this->storeRevision($clone, 'کپی از مقاله #'.$post->id);

        return redirect()->route('admin.posts.edit', $clone)->with('success', 'کپی مقاله ایجاد شد.');
    }

    public function preview(CmsPost $post): View
    {
        $post->load(['category', 'taxonomyTerms']);
        $related = CmsPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->take(3)
            ->get();

        $seo = [
            'title' => ($post->meta_title ?: $post->title).' | پیش‌نمایش',
            'description' => $post->meta_description ?: $post->excerpt,
            'robots' => 'noindex, nofollow',
        ];

        return $this->theme->view('pages.blog.show', array_merge(
            $this->siteData->sharedViewData(),
            [
                'post' => $post,
                'related' => $related,
                'seo' => $seo,
                'isPreview' => true,
            ]
        ));
    }

    public function revisions(CmsPost $post): View
    {
        return view('admin.posts.revisions', [
            'post' => $post,
            'revisions' => $post->revisions()->with('admin')->limit(30)->get(),
        ]);
    }

    public function restoreRevision(CmsPost $post, CmsPostRevision $revision): RedirectResponse
    {
        abort_unless($revision->post_id === $post->id, Response::HTTP_NOT_FOUND);

        $snapshot = $revision->snapshot ?? [];
        unset($snapshot['slug']);

        $post->fill($snapshot);
        $post->save();
        $this->storeRevision($post->fresh(), 'بازگردانی نسخه #'.$revision->id);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'نسخه بازگردانی شد.');
    }

    /** @return array<string, mixed> */
    private function formData(CmsPost $post): array
    {
        return [
            'post' => $post,
            'categories' => CmsCategory::query()->orderBy('sort_order')->get(),
            'tags' => $this->taxonomy->termsFor('post-tag'),
            'selectedTerms' => $post->exists
                ? $post->taxonomyTerms()->pluck('cms_taxonomy_terms.id')->all()
                : [],
        ];
    }

    private function storeRevision(CmsPost $post, string $note): void
    {
        CmsPostRevision::query()->create([
            'post_id' => $post->id,
            'admin_id' => auth('cms')->id(),
            'snapshot' => $post->revisionSnapshot(),
            'note' => $note,
        ]);
    }
}
