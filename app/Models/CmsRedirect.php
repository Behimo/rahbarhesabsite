<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsRedirect extends Model
{
    protected $fillable = [
        'from_path', 'to_path', 'status_code', 'is_active',
        'match_query', 'from_query', 'source',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'match_query' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Keep from_query a plain string (never NULL) so the composite unique
        // index and the unique validation rule behave consistently: MySQL treats
        // NULL as distinct, which previously allowed duplicate from_path rows.
        static::saving(function (self $redirect): void {
            $redirect->from_query = (string) ($redirect->from_query ?? '');
        });
    }
}
