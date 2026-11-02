<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreCommentRequest;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Services\Comments\Actions\StoreCommentAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, StoreCommentAction $action): RedirectResponse
    {
        $commentable = $this->resolveCommentable(
            (string) $request->validated('commentable_type'),
            (int) $request->validated('commentable_id'),
        );

        try {
            $result = $action->handle($request->user(), $commentable, [
                'body' => $request->validated('body'),
                'parent_id' => $request->validated('parent_id'),
                'author_name' => $request->input('author_name'),
                'author_email' => $request->input('author_email'),
                'honeypot' => $request->input('website'),
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()])->withFragment('comments');
        }

        return back()
            ->with('comment_success', $result['message'])
            ->withFragment('comments');
    }

    private function resolveCommentable(string $type, int $id): Model
    {
        return match ($type) {
            'post' => CmsPost::query()->published()->findOrFail($id),
            'product' => ShopProduct::query()
                ->published()
                ->where('type', ShopProduct::TYPE_COURSE)
                ->findOrFail($id),
            default => abort(404),
        };
    }
}
