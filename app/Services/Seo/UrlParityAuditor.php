<?php

namespace App\Services\Seo;

use App\Models\CmsRedirect;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Throwable;

class UrlParityAuditor
{
    public const ORIGIN = 'https://rahbarhesab.com';

    public const COLUMNS = [
        'old_url', 'new_url', 'source', 'expected', 'status', 'location', 'result',
        'canonical', 'robots', 'title', 'h1', 'h1_count', 'h2_count', 'main_count',
        'article_count', 'images_missing_alt', 'internal_links', 'wp_title', 'wp_h1',
        'wp_canonical', 'wp_robots', 'wp_h2_count', 'wp_internal_links', 'notes',
    ];

    private const FETCH_ATTEMPTS = 3;

    private array $urls = [];

    private array $visited = [];

    private array $responses = [];

    /** @return list<array<string, mixed>> */
    public function audit(array $options, ?callable $progress = null): array
    {
        $this->urls = $this->visited = $this->responses = [];
        $this->collectSitemap($options['sitemap'] ?? self::ORIGIN.'/sitemap_index.xml');
        if ($options['with_db'] ?? false) {
            $this->collectDatabase();
        }
        $entries = array_values($this->urls);
        if ($entries === []) {
            throw new RuntimeException('No URLs collected; check the sitemap input.');
        }
        if (! empty($options['limit'])) {
            $entries = array_slice($entries, 0, (int) $options['limit']);
        }
        $redirects = CmsRedirect::query()->where('is_active', true)->get();
        foreach ($entries as &$entry) {
            parse_str(parse_url($entry['path'], PHP_URL_QUERY) ?? '', $query);
            foreach ($redirects as $redirect) {
                $source = $this->path($redirect->from_path);
                $matches = parse_url($source, PHP_URL_QUERY) !== null
                    ? $source === $entry['path']
                    : rtrim(strtok($source, '?'), '/') === rtrim(strtok($entry['path'], '?'), '/');
                parse_str((string) $redirect->from_query, $required);
                if ($matches && (! $redirect->match_query || ($required !== [] && array_intersect_key($query, $required) == $required))) {
                    $entry['expected'] = 301;
                    break;
                }
            }
        }
        unset($entry);
        $target = rtrim($options['target'] ?? '', '/');
        $environment = app()->environment();
        $originalRequest = app('request');
        $guard = Auth::guard();
        $originalUser = $guard->user();
        $connection = DB::connection();
        $level = $connection->transactionLevel();
        $rows = [];
        try {
            if ($target === '') {
                $connection->beginTransaction();
                app()->detectEnvironment(fn () => 'production');
                $guard->logout();
            } else {
                $this->fetchBatch(array_map(fn ($entry) => $target.$entry['path'], array_filter($entries, fn ($entry) => $entry['type'] !== 'kml')), 8, true);
            }
            if ($options['compare_live'] ?? false) {
                $this->fetchBatch(array_map(fn ($entry) => self::ORIGIN.$entry['path'], array_filter($entries, fn ($entry) => $entry['type'] !== 'kml')), 4, false);
            }
            foreach ($entries as $entry) {
                $row = $this->inspectEntry($entry, $target, (bool) ($options['compare_live'] ?? false));
                $rows[] = $row;
                $progress && $progress(count($rows), count($entries));
            }
        } finally {
            if ($target === '') {
                while ($connection->transactionLevel() > $level) {
                    $connection->rollBack();
                }
                app()->detectEnvironment(fn () => $environment);
                app()->instance('request', $originalRequest);
                $guard->forgetUser();
                if ($originalUser !== null) {
                    $guard->setUser($originalUser);
                }
            }
        }
        $order = ['missing' => 0, 'error' => 0, 'redirected' => 1, 'warn' => 2, 'ok' => 3, 'ok-redirect' => 3, 'kml' => 4];
        usort($rows, fn ($a, $b) => [$order[$a['result']], $a['source'], $a['old_url']] <=> [$order[$b['result']], $b['source'], $b['old_url']]);

        return $rows;
    }

