<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Models\CmsPost;
use Illuminate\Console\Command;

class CmsPublishScheduledCommand extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publish scheduled CMS pages and posts';

    public function handle(): int
    {
        $pages = CmsPage::query()
            ->where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published', 'is_published' => true]);

        $posts = CmsPost::query()
            ->where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published', 'is_published' => true]);

        $this->info("Published {$pages} pages and {$posts} posts.");

        return self::SUCCESS;
    }
}
