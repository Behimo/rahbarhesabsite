# راهنمای توسعه CMS راهبر حساب

## بلوک چیدمان ستونی

بلوک `columns` ساختار تو در تو دارد:

```
ستون → ۴ قسمت → چند بلوک
```

هر ستون دقیقاً ۴ قسمت دارد. در هر قسمت می‌توانید بلوک‌های دیگر (متن، تصویر، FAQ و …) اضافه کنید. بلوک `columns` داخل قسمت‌ها قابل استفاده نیست.

## افزودن بلوک جدید

### روش ۱ — کلاس در `app/Blocks/`

1. کلاس جدید بسازید که `AbstractBlock` را extend کند.
2. view مربوطه را در `resources/views/blocks/` اضافه کنید.
3. کلاس را در `config/cms.php` → `blocks` ثبت کنید.

```php
// config/cms.php
'blocks' => [
    \App\Blocks\PricingBlock::class,
],
```

### ساختار schema

هر فیلد در `schema()` یکی از این typeها را دارد:

| type | توضیح |
|------|--------|
| `text` | ورودی متنی |
| `textarea` | متن چندخطی |
| `richtext` | HTML (در view با `{!! !!}` رندر شود) |
| `number` | عدد |
| `select` | لیست انتخاب (`options`) |
| `image` | URL تصویر |
| `code` | HTML/کد خام |
| `repeater` | لیست تکرارشونده (`fields`) |

مثال:

```php
public function schema(): array
{
    return [
        'title' => ['type' => 'text', 'label' => 'عنوان', 'default' => ''],
        'items' => [
            'type' => 'repeater',
            'label' => 'آیتم‌ها',
            'default' => [['label' => '']],
            'fields' => [
                'label' => ['type' => 'text', 'label' => 'برچسب', 'default' => ''],
            ],
        ],
    ];
}
```

## افزودن قالب

1. پوشه `themes/{slug}/` با `theme.json` و `views/`.
2. `php artisan cms:discover`
3. از پنل `/admin/themes` ZIP آپلود یا فعال‌سازی کنید.

## افزودن افزونه

1. پوشه `plugins/{Slug}/` با `plugin.json` و ServiceProvider.
2. `php artisan cms:discover`
3. از پنل `/admin/plugins` فعال کنید.

## REST API

Header: `Authorization: Bearer {CMS_API_TOKEN}`

- `GET /api/v1/pages`
- `GET /api/v1/posts`
- `GET /api/v1/courses`
