<?php

namespace App\Console\Commands;

use App\Services\RankMathRedirectImporter;
use Illuminate\Console\Command;

class ImportRankMathRedirectsCommand extends Command
{
    protected $signature = 'seo:import-rankmath-redirects
                            {path? : Path to Rank Math export file}
                            {--stdin : Read redirect lines from STDIN}';

    protected $description = 'Import Rank Math redirect export into cms_redirects (301, no chains preferred)';

    public function handle(RankMathRedirectImporter $importer): int
    {
        if ($this->option('stdin')) {
            $payload = stream_get_contents(STDIN) ?: '';
        } else {
            $path = $this->argument('path');
            if (! $path || ! is_file($path)) {
                $this->error('Provide a file path or --stdin.');

                return self::FAILURE;
            }
            $payload = (string) file_get_contents($path);
        }

        $result = $importer->import($payload);

        $this->info("Imported: {$result['imported']}, skipped: {$result['skipped']}");

        foreach ($result['chains'] as $chain) {
            $this->warn($chain);
        }

        return self::SUCCESS;
    }
}
