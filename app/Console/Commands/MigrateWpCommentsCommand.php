<?php

namespace App\Console\Commands;

use App\Models\CmsPost;
use App\Models\Comment;
use App\Models\User;
use App\Services\Comments\CommentSanitizer;
use App\Services\WordpressMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateWpCommentsCommand extends Command
{
    protected $signature = 'comments:migrate-wp {--chunk=250 : Batch size}';

    protected $description = 'Migrate approved WordPress post comments into polymorphic comments';

    public function handle(WordpressMigrationService $migration, CommentSanitizer $sanitizer): int
    {
        if (! $migration->connectionReady()) {
            $this->error('اتصال وردپرس در دسترس نیست.');

            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $posts = CmsPost::query()
            ->whereNotNull('wp_post_id')
            ->get(['id', 'wp_post_id']);

        if ($posts->isEmpty()) {
            $this->warn('هیچ مقاله مهاجرت‌شده‌ای با wp_post_id یافت نشد.');

            return self::SUCCESS;
        }

        $postMap = $posts->pluck('id', 'wp_post_id');

        $query = DB::connection('wordpress')->table('comments')
            ->where('comment_approved', '1')
            ->whereIn('comment_type', ['', 'comment'])
            ->whereIn('comment_post_ID', $postMap->keys()->all())
            ->orderBy('comment_ID');

        $parentsByWp = [];

        $query->chunkById($chunk, function ($rows) use ($postMap, $sanitizer, &$created, &$updated, &$skipped, &$parentsByWp) {
            $userMap = User::query()
                ->whereIn('wp_id', collect($rows)->pluck('user_id')->filter()->unique()->all())
                ->pluck('id', 'wp_id');

            foreach ($rows as $row) {
                $postId = $postMap[(int) $row->comment_post_ID] ?? null;

                if (! $postId) {
                    $skipped++;

                    continue;
                }

                $body = $sanitizer->sanitize((string) $row->comment_content);

                if ($body === '') {
                    $skipped++;

                    continue;
                }

                $parentId = null;
                $wpParent = (int) $row->comment_parent;
                if ($wpParent > 0) {
                    $parentId = $parentsByWp[$wpParent]
                        ?? Comment::query()->where('wp_comment_id', $wpParent)->value('id');
                }

                $comment = Comment::query()->firstOrNew(['wp_comment_id' => (int) $row->comment_ID]);
                $exists = $comment->exists;

                $comment->forceFill([
                    'user_id' => $userMap[$row->user_id] ?? null,
                    'author_name' => mb_substr((string) $row->comment_author, 0, 191) ?: null,
                    'author_email' => mb_substr((string) $row->comment_author_email, 0, 191) ?: null,
                    'author_ip' => mb_substr((string) $row->comment_author_IP, 0, 45) ?: null,
                    'commentable_type' => CmsPost::class,
                    'commentable_id' => $postId,
                    'parent_id' => $parentId,
                    'body' => $body,
                    'status' => Comment::STATUS_APPROVED,
                    'approved_at' => $row->comment_date ?: now(),
                    'created_at' => $row->comment_date ?: now(),
                    'updated_at' => $row->comment_date ?: now(),
                    'wp_comment_id' => (int) $row->comment_ID,
                ]);

                Comment::withoutTimestamps(fn () => $comment->save());
                $parentsByWp[(int) $row->comment_ID] = $comment->id;

                $exists ? $updated++ : $created++;
            }
        }, 'comment_ID');

        $this->info("ایجاد: {$created} | به‌روزرسانی: {$updated} | رد شده: {$skipped}");

        return self::SUCCESS;
    }
}
