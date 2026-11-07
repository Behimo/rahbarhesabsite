<?php

namespace App\Services;

use App\Services\Wordpress\AuditImporter;
use App\Services\Wordpress\ContentImporter;
use App\Services\Wordpress\CourseImporter;
use App\Services\Wordpress\OrderImporter;
use App\Services\Wordpress\QuestionImporter;
use App\Services\Wordpress\TaxonomyImporter;
use App\Services\Wordpress\UserImporter;
use App\Services\Wordpress\WordpressImporter;
use Illuminate\Database\Eloquent\Model;

class WordpressMigrationService
{
    public function __construct(
        private WordpressImporter $wordpress,
        private QuestionImporter $questions,
        private TaxonomyImporter $taxonomy,
        private UserImporter $users,
        private CourseImporter $courses,
        private OrderImporter $orders,
        private ContentImporter $content,
        private AuditImporter $audit,
    ) {}

    public function connectionReady(): bool
    {
        return $this->wordpress->connectionReady();
    }

    public function countUsers(): int
    {
        return $this->users->countUsers();
    }

    public function countProducts(): int
    {
        return $this->courses->countProducts();
    }

    public function countPaidOrders(): int
    {
        return $this->orders->countPaidOrders();
    }

    public function countCategories(): int
    {
        return $this->taxonomy->countCategories();
    }

    public function countTermLinkTargets(): int
    {
        return $this->courses->countProducts() + $this->content->countPosts();
    }

    public function countPosts(): int
    {
        return $this->content->countPosts();
    }

    public function countQuestions(): int
    {
        return $this->wordpress->countQuestions();
    }

    public function countPages(): int
    {
        return $this->content->countPages();
    }

    /**
     * @return array{created: int, updated: int, answers: int, comments: int, redirects: int, conflicts: list<string>}
     */
    public function migrateQuestions(bool $dryRun = false, ?int $fallbackUserId = null, ?callable $onProgress = null): array
    {
        return $this->questions->migrateQuestions($dryRun, $fallbackUserId, $onProgress);
    }

    /** @return array{created: int, updated: int, conflicts: list<string>} */
    public function migrateCategories(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->taxonomy->migrateCategories($dryRun, $onProgress);
    }

    /** @return array{created: int, updated: int, conflicts: list<string>} */
    public function migrateTags(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->taxonomy->migrateTags($dryRun, $onProgress);
    }

    /** @return array{products_linked: int, posts_linked: int, skipped: int} */
    public function migrateTermLinks(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->taxonomy->migrateTermLinks($dryRun, $onProgress);
    }

    /** @return array<string, int|list<string>> */
    public function migrateUsers(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->users->migrateUsers($dryRun, $onProgress);
    }

    /** @return array<string, int|list<string>> */
    public function migrateCourses(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->courses->migrateCourses($dryRun, $onProgress);
    }

    /** @return array<string, int|list<string>> */
    public function migratePosts(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->content->migratePosts($dryRun, $onProgress);
    }

    /** @return array<string, int|list<string>> */
    public function migratePages(bool $dryRun = false, ?callable $onProgress = null, array $options = []): array
    {
        return $this->content->migratePages($dryRun, $onProgress, $options);
    }

    /** @return array<string, int|list<string>> */
    public function migrateOrders(bool $dryRun = false, ?callable $onProgress = null): array
    {
        return $this->orders->migrateOrders($dryRun, $onProgress);
    }

    /** @return array<string, int> */
    public function audit(): array
    {
        return $this->audit->audit();
    }

    public function normalizeMediaUrl(?string $url, int $maxLength = 191): ?string
    {
        return $this->wordpress->normalizeMediaUrl($url, $maxLength);
    }

    public function relativizeEmbeddedMediaUrls(string $html): string
    {
        return $this->wordpress->relativizeEmbeddedMediaUrls($html);
    }

    /** @return array<string, int> */
    public function rewriteStoredMediaUrls(): array
    {
        return $this->content->rewriteStoredMediaUrls();
    }

    protected function ensureRedirect(string $from, string $to): bool
    {
        return $this->wordpress->ensureRedirect($from, $to);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function applyRankMathMeta(Model $model, array $meta, ?string $wpExcerpt = null): void
    {
        $this->wordpress->applyRankMathMeta($model, $meta, $wpExcerpt);
    }

    protected function resolveRankMathTemplate(?string $template, Model $model, ?string $excerpt = null): ?string
    {
        return $this->wordpress->resolveRankMathTemplate($template, $model, $excerpt);
    }
}
