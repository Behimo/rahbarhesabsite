<?php

/**
 * SEO / WordPress URL parity contract for phase-1 migration.
 *
 * Canonical host: https://rahbarhesab.com (no www)
 * Trailing slash: required (matches live WordPress)
 * Valuable indexed URLs must return 200 on the same path — no cutover 301.
 */

return [
    'canonical_host' => env('SEO_CANONICAL_HOST', 'rahbarhesab.com'),
    'force_https' => env('SEO_FORCE_HTTPS', true),
    'force_www' => false,
    'trailing_slash' => true,
    'normalization_environments' => array_filter(array_map('trim', explode(',', (string) env('SEO_NORMALIZE_ENVS', 'production')))),
    'system_prefixes' => [
        'admin', 'panel', 'api', 'livewire', 'sanctum', 'up',
        'storage', 'build', 'vendor', 'site', 'themes', 'migrated-wp',
        'horizon', 'telescope', 'ignition', 'courses', 'cart', 'checkout',
        'login', 'logout', 'register',
    ],
    'sitemap_cache_ttl' => 300,
    'defaults' => [
        'description' => 'موسسه آموزش حسابداری راهبر حساب — دوره‌های کاربردی حسابداری و مالیات.',
        'keywords' => 'راهبر حساب, آموزش حسابداری, مالیات, rahbarhesab',
        'home_label' => 'خانه',
        // Rank Math global default for indexable content.
        'robots' => 'index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large',
    ],

    /*
    | Live WordPress permalink map (verified 2026-10 against production).
    */
    'patterns' => [
        'home' => '/',
        'blog_index' => '/blog/',
        'blog_paged' => '/blog/page/{page}/',
        'post' => '/{category}/{slug}/',
        'category' => '/category/{slug}/',
        'category_paged' => '/category/{slug}/page/{page}/',
        'tag' => '/tag/{slug}/',
        'tag_paged' => '/tag/{slug}/page/{page}/',
        'product_index' => '/product/',
        'product_paged' => '/product/page/{page}/',
        'product' => '/product/{slug}/',
        'product_category' => '/product-category/{slug}/',
        'product_category_paged' => '/product-category/{slug}/page/{page}/',
        'page' => '/{slug}/',
        'sitemap' => '/sitemap_index.xml',
        'legacy_sitemap' => '/sitemap.xml',
        'feed' => '/feed/',
        'wp_login' => '/wp-login.php',
        'wp_admin' => '/wp-admin',
    ],

    /*
    | Exact first-segment names reserved for Laravel systems, institutional
    | routes and SEO endpoints. A CMS page/post may not claim these as its
    | slug, and the frontend routes exclude them so real routes always win.
    |
    | Matching is anchored: only the whole segment is reserved, so e.g.
    | "builder-page" or "productivity" are still valid page slugs.
    */
    'reserved_prefixes' => [
        // Laravel / framework
        'admin', 'panel', 'api', 'login', 'logout', 'register', 'up', 'sanctum',
        'livewire', 'vendor', 'storage', 'build', 'site', 'themes', 'horizon',
        'telescope', 'my-login',
        // Commerce & account
        'cart', 'checkout', 'products', 'product', 'product-category',
        // Blog & taxonomies
        'blog', 'category', 'tag', 'feed',
        // Content sections
        'courses', 'laws', 'cafe-soal', 'questions', 'search',
        // Institutional routes
        'about', 'about-us', 'contact', 'contact-us', 'services', 'why-bisan',
        // Sitemaps & feeds
        'sitemap_index.xml', 'sitemap.xml', 'post-sitemap.xml', 'page-sitemap.xml',
        'course-sitemap.xml', 'product-sitemap.xml', 'law-sitemap.xml', 'robots.txt',
        'qa-sitemap.xml', 'qa-sitemap1.xml', 'qa-sitemap2.xml',
        // WordPress account pages (see account_redirects)
        'my-account', 'edit-your-profile', 'account-phone', 'update-billing-card', 'order-tracking', 'وضعیت-پرداخت',
        // WordPress leftovers
        'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php', 'xmlrpc.php',
        // Retired staging-era CMS prefix; reserved to prevent accidental reuse.
        'p',
    ],

    /*
    | Archive page sizes of the live WordPress site, so /page/N/ URLs keep the same
    | content and "صفحه N از M" titles (posts_per_page=20, Elementor shop grid=16).
    */
    'per_page' => [
        'posts' => 20,
        'products' => 16,
        'questions' => 20,
    ],

    /*
    | WooCommerce / Digits account pages from the WordPress page sitemap → 301 to
    | the Laravel panel. /my-account/* sub-endpoints all go to the dashboard.
    */
    'account_redirects' => [
        '/my-account' => '/panel',
        '/my-login' => '/login',
        '/edit-your-profile' => '/panel/profile',
        '/account-phone' => '/panel/profile',
        '/update-billing-card' => '/panel/profile',
        '/order-tracking' => '/panel/orders',
        '/وضعیت-پرداخت' => '/panel/orders',
    ],

    /*
    | Sitemap URLs whose redirect is intended (path => destination); the URL
    | parity audit reports them as ok-redirect instead of a failure.
    */
    'audit_expected_redirects' => [
        '/checkout' => '/login',
    ],

    /*
    | Dead WordPress endpoints → HTTP 410 Gone.
    */
    'gone_paths' => [
        '/wp-login.php',
        '/wp-admin',
        '/wp-admin/',
        '/xmlrpc.php',
        '/wp-trackback.php',
        '/wp-comments-post.php',
        '/feed',
        '/feed/',
        '/comments/feed',
        '/comments/feed/',
    ],

    'analytics' => [
        'gtm_id' => env('SEO_GTM_ID'),
        'ga4_id' => env('SEO_GA4_ID'),
        'gsc_verification' => env('SEO_GSC_VERIFICATION'),
    ],
];
