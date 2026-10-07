<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsSidebar extends Model
{
    public const MODE_PAGES = 'pages';

    public const MODE_RULES = 'rules';

    public const SOURCE_PRODUCT = 'product';

    public const SOURCE_COURSE = 'course';

    public const SOURCE_POST = 'post';

    public const SELECTION_LATEST = 'latest';

    public const SELECTION_CATEGORY = 'category';

    public const SELECTION_BESTSELLER = 'bestseller';

    public const SOURCES = [
        self::SOURCE_PRODUCT => 'محصول',
        self::SOURCE_COURSE => 'دوره',
        self::SOURCE_POST => 'مقاله',
    ];

    public const SELECTIONS = [
        self::SELECTION_CATEGORY => 'دسته مشخص',
        self::SELECTION_LATEST => 'جدیدترین‌ها',
        self::SELECTION_BESTSELLER => 'پرفروش‌ترین‌ها',
    ];

    public const RULE_MATCHES = [
        'all' => 'همه صفحات سایت',
        'contains' => 'مسیر شامل این عبارت باشد',
        'starts' => 'مسیر با این آدرس شروع شود',
        'equals' => 'مسیر دقیقاً همین باشد',
    ];

    protected $fillable = [
        'name', 'title', 'is_active', 'target_mode', 'pages', 'rules',
        'source', 'selection', 'category_id', 'limit', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'pages' => 'array',
            'rules' => 'array',
            'limit' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function rankedLabel(): string
    {
        return $this->source === self::SOURCE_POST ? 'پربازدیدترین‌ها' : 'پرفروش‌ترین‌ها';
    }

    public function selectionLabel(): string
    {
        if ($this->selection === self::SELECTION_BESTSELLER) {
            return $this->rankedLabel();
        }

        return self::SELECTIONS[$this->selection] ?? (string) $this->selection;
    }

    public function contentSummary(): string
    {
        $source = self::SOURCES[$this->source] ?? $this->source;
        $summary = $source.' · '.$this->selectionLabel();

        if ($this->selection === self::SELECTION_CATEGORY && $this->category) {
            $summary .= ' · '.$this->category->name;
        }

        return $summary;
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
