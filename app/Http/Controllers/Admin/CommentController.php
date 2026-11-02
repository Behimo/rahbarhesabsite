<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPost;
use App\Models\Comment;
use App\Models\ShopProduct;
use App\Services\Comments\Actions\ModerateCommentAction;
use App\Services\Comments\CommentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: Comment::STATUS_PENDING;
        $allowed = array_keys(Comment::statusLabels());

        if (! in_array($status, $allowed, true) && $status !== 'all') {
            $status = Comment::STATUS_PENDING;
        }

        $query = Comment::query()
            ->with(['user', 'commentable'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', '%'.$search.'%')
                    ->orWhere('author_name', 'like', '%'.$search.'%')
                    ->orWhere('author_email', 'like', '%'.$search.'%');
            });
        }

        if ($type = $request->input('type')) {
            $map = [
                'post' => CmsPost::class,
                'product' => ShopProduct::class,
            ];
            if (isset($map[$type])) {
                $query->where('commentable_type', $map[$type]);
            }
        }

        $counts = Comment::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.comments.index', [
            'comments' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'counts' => $counts,
            'statuses' => Comment::statusLabels(),
        ]);
    }

    public function settings(CommentSettings $settings): View
    {
        return view('admin.comments.settings', [
            'settings' => $settings->all(),
        ]);
    }

    public function updateSettings(Request $request, CommentSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'types.cms_post' => ['nullable', 'boolean'],
            'types.shop_product' => ['nullable', 'boolean'],
            'default_status' => ['required', 'in:open,closed'],
            'require_approval' => ['nullable', 'boolean'],
            'auto_approve_known_users' => ['nullable', 'boolean'],
            'links_force_moderation' => ['nullable', 'boolean'],
            'auth_required' => ['nullable', 'boolean'],
            'guests_allowed' => ['nullable', 'boolean'],
            'allow_replies' => ['nullable', 'boolean'],
            'max_depth' => ['required', 'integer', 'min:1', 'max:3'],
            'min_length' => ['required', 'integer', 'min:1', 'max:500'],
            'max_length' => ['required', 'integer', 'min:10', 'max:10000'],
            'edit_window_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'allow_delete_own' => ['nullable', 'boolean'],
            'order' => ['required', 'in:newest,oldest'],
            'per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'show_count_on_listings' => ['nullable', 'boolean'],
            'closed_message' => ['required', 'string', 'max:500'],
            'pending_message' => ['required', 'string', 'max:500'],
            'rejected_message' => ['required', 'string', 'max:500'],
            'throttle_per_minute' => ['required', 'integer', 'min:1', 'max:60'],
            'honeypot_enabled' => ['nullable', 'boolean'],
            'max_links' => ['required', 'integer', 'min:0', 'max:20'],
            'block_on_max_links' => ['required', 'in:spam,pending,reject'],
            'duplicate_window_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'max_repeat_chars' => ['required', 'integer', 'min:0', 'max:50'],
            'profanity_filter_enabled' => ['nullable', 'boolean'],
            'profanity_action' => ['required', 'in:reject,spam,pending'],
            'profanity_words' => ['nullable', 'string'],
            'blacklist_words' => ['nullable', 'string'],
            'blacklist_action' => ['required', 'in:spam,pending,reject'],
            'blocked_ips' => ['nullable', 'string'],
            'auto_close_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'soft_score_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $boolKeys = [
            'enabled', 'require_approval', 'auto_approve_known_users', 'links_force_moderation',
            'auth_required', 'guests_allowed', 'allow_replies', 'allow_delete_own',
            'show_count_on_listings', 'honeypot_enabled', 'profanity_filter_enabled',
        ];

        foreach ($boolKeys as $key) {
            $validated[$key] = $request->boolean($key);
        }

        $validated['types'] = [
            'cms_post' => $request->boolean('types.cms_post'),
            'shop_product' => $request->boolean('types.shop_product'),
        ];

        foreach (['profanity_words', 'blacklist_words', 'blocked_ips'] as $listKey) {
            $lines = preg_split('/\r\n|\r|\n/', (string) ($validated[$listKey] ?? '')) ?: [];
            $validated[$listKey] = array_values(array_unique(array_filter(array_map('trim', $lines))));
        }

        $validated['word_lists_managed'] = true;

        $settings->save($validated);

        return back()->with('success', 'تنظیمات دیدگاه‌ها ذخیره شد.');
    }

    public function approve(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->approve($comment, request()->user());

        return back()->with('success', 'دیدگاه تأیید شد.');
    }

    public function reject(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->reject($comment, request()->user());

        return back()->with('success', 'دیدگاه رد شد.');
    }

    public function spam(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->markSpam($comment, request()->user());

        return back()->with('success', 'دیدگاه به‌عنوان اسپم علامت خورد.');
    }

    public function trash(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->trash($comment, request()->user());

        return back()->with('success', 'دیدگاه به زباله‌دان منتقل شد.');
    }

    public function restore(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->restore($comment, request()->user());

        return back()->with('success', 'دیدگاه بازیابی شد.');
    }

    public function destroy(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->destroy($comment, request()->user());

        return back()->with('success', 'دیدگاه حذف شد.');
    }

    public function pin(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->togglePin($comment, request()->user());

        return back()->with('success', 'وضعیت پین به‌روز شد.');
    }

    public function reply(Request $request, Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $moderator->replyAsAdmin($comment, $request->user(), $validated['body']);

        return back()->with('success', 'پاسخ ادمین ثبت شد.');
    }

    public function banUser(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->banUserFromComments($comment, request()->user());

        return back()->with('success', 'کاربر از ارسال دیدگاه محروم شد.');
    }

    public function blockIp(Comment $comment, ModerateCommentAction $moderator): RedirectResponse
    {
        $moderator->blockIp($comment, request()->user());

        return back()->with('success', 'آی‌پی مسدود شد.');
    }

    public function bulk(Request $request, ModerateCommentAction $moderator): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:comments,id'],
            'action' => ['required', 'in:approve,reject,spam,trash,destroy'],
        ]);

        $comments = Comment::query()->whereIn('id', $validated['ids'])->get();
        $admin = $request->user();

        foreach ($comments as $comment) {
            match ($validated['action']) {
                'approve' => $moderator->approve($comment, $admin),
                'reject' => $moderator->reject($comment, $admin),
                'spam' => $moderator->markSpam($comment, $admin),
                'trash' => $moderator->trash($comment, $admin),
                'destroy' => $moderator->destroy($comment, $admin),
            };
        }

        return back()->with('success', 'عملیات گروهی انجام شد.');
    }
}
