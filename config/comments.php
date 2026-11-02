<?php

return [
    'settings_key' => 'comments_settings',

    'defaults' => [
        'enabled' => true,
        'types' => [
            'cms_post' => true,
            'shop_product' => true,
        ],
        'default_status' => 'open',

        'require_approval' => true,
        'auto_approve_known_users' => false,
        'links_force_moderation' => true,

        'auth_required' => true,
        'guests_allowed' => false,
        'allow_replies' => true,
        'max_depth' => 1,
        'min_length' => 3,
        'max_length' => 2000,
        'edit_window_minutes' => 0,
        'allow_delete_own' => false,

        'order' => 'newest',
        'per_page' => 20,
        'show_count_on_listings' => true,
        'closed_message' => 'دیدگاه‌ها برای این محتوا بسته شده‌اند.',
        'pending_message' => 'دیدگاه شما ثبت شد و پس از تأیید نمایش داده می‌شود.',
        'rejected_message' => 'دیدگاه شما به دلیل نقض قوانین پذیرفته نشد.',

        'throttle_per_minute' => 3,
        'honeypot_enabled' => true,
        'max_links' => 1,
        'block_on_max_links' => 'spam',
        'duplicate_window_minutes' => 30,
        'max_repeat_chars' => 5,
        'profanity_filter_enabled' => true,
        'profanity_action' => 'reject',
        'profanity_words' => [],
        'blacklist_words' => [],
        'word_lists_managed' => false,
        'blacklist_action' => 'spam',
        'blocked_ips' => [],
        'auto_close_days' => 0,
        'soft_score_threshold' => 30,
    ],

    'guard_rules' => [
        App\Services\Comments\Rules\BlockedIpRule::class,
        App\Services\Comments\Rules\HoneypotRule::class,
        App\Services\Comments\Rules\DuplicateCommentRule::class,
        App\Services\Comments\Rules\LinkLimitRule::class,
        App\Services\Comments\Rules\RepeatedCharsRule::class,
        App\Services\Comments\Rules\BlacklistRule::class,
        App\Services\Comments\Rules\ProfanityRule::class,
    ],
];
