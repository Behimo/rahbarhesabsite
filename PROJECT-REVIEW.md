# 🔍 Rahbar Hesab — Project Review

**Date:** 2026-10-31 · **Stack:** Laravel 12.62 / PHP 8.3 / Blade + Alpine + Vite · **Scope:** full codebase review

---

## 1. Overall health (what was verified)

| Check | Result |
|---|---|
| App boots (`php artisan about`) | ✅ OK (local env, debug ON) |
| Test suite | ✅ **467 passed / 2299 assertions**, 75.5s |
| Code style (`vendor/bin/pint --test`) | ✅ Clean |
| PHP syntax across `app/config/routes/database` | ✅ No errors |
| `composer audit` | ⚠️ **27 advisories / 8 packages** (see §2.1) |
| Debug leftovers (`dd/dump/var_dump`) | ✅ None |
| TODO/FIXME in app code | ✅ None |

The codebase is mature and unusually well-hardened for a CMS/shop:
payment callbacks are guarded by per-attempt tokens + amount verification +
row/order locks, lesson files use signed URLs, OTP is rate-limited and
hash_equals-compared, admin RBAC is centralized, and there is a large,
meaningful test suite. The issues below are mostly **dependency, hygiene and
configuration** problems rather than architectural ones.

---

## 2. Security issues

### 2.1 🔴 Outdated Composer dependencies — 27 security advisories
`composer audit` reports **27 advisories across 8 packages**:

- **critical** — `laravel/framework` **CVE-2026-114509** (TokenGuard auth bypass via non-string API credentials). The app does **not** use `TokenGuard` (custom `EnsureApiToken` middleware + session guard), so it is likely not exploitable here — but update anyway.
- **high** — `guzzlehttp/guzzle` CVE-2026-69246 (host-based check bypass; Guzzle is used for payment/SMS/Spotplayer HTTP calls), `symfony/http-foundation` CVE-2026-108082, `symfony/yaml` CVE-2026-109413, `league/commonmark` (multiple high).
- **medium/low** — guzzle, psr7, symfony/mime, league/flysystem, league/commonmark.

Patched versions are already available (`composer update --dry-run`):
laravel 12.70.2, guzzle 7.16.1, psr7 2.13.2, symfony 7.4.22, commonmark 2.10.6, spatie/laravel-permission 8.3.2.
**Fix:** run `composer update` + re-run tests before any deploy.

### 2.2 🟠 `CMS_API_TOKEN=change-me` → public API is dead
`.env` still holds the sentinel default `change-me`, which `EnsureApiToken`
explicitly rejects (`strcasecmp(..., 'change-me') === 0` → 401). That means
**every `/api/v1/*` endpoint always returns 401**. Either set a real token
(and rotate it), or remove the API routes if they are not needed at launch.

### 2.3 🟠 HTML sanitizer gaps (defense-in-depth)
`app/Support/HtmlSanitizer::clean()` strips `<script>`/`<iframe>`, `on*`
handlers and `javascript:|data:|vbscript:` in `href`/`src`, but:

- `<style>` tags and `style="..."` attributes are **not** stripped (CSS
  injection / layout breaking, legacy CSS exfil vectors).
- `data:`/`javascript:` URLs are only filtered in `href`/`src`, not in
  `style`/`background` attributes.
- `pages.dynamic` / `pages.wp-imported` render `$bodyHtml` **raw** without
  `clean_html()` (only `ContentUrlRewriter` runs). Content is admin-authored /
  imported so risk is lower, but there is no sanitize-on-write for migrated
  WP bodies either. Apply `clean_html()` (or sanitize at import) for parity
  with the block renderers.

### 2.4 🟠 Deployment foot-gun: debug + env
- `.env` has `APP_DEBUG=true` and `APP_ENV=local`. If this file is ever copied
  to production, `APP_DEBUG=true` exposes env vars, DB credentials and config
  via the error page.
- `NormalizeSeoUrl` is documented (`.env.example`) to produce an **HTTPS 301
  redirect loop** if `APP_ENV=production` without `TRUSTED_PROXIES`. Fine
  locally, but a real deploy risk — make sure `TRUSTED_PROXIES` is set on the
  host.

### 2.5 🟠 258 MB SQL dump in the project root
`lztukinl_njkfjhdjkn.sql` (247 MB, dated 17 Oct) sits in the repo root. It is
gitignored (`*.sql`), so it is **not** in git history — but it is a full,
randomly-named dump of the live DB sitting on the working disk/server.
**Fix:** move it out of the project into a secured/encrypted backup location
and restrict permissions.

---

