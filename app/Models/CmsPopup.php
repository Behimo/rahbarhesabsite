<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPopup extends Model
{
    public const MODE_PAGES = 'pages';

    public const MODE_RULES = 'rules';

    public const FREQUENCIES = [
        'always' => 'هر بار بازدید',
        'session' => 'یک‌بار در هر نشست',
        'day' => 'یک‌بار در روز',
        'once' => 'فقط یک‌بار',
    ];

    public const AUDIENCES = [
        'all' => 'همه بازدیدکننده‌ها',
        'guest' => 'فقط مهمان‌ها',
        'auth' => 'فقط کاربران واردشده',
    ];

    public const RULE_MATCHES = [
        'all' => 'همه صفحات سایت',
        'contains' => 'مسیر شامل این عبارت باشد',
        'starts' => 'مسیر با این آدرس شروع شود',
        'equals' => 'مسیر دقیقاً همین باشد',
    ];

    protected $fillable = [
        'name', 'title', 'body', 'image_url', 'button_label', 'button_url',
        'is_active', 'starts_at', 'ends_at', 'target_mode', 'pages', 'rules',
        'delay_seconds', 'frequency', 'audience', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'pages' => 'array',
            'rules' => 'array',
            'delay_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function placementSummary(): string
    {
        if ($this->target_mode === self::MODE_RULES) {
            $match = $this->rules['match'] ?? 'all';
            $path = $this->rules['path'] ?? '';

            return match ($match) {
                'contains' => 'شرط: شامل '.$path,
                'starts' => 'شرط: شروع با '.$path,
                'equals' => 'شرط: '.$path,
                default => 'شرط: همه صفحات',
            };
        }

        $count = count($this->pages ?? []);

        return 'صفحه انتخاب‌شده: '.fa_digits($count);
    }
}
