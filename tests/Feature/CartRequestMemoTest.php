<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartRequestMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_are_loaded_once_and_count_sums_quantities(): void
    {
        $user = User::factory()->create();
        $first = ShopProduct::query()->create([
            'slug' => 'memo-a-'.uniqid(),
            'title' => 'کالای اول',
            'price' => 1000,
            'type' => ShopProduct::TYPE_PHYSICAL,
            'stock' => 10,
            'is_published' => true,
        ]);
        $second = ShopProduct::query()->create([
            'slug' => 'memo-b-'.uniqid(),
            'title' => 'کالای دوم',
            'price' => 2000,
            'type' => ShopProduct::TYPE_PHYSICAL,
            'stock' => 10,
            'is_published' => true,
        ]);
        CartItem::query()->create(['user_id' => $user->id, 'shop_product_id' => $first->id, 'quantity' => 2]);
        CartItem::query()->create(['user_id' => $user->id, 'shop_product_id' => $second->id, 'quantity' => 3]);

        $this->actingAs($user);
        $cart = app(CartService::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $cart->items();
        $cart->items();

        $cartQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'cart_items'))
            ->count();

        $this->assertSame(1, $cartQueries);
        $this->assertSame(5, $cart->count());
        $this->assertSame(5, $cart->quantityTotal());
    }
}
