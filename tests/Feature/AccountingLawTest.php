<?php

namespace Tests\Feature;

use App\Models\AccountingLaw;
use App\Models\Category;
use App\Models\LawEvolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingLawTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_law_with_category(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::factory()->law()->create(['name' => 'مالیات']);

        $this->actingAs($admin)->post(route('admin.laws.store'), [
            'article_number' => '147',
            'title' => 'هزینه‌های قابل قبول',
            'slug' => 'made-147',
            'category_id' => $category->id,
            'introduction' => '<p>مقدمه ماده</p>',
            'article_text' => '<p>متن رسمی ماده</p>',
            'featured_image' => 'https://example.com/law-cover.jpg',
            'featured_image_alt' => 'تصویر ماده ۱۴۷',
            'is_published' => '1',
            'status' => 'published',
        ])->assertRedirect();

        $law = AccountingLaw::query()->where('slug', 'made-147')->first();
        $this->assertNotNull($law);
        $this->assertTrue($law->isLive());
        $this->assertSame($category->id, $law->category?->id);
        $this->assertSame('https://example.com/law-cover.jpg', $law->featured_image);
        $this->assertSame('تصویر ماده ۱۴۷', $law->featured_image_alt);
    }

    public function test_admin_can_manage_law_evolutions(): void
    {
        $admin = $this->makeAdmin();
        $law = AccountingLaw::factory()->create();

        $this->actingAs($admin)->post(route('admin.laws.evolutions.store', $law), [
            'event_date' => '1400/01/01',
            'title' => 'تصویب اولیه',
            'description' => 'قانون برای نخستین بار تصویب شد.',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.laws.edit', $law));

        $evolution = LawEvolution::query()->where('accounting_law_id', $law->id)->first();
        $this->assertNotNull($evolution);

        $this->actingAs($admin)->put(route('admin.laws.evolutions.update', [$law, $evolution]), [
            'event_date' => '1401/06/15',
            'title' => 'اصلاحیه',
            'description' => 'اصلاح بندهای کلیدی.',
            'sort_order' => 2,
        ])->assertRedirect(route('admin.laws.edit', $law));

        $this->assertDatabaseHas('law_evolutions', [
            'id' => $evolution->id,
            'title' => 'اصلاحیه',
            'event_date' => '1401/06/15',
        ]);

        $this->actingAs($admin)->delete(route('admin.laws.evolutions.destroy', [$law, $evolution]))
            ->assertRedirect(route('admin.laws.edit', $law));

        $this->assertDatabaseMissing('law_evolutions', ['id' => $evolution->id]);
    }

    public function test_published_law_is_visible_on_public_pages(): void
    {
        $category = Category::factory()->law()->create(['name' => 'بیمه', 'slug' => 'insurance']);
        $law = AccountingLaw::factory()->published()->create([
            'article_number' => '169',
            'title' => 'اظهارنامه مالیاتی',
            'slug' => 'made-169',
            'introduction' => '<p>مقدمه عمومی</p>',
            'article_text' => '<p>متن ماده</p>',
            'plain_language' => '<p>به زبان ساده</p>',
        ]);
        $law->syncCategories([$category->id], $category->id);
        $law->evolutions()->create([
            'event_date' => '1399/12/29',
            'title' => 'تصویب',
            'description' => 'تصویب اولیه قانون',
            'sort_order' => 1,
        ]);

        $this->get(route('laws.index'))
            ->assertOk()
            ->assertSee('اظهارنامه مالیاتی')
            ->assertSee('مواد قانونی')
            ->assertSee('دسته‌بندی')
            ->assertSee('بیمه')
            ->assertSee('جستجو بین مواد قانونی')
            ->assertSee('laws-hero', false)
            ->assertDontSee('cafe-hero', false);

        $this->get(route('laws.index', ['category' => 'insurance']))
            ->assertOk()
            ->assertSee('اظهارنامه مالیاتی');

        $this->get(route('laws.index', ['q' => 'اظهارنامه']))
            ->assertOk()
            ->assertSee('اظهارنامه مالیاتی');

        $this->get(route('laws.show', 'made-169'))
            ->assertOk()
            ->assertSee('ماده ۱۶۹')
            ->assertSee('اظهارنامه مالیاتی')
            ->assertSee('به زبان ساده')
            ->assertSee('سیر تحول')
            ->assertSee('1399/12/29')
            ->assertSee('تصویب');
    }

    public function test_draft_law_is_not_public(): void
    {
        AccountingLaw::factory()->create([
            'slug' => 'draft-law',
            'is_published' => false,
            'status' => AccountingLaw::STATUS_DRAFT,
        ]);

        $this->get(route('laws.show', 'draft-law'))->assertNotFound();
    }
}
