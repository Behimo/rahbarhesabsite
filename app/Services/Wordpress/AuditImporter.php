<?php

namespace App\Services\Wordpress;

use App\Models\CafeQuestion;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuditImporter extends WordpressImporter
{
    public function audit(): array
    {
        return [
            'wp_users' => $this->wp()->table('users')->count(),
            'local_users_with_wp_id' => User::query()->whereNotNull('wp_id')->count(),
            'local_pending_activation' => User::query()->where('status', User::STATUS_PENDING_ACTIVATION)->count(),
            'wp_product_cats' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'product_cat')->count(),
            'local_product_categories' => Category::query()->where('type', Category::TYPE_PRODUCT)->count(),
            'wp_post_cats' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'category')->count(),
            'local_post_categories' => Category::query()->where('type', Category::TYPE_POST)->count(),
            'wp_products' => $this->wp()->table('posts')->where('post_type', 'product')->whereIn('post_status', ['publish', 'private'])->count(),
            'local_courses' => Course::query()->count(),
            'local_course_category_links' => DB::table('categoryables')
                ->where('categoryable_type', ShopProduct::class)
                ->count(),
            'wp_questions' => $this->countQuestions(),
            'local_questions' => CafeQuestion::query()->whereNotNull('wp_post_id')->count(),
            'wp_posts' => $this->wp()->table('posts')->where('post_type', 'post')->where('post_status', 'publish')->count(),
            'local_posts' => CmsPost::query()->count(),
            'local_post_category_links' => DB::table('categoryables')
                ->where('categoryable_type', CmsPost::class)
                ->count(),
            'wp_pages' => $this->wp()->table('posts')->where('post_type', 'page')->where('post_status', 'publish')->count(),
            'local_pages_with_wp_post_id' => CmsPage::query()->whereNotNull('wp_post_id')->count(),
            'local_wp_html_pages' => CmsPage::query()->where('template', 'wp-html')->count(),
            'wp_post_tags' => $this->wp()->table('term_taxonomy')->where('taxonomy', 'post_tag')->count(),
            'local_tags' => Tag::query()->count(),
            'local_taggables' => DB::table('taggables')->count(),
            'wp_paid_orders' => $this->wp()->table('posts')->where('post_type', 'shop_order')->whereIn('post_status', ['wc-completed', 'wc-processing'])->count(),
            'local_wp_orders' => Order::query()->whereNotNull('wp_id')->count(),
            'local_orders_needs_review' => Order::query()->where('needs_review', true)->count(),
            'local_orders_without_user' => Order::query()->whereNull('user_id')->count(),
            'local_enrollments' => CourseEnrollment::query()->count(),
        ];
    }
}
