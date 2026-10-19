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
    | Path prefixes reserved for Laravel systems — never treated as WP pages/posts.
    */
    'reserved_prefixes' => [
        'admin', 'panel', 'api', 'login', 'logout', 'cart', 'checkout', 'search',
        'courses', 'laws', 'cafe-soal', 'up', 'sanctum', 'livewire', 'vendor',
        'storage', 'build', 'site', 'themes', 'horizon', 'telescope',
        'product', 'product-category', 'category', 'tag', 'blog', 'feed',
        'wp-admin', 'wp-content', 'wp-includes', 'wp-json', 'wp-login.php',
        'xmlrpc.php', 'my-login',
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
