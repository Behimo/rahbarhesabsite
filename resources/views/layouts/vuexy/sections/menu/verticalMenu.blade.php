@php
use App\Support\AccessCatalog;

$configData = Helper::appClasses();
$menuRoute = Route::currentRouteName() ?? '';
$adminUser = auth()->user();
$routePage = request()->route('page');
$editingHomePage = $routePage instanceof \App\Models\CmsPage && $routePage->slug === 'home';

$menuSlugActive = function ($slug) use ($menuRoute, $editingHomePage): bool {
    foreach (is_array($slug) ? $slug : [$slug] as $candidate) {
        if (! is_string($candidate) || $candidate === '') {
            continue;
        }

        $isHomeItem = $candidate === 'admin.home' || str_starts_with($candidate, 'admin.home.');
        $isPagesItem = $candidate === 'admin.pages' || str_starts_with($candidate, 'admin.pages.');

        if ($editingHomePage && $isHomeItem) {
            return true;
        }
        if ($editingHomePage && $isPagesItem) {
            continue;
        }
        if ($menuRoute === $candidate || str_starts_with($menuRoute, $candidate.'.')) {
            return true;
        }
    }

    return false;
};

$menuSlugAllowed = function ($slug) use ($adminUser): bool {
    if (! $adminUser || ! is_string($slug) || $slug === '') {
        return true;
    }

    foreach (AccessCatalog::routeMap() as $pattern => $permission) {
        $prefix = str_ends_with($pattern, '.*') ? substr($pattern, 0, -2) : $pattern;
        $matches = str_ends_with($pattern, '.*')
            ? ($slug === $prefix || str_starts_with($slug, $prefix.'.'))
            : $slug === $pattern;

        if ($matches) {
            return AccessCatalog::allows($adminUser, $permission);
        }
    }

    return true;
};

$menuEntries = [];
foreach ($menuData[0]->menu as $item) {
    if (isset($item->menuHeader)) {
        $menuEntries[] = $item;
        continue;
    }

    $entry = clone $item;
    if (isset($entry->submenu)) {
        $entry->submenu = array_values(array_filter($entry->submenu, function ($sub) use ($menuSlugAllowed) {
            $slug = $sub->slug ?? null;
            foreach (is_array($slug) ? $slug : [$slug] as $candidate) {
                if ($menuSlugAllowed(is_string($candidate) ? $candidate : '')) {
                    return true;
                }
            }

            return false;
        }));
        if ($entry->submenu === []) {
            continue;
        }
    } else {
        $visible = false;
        foreach (is_array($entry->slug ?? null) ? $entry->slug : [$entry->slug ?? null] as $candidate) {
            if ($menuSlugAllowed(is_string($candidate) ? $candidate : '')) {
                $visible = true;
                break;
            }
        }
        if (! $visible) {
            continue;
        }
    }

    $menuEntries[] = $entry;
}

$menuItems = [];
$entryCount = count($menuEntries);
foreach ($menuEntries as $index => $entry) {
    if (! isset($entry->menuHeader)) {
        $menuItems[] = $entry;
        continue;
    }

    $hasItem = false;
    for ($next = $index + 1; $next < $entryCount; $next++) {
        if (isset($menuEntries[$next]->menuHeader)) {
            break;
        }
        $hasItem = true;
        break;
    }
    if ($hasItem) {
        $menuItems[] = $entry;
    }
}
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" aria-label="منوی مدیریت">

  @if(!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
      <span class="app-brand-logo demo">
        <img src="{{ asset(\App\Models\CmsSetting::get('site_logo', config('cms.branding.logo'))) }}" alt="">
      </span>
      <span class="app-brand-text demo menu-text fw-bold">{{ config('cms.site_name_fa') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto" aria-label="بستن منو">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuItems as $menu)
    @if (isset($menu->menuHeader))
    <li class="menu-header">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>
    @else
    @php
      $activeClass = '';
      if (isset($menu->submenu) && $menuSlugActive($menu->slug ?? null)) {
          $activeClass = 'active open';
      } elseif (! isset($menu->submenu) && $menuSlugActive($menu->slug ?? null)) {
          $activeClass = 'active';
      }
    @endphp
    <li class="menu-item {{ $activeClass }}">
      <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if ($activeClass === 'active') aria-current="page" @endif @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
        @isset($menu->icon)
        <i class="{{ $menu->icon }}"></i>
        @endisset
        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
        @isset($menu->badge)
        <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
        @endisset
      </a>

      @isset($menu->submenu)
      @include('layouts.vuexy.sections.menu.submenu',['menu' => $menu->submenu])
      @endisset
    </li>
    @endif
    @endforeach
  </ul>

</aside>
