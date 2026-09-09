<?php

return [
    'site_name' => env('CMS_SITE_NAME', 'Rahbar Hesab'),
    'site_name_fa' => env('CMS_SITE_NAME_FA', 'راهبر حساب'),
    'default_og_image' => env('CMS_OG_IMAGE', '/themes/rahbarhesab/og-image.png'),
    'twitter_handle' => env('CMS_TWITTER', '@rahbarhesab'),
    'contact_email' => env('CMS_CONTACT_EMAIL', 'info@rahbarhesab.ir'),
    'contact_phone' => env('CMS_CONTACT_PHONE'),
    'social' => [
        'linkedin' => env('CMS_SOCIAL_LINKEDIN'),
        'telegram' => env('CMS_SOCIAL_TELEGRAM'),
        'instagram' => env('CMS_SOCIAL_INSTAGRAM'),
    ],
    'admin_email' => env('CMS_ADMIN_EMAIL', 'admin@rahbarhesab.ir'),
    'admin_password' => env('CMS_ADMIN_PASSWORD'),

    'active_theme' => env('CMS_ACTIVE_THEME', 'rahbarhesab'),

    'payment_gateway' => env('CMS_PAYMENT_GATEWAY', 'zibal'),

    'zibal' => [
        'merchant' => env('ZIBAL_MERCHANT', 'zibal'),
        'base_url' => env('ZIBAL_BASE_URL', 'https://gateway.zibal.ir'),
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', '00000000-0000-0000-0000-000000000000'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),
    ],

    'api_token' => env('CMS_API_TOKEN'),

    'spotplayer' => [
        'api_key' => env('SPOTPLAYER_API_KEY'),
        'base_url' => env('SPOTPLAYER_BASE_URL', 'https://api.spotplayer.ir'),
        'player_url' => env('SPOTPLAYER_PLAYER_URL', 'https://app.spotplayer.ir'),
    ],

    'branding' => [
        'logo' => env('CMS_LOGO', '/images/rahbarhesab/logo-full.png'),
        'favicon' => env('CMS_FAVICON', '/images/rahbarhesab/logo-full.png'),
        'og_image' => env('CMS_OG_IMAGE', '/themes/rahbarhesab/og-image.png'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom page builder blocks
    |--------------------------------------------------------------------------
    |
    | Add your own block classes here, or register them via Hook:
    | Hook::addFilter('cms.blocks.classes', fn ($classes) => [...$classes, MyBlock::class]);
    |
    */
    'blocks' => [
        // App\Blocks\MyCustomBlock::class,
    ],
];
