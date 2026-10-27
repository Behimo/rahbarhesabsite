<?php

namespace Tests\Feature;

use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Models\User;
use App\Services\WordpressMigrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WordpressQuestionMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.wordpress' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'wp_']]);
        DB::purge('wordpress');
        $schema = Schema::connection('wordpress');
        $schema->create('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('ID')->primary();
            foreach (['post_type', 'post_status', 'post_name', 'post_title', 'post_content', 'post_date', 'post_modified'] as $column) {
                $table->text($column);
            }
            $table->unsignedBigInteger('post_author')->default(77);
        });
        $schema->create('postmeta', function (Blueprint $table) {
            $table->id('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key');
            $table->text('meta_value');
        });
        $schema->create('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_ID')->primary();
            $table->unsignedBigInteger('comment_post_ID');
            $table->unsignedBigInteger('comment_parent')->default(0);
            $table->unsignedBigInteger('user_id')->default(0);
            foreach (['comment_approved', 'comment_type', 'comment_author', 'comment_content', 'comment_date'] as $column) {
                $table->text($column);
            }
        });
        $schema->create('terms', function (Blueprint $table) {
            $table->unsignedBigInteger('term_id')->primary();
            $table->string('name');
            $table->string('slug');
        });
        $schema->create('term_taxonomy', function (Blueprint $table) {
            $table->unsignedBigInteger('term_taxonomy_id')->primary();
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
        });
        $schema->create('term_relationships', function (Blueprint $table) {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
            $table->integer('term_order')->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('wordpress');
        DB::purge('wordpress');
        parent::tearDown();
    }

    public function test_migration_preserves_content_dates_meta_tags_comments_and_redirects_idempotently(): void
    {
        User::factory()->create(['wp_id' => 77]);
        $this->seedQuestion();
        $this->meta('_qa_answer', '<p>جواب رسمی</p>');
        $this->meta('reza_video_url', 'https://www.dl.rahbarhesab.com/video.mp4');
        $this->meta('reza_video_poster', 'https://dl.rahbarhesab.com/poster.jpg');
        $this->meta('rank_math_title', 'پرسش و پاسخ : %title% %sitename% ');
        $this->meta('_wp_old_slug', 'first-old');
        $this->meta('_wp_old_slug', 'second-old');
        $wp = DB::connection('wordpress');
        $tag = Tag::query()->create(['name' => 'مالیات', 'slug' => 'tax']);
        $wp->table('terms')->insert(['term_id' => 5, 'name' => $tag->name, 'slug' => $tag->slug]);
        $wp->table('term_taxonomy')->insert(['term_taxonomy_id' => 5, 'term_id' => 5, 'taxonomy' => 'post_tag']);
        $wp->table('term_relationships')->insert(['object_id' => 100, 'term_taxonomy_id' => 5]);
        foreach ([1 => 0, 2 => 1] as $id => $parent) {
            $wp->table('comments')->insert(['comment_ID' => $id, 'comment_post_ID' => 100,
                'comment_parent' => $parent, 'user_id' => $id === 1 ? 77 : 0,
                'comment_approved' => '1', 'comment_type' => '', 'comment_author' => 'مهمان',
                'comment_content' => '<p>دیدگاه</p>', 'comment_date' => '2022-02-03 04:05:06']);
        }
        $service = app(WordpressMigrationService::class);
        $stats = $service->migrateQuestions();
        $this->assertSame(['created' => 1, 'updated' => 0, 'answers' => 1, 'comments' => 2, 'redirects' => 3, 'conflicts' => []], $stats);
        $question = CafeQuestion::query()->sole();
        $this->assertSame('same-slug', $question->slug);
        $this->assertSame(CafeQuestion::STATUS_ANSWERED, $question->status);
        $this->assertSame('2020-01-02 03:04:05', $question->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2023-01-02 03:04:05', $question->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('پرسش و پاسخ : عنوان سوال راهبر حساب', $question->meta_title);
        $this->assertSame('https://dl.rahbarhesab.com/video.mp4', $question->video_url);
        $this->assertSame(3, $question->answers_count);
        $this->assertTrue($question->tags->contains($tag));
        $parent = CafeAnswer::query()->where('wp_comment_id', 1)->sole();
        $reply = CafeAnswer::query()->where('wp_comment_id', 2)->sole();
        $this->assertSame($parent->id, $reply->parent_id);
        $this->assertNull($reply->user_id);
        $this->assertDatabaseHas('cms_redirects', ['from_path' => '/?p=100', 'to_path' => '/questions/same-slug/']);
        $this->assertDatabaseHas('cms_redirects', ['from_path' => '/questions/first-old']);
        $this->assertDatabaseHas('cms_redirects', ['from_path' => '/questions/second-old']);
        $question->views_count = 42;
        $question->save();
        $second = $service->migrateQuestions();
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['updated']);
        $this->assertDatabaseCount('cafe_questions', 1);
        $this->assertDatabaseCount('cafe_answers', 3);
        $this->assertSame(42, $question->fresh()->views_count);
        $this->assertSame('2023-01-02 03:04:05', $question->fresh()->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_dry_run_and_trash_do_not_write(): void
    {
        $this->seedQuestion();
        $this->seedQuestion(101, ['post_status' => 'trash', 'post_name' => 'trash-slug']);
        $this->assertSame(1, app(WordpressMigrationService::class)->countQuestions());
        $this->artisan('wp:migrate-questions', ['--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('cafe_questions', 0);
        $this->assertDatabaseCount('cafe_answers', 0);
        $this->assertDatabaseCount('cms_redirects', 0);
    }

    public function test_user_slug_conflicts_are_reported_without_overwrite_even_when_trashed(): void
    {
        $this->seedQuestion();
        $question = CafeQuestion::query()->create(['user_id' => User::factory()->create()->id,
            'slug' => 'same-slug', 'title' => 'متن کاربر', 'body' => 'بدنه کاربر', 'status' => 'pending']);
        $question->delete();
        foreach ([true, false] as $dryRun) {
            $stats = app(WordpressMigrationService::class)->migrateQuestions($dryRun);
            $this->assertCount(1, $stats['conflicts']);
            $this->assertSame(0, $stats['created']);
        }
        $this->assertSame('متن کاربر', $question->fresh()->title);
        $this->assertNull($question->fresh()->wp_post_id);
        $this->assertTrue($question->fresh()->trashed());
    }

    public function test_fallback_user_is_required_when_no_author_or_admin_exists(): void
    {
        $this->seedQuestion();
        $this->artisan('wp:migrate-questions')->assertFailed();
        $this->assertDatabaseCount('cafe_questions', 0);
        $user = User::factory()->create();
        $this->artisan('wp:migrate-questions', ['--fallback-user' => $user->id])->assertSuccessful();
        $this->assertSame($user->id, CafeQuestion::query()->sole()->user_id);
    }

    public function test_rank_math_templates_resolve_literally_like_the_live_site(): void
    {
        $method = new \ReflectionMethod(WordpressMigrationService::class, 'resolveRankMathTemplate');
        foreach ([new CmsPost(['title' => 'عنوان']), new ShopProduct(['title' => 'عنوان'])] as $model) {
            foreach (['%title% %sep% %sitename%', '%title% %page% %sep% %sitename%', '%title% - %sitename%'] as $template) {
                $this->assertSame('عنوان - راهبر حساب', $method->invoke(app(WordpressMigrationService::class), $template, $model));
            }
            // Rank Math prints a template without %sep% as written (e.g. "پرسش و پاسخ : X راهبر حساب").
            $this->assertSame('عنوان راهبر حساب', $method->invoke(app(WordpressMigrationService::class), '%title% %sitename% ', $model));
        }
    }

    public function test_manual_excerpt_becomes_the_description_like_rank_math(): void
    {
        $method = new \ReflectionMethod(WordpressMigrationService::class, 'applyRankMathMeta');
        $service = app(WordpressMigrationService::class);

        $product = new ShopProduct(['title' => 'دوره']);
        $method->invoke($service, $product, [], "<ul>\r\n<li>15 جلسه آموزش</li>\r\n<li>پشتیبانی</li>\r\n</ul>\r\nپیش نیاز: اصول");
        $this->assertSame('15 جلسه آموزش پشتیبانی پیش نیاز: اصول', $product->meta_description);

        $post = new CmsPost(['title' => 'عنوان']);
        $method->invoke($service, $post, ['rank_math_description' => 'خلاصه: %excerpt%'], 'متن دستی');
        $this->assertSame('خلاصه: متن دستی', $post->meta_description);

        $page = new CmsPost(['title' => 'عنوان']);
        $method->invoke($service, $page, [], '');
        $this->assertNull($page->meta_description);
    }

    public function test_rank_math_template_quirks_match_the_live_output(): void
    {
        $method = new \ReflectionMethod(WordpressMigrationService::class, 'resolveRankMathTemplate');
        $service = app(WordpressMigrationService::class);
        $model = new CmsPost(['title' => 'عنوان']);

        $this->assertSame('پرسش و پاسخ : عنوان -', $method->invoke($service, 'پرسش و پاسخ : %title% -  %SITename% ', $model));
        $this->assertSame('پرسش و پاسخ : عنوان -', $method->invoke($service, 'پرسش و پاسخ : %title% -  %sitename%%', $model));
        $this->assertSame('پرسش و پاسخ : عنوان - راهبر حساب', $method->invoke($service, 'پرسش و پاسخ : %title% -  %sep% %sitename%', $model));
    }

    public function test_long_redirects_are_saved_and_oversized_paths_are_logged(): void
    {
        $method = new \ReflectionMethod(WordpressMigrationService::class, 'ensureRedirect');
        $path = '/questions/'.str_repeat('a', 300);
        $this->assertTrue($method->invoke(app(WordpressMigrationService::class), $path, '/questions/final/'));
        $this->assertDatabaseHas('cms_redirects', ['from_path' => $path]);
        Log::shouldReceive('warning')->once();
        $this->assertFalse($method->invoke(app(WordpressMigrationService::class), '/'.str_repeat('a', 501), '/questions/final/'));
    }

    private function seedQuestion(int $id = 100, array $attributes = []): void
    {
        DB::connection('wordpress')->table('posts')->insert(array_merge([
            'ID' => $id, 'post_type' => 'qa', 'post_status' => 'publish', 'post_name' => 'same-slug',
            'post_title' => 'عنوان سوال', 'post_content' => '<p>سوال</p>', 'post_author' => 77,
            'post_date' => '2020-01-02 03:04:05', 'post_modified' => '2023-01-02 03:04:05',
        ], $attributes));
    }

    private function meta(string $key, string $value): void
    {
        DB::connection('wordpress')->table('postmeta')->insert(['post_id' => 100, 'meta_key' => $key, 'meta_value' => $value]);
    }
}
