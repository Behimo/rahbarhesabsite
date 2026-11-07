<?php

namespace App\Services\Wordpress;

use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use App\Models\User;
use App\Services\ContentUrlRewriter;
use App\Services\PermalinkService;
use App\Support\AccessCatalog;
use Illuminate\Support\Facades\DB;

class QuestionImporter extends WordpressImporter
{
    /**
     * @return array{created: int, updated: int, answers: int, comments: int, redirects: int, conflicts: list<string>}
     */
    public function migrateQuestions(bool $dryRun = false, ?int $fallbackUserId = null, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'answers' => 0, 'comments' => 0, 'redirects' => 0, 'conflicts' => []];
        if ($fallbackUserId !== null && ! User::query()->whereKey($fallbackUserId)->exists()) {
            throw new \RuntimeException('کاربر fallback در Laravel پیدا نشد.');
        }
        $posts = $this->wp()->table('posts')->where('post_type', 'qa')->where('post_status', 'publish')->lazyById(250, 'ID');
        $rewriter = app(ContentUrlRewriter::class);
        foreach ($posts->chunk(250) as $batch) {
            $postMeta = $this->postMetaForIds($batch->pluck('ID')->all());
            $authors = User::query()->whereIn('wp_id', $batch->pluck('post_author')->unique()->all())->pluck('id', 'wp_id');
            foreach ($batch as $post) {
                $slug = $this->normalizeWpSlug((string) $post->post_name);
                $collision = CafeQuestion::withTrashed()->where('slug', $slug)->first();
                if ($slug === '' || mb_strlen($slug) > 191 || ($collision && (int) $collision->wp_post_id !== (int) $post->ID)) {
                    $stats['conflicts'][] = 'WP #'.$post->ID.': نامک قابل انتقال نیست یا متعلق به سؤال دیگری است: '.$slug;
                    $onProgress && $onProgress();

                    continue;
                }
                $question = CafeQuestion::withTrashed()->where('wp_post_id', $post->ID)->first() ?? $collision;
                $counter = $question ? 'updated' : 'created';
                if ($dryRun) {
                    $stats[$counter]++;
                    $onProgress && $onProgress();

                    continue;
                }
                $authorId = $authors[$post->post_author] ?? $fallbackUserId
                    ?? User::query()->whereHas('roles', fn ($query) => $query->where('name', AccessCatalog::ROLE_ADMIN)->where('guard_name', 'web'))->orderBy('id')->value('id');
                if (! $authorId) {
                    throw new \RuntimeException('نویسنده‌ی پرسش WP #'.$post->ID.' پیدا نشد؛ --fallback-user را با شناسه‌ی کاربر Laravel مشخص کنید.');
                }
                $meta = $postMeta[$post->ID] ?? [];
                $delta = DB::transaction(function () use ($post, $slug, $question, $authorId, $meta, $rewriter) {
                    $question ??= new CafeQuestion;
                    $question->fill([
                        'user_id' => $authorId, 'title' => $post->post_title, 'slug' => $slug,
                        'body' => $rewriter->rewrite((string) $post->post_content),
                        'status' => CafeQuestion::STATUS_ANSWERED, 'answered_at' => $post->post_date,
                        'rejected_at' => null, 'rejection_reason' => null,
                        'featured_image' => $this->featuredImageUrl($meta),
                        'video_url' => $this->normalizeMediaUrl(str_replace('www.dl.rahbarhesab.com', 'dl.rahbarhesab.com', (string) ($meta['reza_video_url'] ?? '')), 500),
                        'video_poster' => $this->normalizeMediaUrl(str_replace('www.dl.rahbarhesab.com', 'dl.rahbarhesab.com', (string) ($meta['reza_video_poster'] ?? '')), 500),
                        'meta_title' => null,
                    ]);
                    $question->wp_post_id = (int) $post->ID;
                    $question->deleted_at = null;
                    $question->created_at = $post->post_date;
                    $question->updated_at = $post->post_modified ?: $post->post_date;
                    if (empty($meta['rank_math_title'])) {
                        unset($meta['rank_math_facebook_title']);
                    }
                    $this->applyRankMathMeta($question, $meta, $post->post_excerpt ?? null);
                    CafeQuestion::withoutTimestamps(fn () => $question->save());
                    $answers = $comments = $redirects = 0;
                    if (trim((string) ($meta['_qa_answer'] ?? '')) !== '') {
                        $answer = CafeAnswer::query()->firstOrNew(['official_for_question_id' => $question->id]);
                        $answer->forceFill([
                            'cafe_question_id' => $question->id, 'user_id' => $authorId,
                            'body' => $rewriter->rewrite($meta['_qa_answer']), 'is_official' => true,
                            'parent_id' => null, 'created_at' => $post->post_date,
                            'updated_at' => $post->post_modified ?: $post->post_date,
                        ]);
                        CafeAnswer::withoutTimestamps(fn () => $answer->save());
                        $answers++;
                    }
                    $wpComments = $this->wp()->table('comments')->where('comment_post_ID', $post->ID)
                        ->where('comment_approved', '1')->whereIn('comment_type', ['', 'comment'])->orderBy('comment_ID')->get();
                    $commentUsers = User::query()->whereIn('wp_id', $wpComments->pluck('user_id')->filter()->unique()->all())->pluck('id', 'wp_id');
                    $parents = [];
                    foreach ($wpComments as $comment) {
                        $answer = CafeAnswer::query()->firstOrNew(['wp_comment_id' => (int) $comment->comment_ID]);
                        $answer->forceFill([
                            'cafe_question_id' => $question->id, 'user_id' => $commentUsers[$comment->user_id] ?? null,
                            'wp_comment_id' => (int) $comment->comment_ID,
                            'author_name' => mb_substr((string) $comment->comment_author, 0, 191),
                            'body' => (string) $comment->comment_content, 'is_official' => false,
                            'parent_id' => $parents[(int) $comment->comment_parent] ?? null,
                            'created_at' => $comment->comment_date, 'updated_at' => $comment->comment_date,
                        ]);
                        CafeAnswer::withoutTimestamps(fn () => $answer->save());
                        $parents[(int) $comment->comment_ID] = $answer->id;
                        $comments++;
                    }
                    $this->syncModelTags($question, (int) $post->ID, 'post_tag');
                    $question->refresh();
                    $question->answers_count = $question->answers()->count();
                    // Answer observers update the parent timestamp; preserve WordPress lastmod after all answers.
                    $question->updated_at = $post->post_modified ?: $post->post_date;
                    CafeQuestion::withoutTimestamps(fn () => $question->save());
                    $target = app(PermalinkService::class)->question($question);
                    $redirects += (int) $this->ensureRedirect('/?p='.$post->ID, $target);
                    $redirects += $this->migrateOldSlugs((int) $post->ID, '/questions', $slug, $target);

                    return compact('answers', 'comments', 'redirects');
                });
                $stats[$counter]++;
                foreach ($delta as $key => $value) {
                    $stats[$key] += $value;
                }
                $onProgress && $onProgress();
            }
        }

        return $stats;
    }
}
