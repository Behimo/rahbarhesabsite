<?php

namespace Tests\Feature;

use App\Blocks\PostsBlock;
use App\Models\Category;
use App\Models\CmsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomeNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_news_limits_long_excerpts_without_changing_the_post(): void
    {
        $excerpt = str_repeat('توضیحات طولانی درباره بخشنامه مالیاتی ', 30).'پایان اختصاصی خلاصه';
        $post = CmsPost::factory()->published()->create([
            'title' => str_repeat('عنوان کامل خبر جدید ', 10),
            'excerpt' => $excerpt,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee($post->url())
            ->assertSee(Str::limit(Str::squish($excerpt), 120))
            ->assertDontSee('پایان اختصاصی خلاصه');

        $this->assertSame($excerpt, $post->fresh()->excerpt);
    }

    public function test_posts_block_also_limits_long_excerpts(): void
    {
        $excerpt = str_repeat('شرح بخشنامه جدید ', 40).'پایان اختصاصی بلوک';
        $post = CmsPost::factory()->published()->create(['excerpt' => $excerpt]);

        $html = app(PostsBlock::class)->render(['limit' => 1]);

        $this->assertStringContainsString(e($post->title), $html);
        $this->assertStringContainsString(e($post->url()), $html);
        $this->assertStringContainsString(e(Str::limit(Str::squish($excerpt), 120)), $html);
        $this->assertStringNotContainsString('پایان اختصاصی بلوک', $html);
    }

    public function test_news_excerpts_are_plain_text_with_normalized_whitespace(): void
    {
        $post = CmsPost::factory()->published()->create([
            'excerpt' => "<p>خلاصه <strong>خبر</strong>\n\t & جزئیات</p>",
        ]);

        $this->view('partials.home.news', ['latestPosts' => collect([$post])])
            ->assertSee('خلاصه خبر & جزئیات')
            ->assertDontSee('<strong>', false)
            ->assertDontSee('&lt;strong&gt;', false);
    }

    public function test_empty_excerpts_use_the_post_category(): void
    {
        $category = Category::factory()->post()->create(['name' => 'بخشنامه مالیاتی']);
        $post = CmsPost::factory()->published()->create();
        $post->syncCategories([$category->id], $category->id);

        foreach ([null, '', " \n\t ", '<p> </p>'] as $excerpt) {
            $post->excerpt = $excerpt;

            $this->view('partials.home.news', ['latestPosts' => collect([$post])])
                ->assertSee('<p>بخشنامه مالیاتی</p>', false);
        }
    }

    public function test_news_without_an_excerpt_or_category_keeps_the_default_subtitle(): void
    {
        $post = CmsPost::factory()->published()->create(['excerpt' => null]);

        $this->view('partials.home.news', ['latestPosts' => collect([$post])])
            ->assertSee('<p>حسابداری</p>', false);
    }

    public function test_fallback_news_also_has_short_plain_text_subtitles(): void
    {
        $subtitle = str_repeat('زیرعنوان خبر جایگزین ', 30).'پایان اختصاصی جایگزین';

        $this->view('partials.home.news', [
            'latestPosts' => collect(),
            'fallbackNews' => [[
                'title' => 'خبر جایگزین',
                'subtitle' => '<strong>'.$subtitle.'</strong>',
                'href' => '/blog',
            ]],
        ])
            ->assertSee('خبر جایگزین')
            ->assertSee('href="/blog"', false)
            ->assertSee(Str::limit(Str::squish($subtitle), 120))
            ->assertDontSee('پایان اختصاصی جایگزین')
            ->assertDontSee('<strong>', false);
    }
}
