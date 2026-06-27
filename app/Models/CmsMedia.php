<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CmsMedia extends Model
{
    protected $fillable = ['disk', 'path', 'filename', 'mime_type', 'size', 'alt'];

    public function url(): string
    {
        if ($this->disk === 'public') {
            return asset('storage/'.$this->path);
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