    public function writeCsv(array $rows, string $output): void
    {
        if (! is_dir(dirname($output)) && ! mkdir(dirname($output), 0755, true) && ! is_dir(dirname($output))) {
            throw new RuntimeException('Cannot create output directory.');
        }
        $file = fopen($output, 'wb');
        if ($file === false) {
            throw new RuntimeException('Cannot write CSV: '.$output);
        }
        try {
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, self::COLUMNS, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($file, array_map(fn ($key) => $row[$key] ?? '', self::COLUMNS), ',', '"', '');
            }
        } finally {
            fclose($file);
        }
    }

    public function path(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $path = '/'.ltrim($path, '/');
        $path = implode('/', array_map(fn ($part) => rawurlencode(rawurldecode($part)), explode('/', $path)));
        $query = parse_url($url, PHP_URL_QUERY);

        return $path.($query !== null && $query !== '' ? '?'.$query : '');
    }

    private function add(string $url, string $source, int $expected = 200, string $notes = ''): void
    {
        $path = $this->path($url);
        if (str_starts_with(rawurldecode($path), '/wp-content/')) {
            return;
        }
        $type = str_ends_with(strtok($path, '?'), '.kml') ? 'kml' : 'html';
        if (! isset($this->urls[$path])) {
            $this->urls[$path] = compact('path', 'expected', 'notes', 'type') + ['sources' => []];
        }
        $this->urls[$path]['sources'][$source] = true;
        if ($expected === 301) {
            $this->urls[$path]['expected'] = 301;
        }
        if ($notes !== '') {
            $this->urls[$path]['notes'] = trim($this->urls[$path]['notes'].'; '.$notes, '; ');
        }
    }

    private function collectSitemap(string $source): void
    {
        if (isset($this->visited[$source])) {
            return;
        }
        $this->visited[$source] = true;
        $remote = preg_match('~^https?://~i', $source);
        $xml = $remote ? Http::timeout(30)->retry(2, 500)->get($source)->throw()->body() : @file_get_contents($source);
        if ($xml === false) {
            throw new RuntimeException('Cannot read sitemap: '.$source);
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            if ($document === false) {
                throw new RuntimeException('Invalid sitemap XML: '.$source);
            }
            foreach ($document->xpath('/*[local-name()="sitemapindex"]/*[local-name()="sitemap"]/*[local-name()="loc"]') as $child) {
                $child = trim((string) $child);
                if ($remote) {
                    $child = $this->resolveUrl($source, $child);
                    if (parse_url($child, PHP_URL_HOST) !== parse_url($source, PHP_URL_HOST)) {
                        throw new RuntimeException('Cross-host sitemap refused: '.$child);
                    }
                } elseif (! preg_match('~^https?://~i', $child) && ! is_file($child)) {
                    $child = dirname($source).DIRECTORY_SEPARATOR.$child;
                }
                $this->collectSitemap($child);
            }
            foreach ($document->xpath('/*[local-name()="urlset"]/*[local-name()="url"]/*[local-name()="loc"]') as $location) {
                $this->add(trim((string) $location), basename(parse_url($source, PHP_URL_PATH) ?: $source));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function collectDatabase(): void
    {
        $wp = DB::connection('wordpress');
        $options = $wp->table('options')->whereIn('option_name', ['category_base', 'posts_per_page', 'woocommerce_catalog_rows', 'woocommerce_catalog_columns'])->pluck('option_value', 'option_name');
        $perPage = max(1, (int) ($options['posts_per_page'] ?? 20));
        $estimated = empty($options['woocommerce_catalog_rows']) || empty($options['woocommerce_catalog_columns']);
        $productsPerPage = $estimated
            ? max(1, (int) config('seo.per_page.products'))
            : max(1, (int) $options['woocommerce_catalog_rows'] * (int) $options['woocommerce_catalog_columns']);
        $productsNote = $estimated ? 'products per page='.$productsPerPage.' (seo.per_page.products)' : '';
        $posts = $wp->table('posts')->where('post_status', 'publish')->whereIn('post_type', ['post', 'qa', 'product', 'page'])->get()->keyBy('ID');
        $counts = $posts->countBy('post_type');
        foreach (['post' => ['/blog/', $perPage], 'qa' => ['/questions/', (int) config('seo.per_page.questions')], 'product' => ['/product/', $productsPerPage]] as $type => [$base, $size]) {
            $this->addPagination($base, (int) ($counts[$type] ?? 0), $size, $type === 'product' ? $productsNote : '');
        }
        $terms = $wp->table('term_taxonomy as tt')->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->whereIn('tt.taxonomy', ['category', 'post_tag', 'product_cat'])
            ->select(['tt.term_taxonomy_id', 'tt.term_id', 'tt.parent', 'tt.taxonomy', 't.slug'])->get()->keyBy('term_taxonomy_id');
        $relations = $wp->table('term_relationships')->whereIn('object_id', $posts->keys()->all())->get();
        $objects = [];
        $categories = [];
        foreach ($relations as $relation) {
            $term = $terms[$relation->term_taxonomy_id] ?? null;
            $post = $posts[$relation->object_id] ?? null;
            if (! $term || ! $post) {
                continue;
            }
            $allowed = match ($term->taxonomy) {
                'category' => $post->post_type === 'post',
                'post_tag' => in_array($post->post_type, ['post', 'qa'], true),
                default => $post->post_type === 'product',
            };
            if (! $allowed) {
                continue;
            }
            $objects[$term->term_taxonomy_id][$post->ID] = true;
            if ($term->taxonomy === 'category') {
                $categories[$post->ID][$term->term_id] = $term->slug;
            }
        }
        $byTaxonomy = $terms->groupBy('taxonomy');
        foreach ($terms as $term) {
            $set = $objects[$term->term_taxonomy_id] ?? [];
            if ($term->taxonomy !== 'post_tag') {
                foreach ($byTaxonomy[$term->taxonomy] as $descendant) {
                    $parent = $descendant->parent;
                    $seen = [];
                    while ($parent && ! isset($seen[$parent])) {
                        if ((int) $parent === (int) $term->term_id) {
                            $set += $objects[$descendant->term_taxonomy_id] ?? [];
                            break;
                        }
                        $seen[$parent] = true;
                        $parent = $byTaxonomy[$term->taxonomy]->firstWhere('term_id', $parent)?->parent;
                    }
                }
            }
            if ($set === []) {
                continue;
            }
            $slug = $term->slug;
            if ($term->taxonomy !== 'post_tag') {
                $parent = $term->parent;
                $seen = [$term->term_id => true];
                while ($parent && ! isset($seen[$parent])) {
                    $ancestor = $byTaxonomy[$term->taxonomy]->firstWhere('term_id', $parent);
                    if (! $ancestor) {
                        break;
                    }
                    $seen[$parent] = true;
                    $slug = $ancestor->slug.'/'.$slug;
                    $parent = $ancestor->parent;
                }
            }
            $base = match ($term->taxonomy) {
                'category' => trim(($options['category_base'] ?? '') ?: 'category', '/'),
                'post_tag' => 'tag',
                default => 'product-category',
            };
            $base = '/'.$base.'/'.$slug.'/';
            $this->add($base, 'db:'.($term->taxonomy === 'post_tag' ? 'tag' : $term->taxonomy));
            $size = $term->taxonomy === 'product_cat' ? $productsPerPage : $perPage;
            $this->addPagination($base, count($set), $size, $term->taxonomy === 'product_cat' ? $productsNote : '');
        }
        $meta = $wp->table('postmeta')->whereIn('post_id', $posts->keys()->all())
            ->whereIn('meta_key', ['_wp_old_slug', 'rank_math_primary_category'])->orderBy('meta_id')->get()->groupBy('post_id');
        foreach ($posts as $post) {
            $rows = $meta[$post->ID] ?? collect();
            $primary = $rows->firstWhere('meta_key', 'rank_math_primary_category')?->meta_value;
            $category = $categories[$post->ID][$primary] ?? (isset($categories[$post->ID]) ? reset($categories[$post->ID]) : 'blog');
            $prefix = match ($post->post_type) {
                'qa' => '/questions/', 'product' => '/product/', 'page' => '/', default => '/'.$category.'/',
            };
            foreach ($rows->where('meta_key', '_wp_old_slug')->pluck('meta_value')->unique() as $old) {
                if ($old !== '' && rawurldecode($old) !== rawurldecode($post->post_name)) {
                    $this->add($prefix.$old.'/', 'db:old-slug', 301, 'old-slug destination: '.$this->path($prefix.$post->post_name.'/'));
                }
            }
        }
    }

    private function addPagination(string $base, int $count, int $size, string $notes): void
    {
        for ($page = 2; $page <= (int) ceil($count / $size); $page++) {
            $this->add($base.'page/'.$page.'/', 'db:pagination', 200, $notes);
        }
    }

    private function fetchBatch(array $urls, int $concurrency, bool $target, int $attempt = 1): void
    {
        $urls = array_values(array_unique($urls));
        foreach (array_chunk($urls, $concurrency) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $target) {
                $requests = [];
                foreach ($chunk as $url) {
                    $request = $pool->as($url)->withOptions(['allow_redirects' => false])->timeout(20);
                    $credentials = $target ? (string) env('SEO_AUDIT_BASIC_AUTH', '') : '';
                    if ($credentials !== '') {
                        [$user, $password] = array_pad(explode(':', $credentials, 2), 2, '');
                        $request->withBasicAuth($user, $password);
                    }
                    $requests[] = $request->get($url);
                }

                return $requests;
            }, $concurrency);
            foreach ($chunk as $url) {
                $response = $responses[$url] ?? new RuntimeException('No HTTP response.');
                $this->responses[($target ? 'target:' : 'live:').$url] = $response instanceof Response
                    ? ['status' => $response->status(), 'location' => $response->header('Location'), 'body' => $response->body(), 'error' => '']
                    : ['status' => 0, 'location' => '', 'body' => '', 'error' => $response instanceof Throwable ? $response->getMessage() : 'Invalid HTTP response.'];
            }
        }

        // The live WordPress host throttles bursts; retry dropped or throttled URLs one at a time.
        $prefix = $target ? 'target:' : 'live:';
        $failed = array_values(array_filter($urls, fn ($url) => in_array($this->responses[$prefix.$url]['status'], [0, 429, 503], true)));
        if ($failed !== [] && $attempt < self::FETCH_ATTEMPTS) {
            Sleep::for($attempt * 5)->seconds();
            $this->fetchBatch($failed, 1, $target, $attempt + 1);
        }
    }

    private function response(string $path, string $target): array
    {
        $url = ($target ?: self::ORIGIN).$path;
        $key = 'target:'.$url;
        if (! isset($this->responses[$key])) {
            if ($target !== '') {
                $this->fetchBatch([$url], 1, true);
            } else {
                $request = Request::create($url, 'GET');
                $request->headers->set('Accept', 'text/html');
                $kernel = app(Kernel::class);
                try {
                    Auth::guard()->forgetUser();
                    $response = $kernel->handle($request);
                    $this->responses[$key] = ['status' => $response->getStatusCode(), 'location' => $response->headers->get('Location', ''), 'body' => $response->getContent(), 'error' => ''];
                    $kernel->terminate($request, $response);
                } catch (Throwable $exception) {
                    $this->responses[$key] = ['status' => 0, 'location' => '', 'body' => '', 'error' => $exception->getMessage()];
                }
            }
        }

        return $this->responses[$key];
    }

    private function inspectEntry(array $entry, string $target, bool $compareLive): array
    {
        $path = $entry['path'];
        $url = ($target ?: self::ORIGIN).$path;
        $row = array_fill_keys(self::COLUMNS, '');
        $row = array_replace($row, ['old_url' => self::ORIGIN.$path, 'new_url' => $url, 'source' => implode('|', array_keys($entry['sources'])), 'expected' => $entry['expected'], 'notes' => $entry['notes']]);
        if ($entry['type'] === 'kml') {
            return array_replace($row, ['result' => 'kml', 'notes' => 'Rank Math Local SEO KML; not an HTML parity success.']);
        }
        $response = $this->response($path, $target);
        $html = $this->extractHtml($response['body']);
        foreach (self::COLUMNS as $column) {
            if (array_key_exists($column, $html)) {
                $row[$column] = $html[$column];
            }
        }
        $row['status'] = $response['status'];
        $row['location'] = $response['location'];
        [$row['result'], $notes] = $this->classify($response, $html, $path, ! $compareLive, $target);
        $intended = $this->intendedRedirect($path);
        if ($intended !== null) {
            $destination = $response['location'] !== '' ? $this->path($this->resolveUrl($url, $response['location'])) : '';
            if ($response['status'] >= 300 && $response['status'] < 400
                && rtrim(rawurldecode($destination), '/') === rtrim($intended, '/')) {
                $row['result'] = 'ok-redirect';
                $row['new_url'] = ($target ?: self::ORIGIN).$destination;
                $notes = ['Intended redirect (seo.account_redirects / seo.audit_expected_redirects).'];
            } else {
                $row['result'] = 'redirected';
                $notes[] = 'Expected redirect to '.$intended;
            }
        } elseif ($entry['expected'] === 301 && $response['status'] === 301) {
            $destination = $this->resolveUrl($url, $response['location']);
            $destinationOrigin = preg_match('~^https?://[^/]+~i', $destination, $match) ? strtolower($match[0]) : '';
            if ($destinationOrigin !== strtolower($target ?: self::ORIGIN)) {
                $row['result'] = 'redirected';
                $notes[] = 'Redirect leaves target origin: '.$destination;
            } else {
                $destinationPath = $this->path($destination);
                $final = $this->response($destinationPath, $target);
                [$result, $destinationNotes] = $this->classify($final, $this->extractHtml($final['body']), $destinationPath, true, $target);
                $row['result'] = $result === 'ok' ? 'ok-redirect' : 'redirected';
                $notes = $result === 'ok' ? [] : ['Redirect destination is not ok: '.$result.'; '.implode('; ', $destinationNotes)];
                if ($result === 'ok') {
                    $row['new_url'] = ($target ?: self::ORIGIN).$destinationPath;
                }
            }
        } elseif ($entry['expected'] === 301 && $response['status'] === 200) {
            $row['result'] = 'warn';
            $notes[] = 'Expected 301, got 200.';
        }
        if ($compareLive) {
            $live = $this->responses['live:'.self::ORIGIN.$path];
            $wp = $this->extractHtml($live['body']);
            foreach (['title', 'h1', 'canonical', 'robots', 'h2_count', 'internal_links'] as $field) {
                $row['wp_'.$field] = $wp[$field];
            }
            if ($live['status'] !== 200) {
                $notes[] = 'Live comparison unavailable: HTTP '.$live['status'].' '.$live['error'];
                if ($row['result'] === 'ok') {
                    $row['result'] = 'warn';
                }
            } elseif ($response['status'] === 200) {
                $differences = [];
                foreach (['title', 'h1', 'description'] as $field) {
                    if ($html[$field] !== $wp[$field]) {
                        $differences[] = 'WP '.$field.' differs';
                    }
                }
                if ($this->path($html['canonical']) !== $this->path($wp['canonical']) || $html['canonical'] === '' || $wp['canonical'] === '') {
                    $differences[] = 'WP canonical differs';
                }
                if (abs($html['internal_links'] - $wp['internal_links']) / max(1, $wp['internal_links']) > 0.3) {
                    $differences[] = 'WP internal links differ >30%';
                }
                if (str_contains(strtolower($html['robots']), 'noindex') && ! str_contains(strtolower($wp['robots']), 'noindex')) {
                    $differences[] = 'robots: unexpected noindex';
                }
                if ($differences !== []) {
                    $row['result'] = 'warn';
                    $notes = array_merge($notes, $differences);
                }
            }
        }
        $row['notes'] = trim(implode('; ', array_filter([$row['notes'], ...$notes])), '; ');

        return $row;
    }

    private function classify(array $response, array $html, string $path, bool $warnNoindex, string $target = ''): array
    {
        $status = $response['status'];
        if ($response['error'] !== '' || $status >= 500 || $status === 0) {
            return ['error', [$response['error'] ?: 'HTTP '.$status]];
        }
        if (in_array($status, [404, 410], true)) {
            return ['missing', ['HTTP '.$status]];
        }
        if ($status >= 300 && $status < 400) {
            return ['redirected', ['Unexpected redirect: '.$response['location']]];
        }
        if ($status !== 200) {
            return ['error', ['Unexpected HTTP '.$status]];
        }
        $notes = [];
        $canonicalOrigin = preg_match('~^https?://[^/]+~i', $html['canonical'], $match) ? strtolower($match[0]) : '';
        if ($html['canonical'] === '' || ! in_array($canonicalOrigin, array_filter([self::ORIGIN, strtolower($target)]), true) || $this->path($html['canonical']) !== $path) {
            $notes[] = 'canonical mismatch';
        }
        if ($html['h1_count'] !== 1) {
            $notes[] = 'H1 count='.$html['h1_count'];
        }
        if ($html['title'] === '') {
            $notes[] = 'title empty';
        }
        if ($warnNoindex && str_contains(strtolower($html['robots']), 'noindex')) {
            $notes[] = 'robots: noindex';
        }
        if ($html['images_missing_alt'] > 0) {
            $notes[] = 'alt: '.$html['images_missing_alt'].' images missing alt';
        }
        $warnings = array_filter($notes, fn ($note) => ! str_starts_with($note, 'alt:'));

        return [$warnings === [] ? 'ok' : 'warn', $notes];
    }

    public function extractHtml(string $body): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.($body ?: '<html></html>'), LIBXML_NONET);
            $xpath = new DOMXPath($document);
            $text = fn (string $query) => trim(preg_replace('/\s+/u', ' ', $xpath->query($query)->item(0)?->textContent ?? ''));
            $attribute = fn (string $query, string $name) => trim($xpath->query($query)->item(0)?->getAttribute($name) ?? '');
            $links = 0;
            foreach ($xpath->query('//a[@href]') as $anchor) {
                $href = trim($anchor->getAttribute('href'));
                $scheme = parse_url($href, PHP_URL_SCHEME);
                $host = parse_url($href, PHP_URL_HOST);
                if (($scheme === null && $host === null) || ($host && in_array(strtolower($host), ['rahbarhesab.com', 'www.rahbarhesab.com'], true))) {
                    $links++;
                }
            }

            return [
                'title' => $text('//title'), 'description' => $attribute('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]', 'content'),
                'canonical' => $attribute('//link[contains(concat(" ",normalize-space(@rel)," ")," canonical ")]', 'href'),
                'robots' => $attribute('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]', 'content'),
                'h1' => implode(' | ', array_map(fn ($node) => trim(preg_replace('/\s+/u', ' ', $node->textContent)), iterator_to_array($xpath->query('//h1')))),
                'h1_count' => $xpath->query('//h1')->length, 'h2_count' => $xpath->query('//h2')->length,
                'main_count' => $xpath->query('//main')->length, 'article_count' => $xpath->query('//article')->length,
                'images_missing_alt' => $xpath->query('//img[not(@alt) or normalize-space(@alt)=""]')->length,
                'internal_links' => $links,
            ];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function intendedRedirect(string $path): ?string
    {
        $decoded = rtrim(rawurldecode((string) parse_url($path, PHP_URL_PATH)), '/');
        $map = (array) config('seo.account_redirects', []) + (array) config('seo.audit_expected_redirects', []);

        return $map[$decoded] ?? null;
    }

    private function resolveUrl(string $base, string $location): string
    {
        return (string) UriResolver::resolve(new Uri($base), new Uri($location));
    }
}