## 3. Operations & hygiene

### 3.1 🟠 906 MB of legacy WP media in `public/`
`public/wp-content/uploads` is ~906 MB (mp3, mp4, installer `.zip` files such
as `rahbardesk-windows-x64-msi*.zip`, `.svg`). It is not tracked (only
`.gitkeep`) and served statically from the web root. Consequences:
- blows up local backups/sync and disk usage (project total is **2.7 GB**);
- installers / media are publicly downloadable straight from `/wp-content/…`;
- no access logging or expiration.

**Fix:** move media to object storage (or at least outside `public` with a
controlled download route), keep only referenced assets.

### 3.2 🟠 Two frontend lockfiles committed
Both `package-lock.json` (npm) **and** `pnpm-lock.yaml` are committed while
`composer.json` uses `npm`. This is inconsistent — pick one package manager
and delete the other lockfile, otherwise CI/local installs can diverge.

### 3.3 🟠 Dependabot does not watch composer/npm
`.github/dependabot.yml` only covers `github-actions`. That is exactly why the
composer tree fell 27 advisories behind. Add `composer` and `npm` (or `pnpm`)
ecosystems.

### 3.4 🟡 Generated artifacts committed to git
- `storage/framework/route-list.json` (48 KB) is tracked — it is a generated
  cache artifact that changes on every deploy. Remove from git + add to
  `.gitignore`.
- `storage/app` contains leftover temp files: `_export_one.php`,
  `_tmp_privacy_dump.php`, `_privacy_body.html`, `_privacy_sheets.json`.

### 3.5 🟡 `package.json` bloat
The manifest is named **"Vuexy"** (commercial admin theme) and lists a very
large dependency set (jquery, bootstrap, datatables, ckeditor, quill, mapbox,
leaflet, chart.js, apexcharts, fullcalendar, pdfmake, …). `public/build` is
~23 MB. Many are likely unused for this site — prune to reduce build time,
disk and supply-chain surface. `overrides`/`resolutions` duplicate the same
pins.

### 3.6 🟡 Database-backed cache/queue/session
`CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`.
Fine for a small site, but the DB becomes the bottleneck under any load spike
(OTP sends, Spotplayer license jobs, sitemap caching). The `.env.example`
already documents Redis — enable it before scaling.

---

## 4. Code issues (minor)

### 4.1 🟡 Dead feature: top-bar settings
`top_bar_*` settings (enabled/mode/text/button/image) are fully implemented in
`Admin\SettingController` + `SettingRequest` + the admin settings page, but are
**never rendered anywhere on the public site** (no reference in
`layouts/site.blade.php`, `components/navbar.blade.php`, or `SiteDataService`).
Either wire them into the layout or remove the settings.

### 4.2 🟡 Settings cache delay
`CmsSetting::get()` caches each key for 1 hour with invalidation only on
`set()`. Fine in general, but edits made directly in DB or by another process
can take up to an hour to appear on the site.

### 4.3 🟡 Migrations use future timestamps (2026_*)
Convention is fine, but combined with the 2026 system clock it can confuse
tooling that sorts by filename. No action needed unless it bites.

---

## 5. What's genuinely good (keep it this way)
- Payment flow: per-attempt callback tokens (`hash_equals`), reported-amount
  verification, `lockForUpdate` + `Cache::lock` on order/payment, idempotent
  `markPaid`, `afterCommit` license jobs.
- Auth: OTP throttling (phone + IP), cooldown, max attempts, signed URLs for
  lesson files, session regeneration on login/logout.
- RBAC centralized in `AccessCatalog` + middleware; no raw SQL injection
  points; uploads restricted to images/PDF ≤ 5 MB.
- No debug calls, no TODO/FIXME, clean `pint`, and a serious test suite
  (467 passing).

---

## 6. Suggested priority order

1. `composer update` → re-run `composer audit` + `php artisan test` (fixes §2.1).
2. Move `lztukinl_njkfjhdjkn.sql` off the project (fixes §2.5).
3. Set a real `CMS_API_TOKEN` or remove `/api/v1/*` (fixes §2.2).
4. Add composer + npm ecosystems to dependabot (fixes §3.3).
5. Decide the single package manager; delete the other lockfile (fixes §3.2).
6. Untrack `storage/framework/route-list.json`; delete stray `storage/app` files (fixes §3.4).
7. Extend `HtmlSanitizer` (strip `<style>`, style attrs) and sanitize imported
   page bodies (fixes §2.3).
8. Implement or remove the top-bar settings (fixes §4.1).
9. Plan media offloading from `public/wp-content` (fixes §3.1) — larger task.
