<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeoValidationAndMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actual_post_crud_accepts_persian_slugs_and_real_taxonomies_and_extended_robots(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->create(['type' => 'post']);
        $tag = Tag::create(['name' => 'Test tag', 'slug' => 'test-tag']);
        $robots = 'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large';
        $this->actingAs($admin)->post('/admin/posts', [
            'slug' => 'آموزش-مالیات', 'title' => 'نامک فارسی', 'category_id' => $category->id,
            'tag_ids' => [$tag->id], 'robots' => $robots, 'is_published' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $post = CmsPost::where('slug', 'آموزش-مالیات')->firstOrFail();
        $this->assertSame($robots, $post->robots);
        $this->assertSame($category->id, $post->category->id);
        $this->assertSame([$tag->id], $post->tags->modelKeys());
        $this->actingAs($admin)->put('/admin/posts/'.$post->id, [
            'slug' => $post->slug, 'title' => 'Edited', 'robots' => $robots, 'status' => 'published',
        ])->assertSessionHasNoErrors();
        $this->assertSame('آموزش-مالیات', $post->fresh()->slug);
    }

    public function test_shared_robots_validation_rejects_invalid_values_and_forms_preserve_imported_options(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/posts', ['title' => 'Post', 'slug' => 'invalid-robots', 'robots' => 'anything'])
            ->assertSessionHasErrors('robots');
        $this->actingAs($admin)->post('/admin/pages', ['title' => 'Page', 'slug' => 'invalid-robots', 'robots' => 'anything'])
            ->assertSessionHasErrors('robots');
        $this->actingAs($admin)->post('/admin/courses', ['title' => 'Course', 'slug' => 'invalid-robots', 'price' => 0, 'level' => 'beginner', 'robots' => 'anything'])
            ->assertSessionHasErrors('robots');
        $this->withSession(['_old_input' => []]);
        $post = CmsPost::factory()->draft()->create(['robots' => 'index, follow, max-image-preview:large']);
        $this->actingAs($admin)->get('/admin/posts/'.$post->id.'/edit')->assertOk()
            ->assertSee('value="index, follow, max-image-preview:large" selected', false);
        $this->app->instance('env', 'production');
        $this->actingAs($admin)->get('/admin/posts/'.$post->id.'/preview')->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_parity_migrations_rollback_and_reapply_in_correct_reverse_order(): void
    {
        $this->assertSame('sqlite', Schema::getConnection()->getDriverName());
        $this->assertSame(':memory:', Schema::getConnection()->getDatabaseName());
        $parity = require database_path('migrations/2026_10_19_100000_add_seo_parity_columns.php');
        $composite = require database_path('migrations/2026_10_19_120000_cms_redirects_composite_unique.php');
        $robots = require database_path('migrations/2026_10_26_120000_expand_seo_robots_columns.php');
        $robots->down();
        $composite->down();
        $parity->down();
        $this->assertTrue(Schema::hasColumn('cms_pages', 'robots'));
        foreach (['cms_posts', 'cms_pages', 'shop_products'] as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'wp_post_id'));
        }
        $parity->up();
        $composite->up();
        $robots->up();
        foreach (['cms_posts', 'cms_pages', 'shop_products'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'wp_post_id'));
            $this->assertTrue(Schema::hasIndex($table, ['wp_post_id'], 'unique'));
        }
        $this->assertTrue(Schema::hasIndex('cms_redirects', ['from_path', 'from_query'], 'unique'));
    }
}
