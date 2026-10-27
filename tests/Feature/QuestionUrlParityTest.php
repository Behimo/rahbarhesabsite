<?php

namespace Tests\Feature;

use App\Models\CafeAnswer;
use App\Models\CafeQuestion;
use App\Models\Tag;
use App\Models\User;
use App\Services\PermalinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionUrlParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'production');
        // The Laravel test client strips trailing slashes; middleware is covered separately.
        config(['seo.normalization_environments' => []]);
    }

    public function test_archive_has_wordpress_heading_and_canonical(): void
    {
        $this->get('/questions/')->assertOk()
            ->assertSee('<h1 id="cafeHeroTitle" class="cafe-hero__title">کافه سوال راهبر حساب</h1>', false)
            ->assertSee('<link rel="canonical" href="https://rahbarhesab.com/questions/">', false);
        $this->assertSame('/questions/', route('cafe-soal.index', absolute: false));
    }

    public function test_archive_pagination_uses_twenty_items_and_path_links(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->question(['slug' => 'question-'.$i, 'title' => 'Question '.$i]);
        }
        $this->get('/questions/')->assertOk()->assertSee('/questions/page/2/', false)
            ->assertViewHas('questions', fn ($questions) => $questions->count() === 20);
        $this->get('/questions/page/2/')->assertOk()
            ->assertViewHas('questions', fn ($questions) => $questions->count() === 5)
            ->assertSee('صفحه 2 از 2')
            ->assertSee('href="https://rahbarhesab.com/questions/page/2/"', false);
        $this->get('/questions/page/3/')->assertNotFound();
        $this->get('/questions/page/')->assertNotFound();
    }

    public function test_detail_has_wordpress_metadata(): void
    {
        $question = $this->question();
        $this->get('/questions/'.$question->slug.'/')->assertOk()
            ->assertSee('<title>'.$question->title.' - راهبر حساب</title>', false)
            ->assertSee('max-image-preview:large', false)
            ->assertSee('<link rel="canonical" href="https://rahbarhesab.com/questions/'.$question->slug.'/">', false)
            ->assertDontSee('QAPage', false);
        $this->assertSame('/questions/'.$question->slug.'/', app(PermalinkService::class)->question($question));
    }

    public function test_unpublished_questions_are_not_public(): void
    {
        foreach ([CafeQuestion::STATUS_PENDING, CafeQuestion::STATUS_REJECTED] as $status) {
            $question = $this->question(['status' => $status, 'slug' => $status]);
            $this->get('/questions/'.$question->slug.'/')->assertNotFound();
        }
    }

    public function test_staging_aliases_redirect_without_changing_route_names(): void
    {
        $this->get('/cafe-soal/')->assertStatus(301)->assertRedirect('/questions/');
        $this->get('/cafe-soal/example/')->assertStatus(301)->assertRedirect('/questions/example/');
        $this->get('/cafe-soal/page/2/')->assertStatus(301)->assertRedirect('/questions/page/2/');
    }

    public function test_filtered_archives_are_noindex(): void
    {
        foreach (['q=x', 'q=', 'sort=popular', 'category=999'] as $query) {
            $this->get('/questions/?'.$query)->assertOk()->assertSee('noindex, follow', false);
        }
        $this->get('/questions/?sort=latest')->assertOk()->assertDontSee('noindex', false);
    }

    public function test_question_sitemap_excludes_noindex_and_pending_and_invalidates_cache(): void
    {
        $published = $this->question(['slug' => 'published']);
        $noindex = $this->question(['slug' => 'hidden', 'robots' => 'noindex, follow']);
        $pending = $this->question(['slug' => 'pending', 'status' => CafeQuestion::STATUS_PENDING]);
        $this->get('/qa-sitemap.xml')->assertOk()->assertSee('/questions/published/', false)
            ->assertDontSee('/questions/hidden/', false)->assertDontSee('/questions/pending/', false);
        $published->update(['robots' => 'noindex, follow']);
        $this->get('/qa-sitemap.xml')->assertOk()->assertDontSee('/questions/published/', false);
        $this->get('/sitemap_index.xml')->assertOk()->assertSee('/qa-sitemap.xml', false);
        $this->get('/page-sitemap.xml')->assertOk()->assertDontSee('/cafe-soal', false)->assertDontSee('/questions/', false);
        $this->get('/qa-sitemap1.xml')->assertStatus(301)->assertRedirect('/qa-sitemap.xml');
        $this->get('/qa-sitemap2.xml')->assertStatus(301)->assertRedirect('/qa-sitemap.xml');
    }

    public function test_imported_html_is_sanitized_and_new_question_text_is_escaped(): void
    {
        $imported = $this->question(['slug' => 'imported', 'body' => '<p><b>سوال وردپرس</b></p><script>alert(1)</script>']);
        $imported->wp_post_id = 123;
        $imported->save();
        $this->get('/questions/imported/')->assertOk()->assertSee('<b>سوال وردپرس</b>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $new = $this->question(['slug' => 'new', 'body' => '<b>سوال جدید</b>']);
        $this->get('/questions/new/')->assertOk()->assertSee('&lt;b&gt;سوال جدید&lt;/b&gt;', false);
    }

    public function test_guest_comments_and_threaded_replies_are_rendered_safely(): void
    {
        $question = $this->question();
        $answer = new CafeAnswer(['cafe_question_id' => $question->id, 'user_id' => null,
            'author_name' => 'مهمان وردپرس', 'body' => '<b>متن دیدگاه</b>', 'is_official' => false]);
        $answer->wp_comment_id = 901;
        $answer->save();
        $answer->replies()->create(['cafe_question_id' => $question->id, 'user_id' => null,
            'author_name' => 'مهمان دوم', 'body' => '<b>پاسخ جدید</b>', 'is_official' => false]);
        $this->get('/questions/'.$question->slug.'/')->assertOk()
            ->assertSee('مهمان وردپرس')->assertSee('مهمان دوم')->assertSee('cafe-answer--reply', false)
            ->assertSee('<b>متن دیدگاه</b>', false)->assertSee('&lt;b&gt;پاسخ جدید&lt;/b&gt;', false);
    }

    public function test_video_schema_requires_poster_and_video_remains_visible_without_it(): void
    {
        $question = $this->question(['video_url' => 'https://dl.rahbarhesab.com/question.mp4',
            'video_poster' => 'https://dl.rahbarhesab.com/poster.jpg']);
        $this->get('/questions/'.$question->slug.'/')->assertOk()->assertSee('<video ', false)
            ->assertSee('VideoObject', false)->assertSee('uploadDate', false);
        $question->update(['video_poster' => null]);
        $this->get('/questions/'.$question->slug.'/')->assertOk()->assertSee('<video ', false)
            ->assertDontSee('VideoObject', false);
    }

    public function test_imported_plain_text_keeps_wordpress_paragraphs_and_line_breaks(): void
    {
        $question = $this->question(['body' => "خط اول\nخط دوم\n\nپاراگراف دوم"]);
        $question->wp_post_id = 124;
        $question->save();
        $answer = new CafeAnswer(['cafe_question_id' => $question->id, 'user_id' => null,
            'author_name' => 'مهمان', 'body' => "دیدگاه\nادامه", 'is_official' => false]);
        $answer->wp_comment_id = 902;
        $answer->save();

        $this->get('/questions/'.$question->slug.'/')->assertOk()
            ->assertSee('<p>خط اول<br>'."\n".'خط دوم</p>', false)
            ->assertSee('<p>پاراگراف دوم</p>', false)
            ->assertSee('<p>دیدگاه<br>'."\n".'ادامه</p>', false);
        $this->assertSame('<p>از قبل</p>', wp_autop_html('<p>از قبل</p>'));
    }

    public function test_tag_archive_lists_tagged_questions_twenty_per_page(): void
    {
        $tag = Tag::query()->create(['name' => 'پرسش و پاسخ سامانه مودیان', 'slug' => 'samaneh-moodian-questions']);
        for ($i = 1; $i <= 22; $i++) {
            $this->question(['slug' => 'tagged-'.$i, 'title' => 'سوال برچسب '.$i, 'answered_at' => now()->subDays($i)])
                ->syncTags([$tag->id]);
        }
        $this->question(['slug' => 'pending-tagged', 'status' => CafeQuestion::STATUS_PENDING])->syncTags([$tag->id]);

        $this->get('/tag/samaneh-moodian-questions/')->assertOk()
            ->assertSee('<title>پرسش و پاسخ سامانه مودیان Archives - راهبر حساب</title>', false)
            ->assertSee('https://rahbarhesab.com/questions/tagged-1/', false)
            ->assertSee('/tag/samaneh-moodian-questions/page/2/', false)
            ->assertDontSee('/questions/pending-tagged/', false)
            ->assertViewHas('posts', fn ($items) => $items->count() === 20);
        $this->get('/tag/samaneh-moodian-questions/page/2/')->assertOk()
            ->assertSee('https://rahbarhesab.com/questions/tagged-22/', false)
            ->assertViewHas('posts', fn ($items) => $items->count() === 2);
        $this->get('/tag/samaneh-moodian-questions/page/3/')->assertNotFound();
    }

    public function test_wordpress_account_pages_redirect_to_panel(): void
    {
        foreach ([
            '/my-account/' => '/panel', '/my-account/orders/' => '/panel', '/my-login/' => '/login',
            '/edit-your-profile/' => '/panel/profile', '/order-tracking/' => '/panel/orders',
            '/'.rawurlencode('وضعیت-پرداخت').'/' => '/panel/orders',
        ] as $from => $to) {
            $this->get($from)->assertStatus(301)->assertRedirect($to);
        }
    }

    public function test_question_breadcrumb_uses_archive_title(): void
    {
        $question = $this->question();
        $this->get('/questions/'.$question->slug.'/')->assertOk()
            ->assertSee('>کافه سوال راهبر حساب</a>', false);
    }

    private function question(array $attributes = []): CafeQuestion
    {
        return CafeQuestion::query()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'title' => 'پرسش حسابداری', 'slug' => 'sample-question', 'body' => 'متن سوال',
            'status' => CafeQuestion::STATUS_ANSWERED, 'answered_at' => now(),
        ], $attributes));
    }
}
