<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        $this->mergeDuplicateCartItems();
        $this->dedupePaymentAuthorities();

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->string('currency')->default('IRT')->change();
        });

        DB::table('orders')->where('currency', 'IRR')->update(['currency' => 'IRT']);

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['shop_product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_product_id')->nullable()->change();
            $table->foreign('shop_product_id')->references('id')->on('shop_products')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['gateway', 'authority'], 'payments_gateway_authority_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'shop_product_id'], 'cart_items_user_product_unique');
            $table->unique(['session_id', 'shop_product_id'], 'cart_items_session_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_product_unique');
            $table->dropUnique('cart_items_session_product_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_gateway_authority_unique');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['shop_product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_product_id')->nullable(false)->change();
            $table->foreign('shop_product_id')->references('id')->on('shop_products')->cascadeOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('currency')->default('IRR')->change();
        });

        DB::table('orders')->where('currency', 'IRT')->update(['currency' => 'IRR']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    private function mergeDuplicateCartItems(): void
    {
        $userDupes = DB::table('cart_items')
            ->select('user_id', 'shop_product_id', DB::raw('MIN(id) as keep_id'), DB::raw('SUM(quantity) as total_qty'))
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'shop_product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($userDupes as $dupe) {
            DB::table('cart_items')->where('id', $dupe->keep_id)->update([
                'quantity' => min(65535, (int) $dupe->total_qty),
            ]);
            DB::table('cart_items')
                ->where('user_id', $dupe->user_id)
                ->where('shop_product_id', $dupe->shop_product_id)
                ->where('id', '!=', $dupe->keep_id)
                ->delete();
        }

        $sessionDupes = DB::table('cart_items')
            ->select('session_id', 'shop_product_id', DB::raw('MIN(id) as keep_id'), DB::raw('SUM(quantity) as total_qty'))
            ->whereNotNull('session_id')
            ->whereNull('user_id')
            ->groupBy('session_id', 'shop_product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($sessionDupes as $dupe) {
            DB::table('cart_items')->where('id', $dupe->keep_id)->update([
                'quantity' => min(65535, (int) $dupe->total_qty),
            ]);
            DB::table('cart_items')
                ->where('session_id', $dupe->session_id)
                ->whereNull('user_id')
                ->where('shop_product_id', $dupe->shop_product_id)
                ->where('id', '!=', $dupe->keep_id)
                ->delete();
        }
    }

    private function dedupePaymentAuthorities(): void
    {
        $dupes = DB::table('payments')
            ->select('gateway', 'authority', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('authority')
            ->groupBy('gateway', 'authority')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $dupe) {
            DB::table('payments')
                ->where('gateway', $dupe->gateway)
                ->where('authority', $dupe->authority)
                ->where('id', '!=', $dupe->keep_id)
                ->update(['authority' => null]);
        }
    }
};
