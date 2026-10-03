<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName =  Route::currentRouteName();

      $menuUrl = (string) ($submenu->url ?? '');
      $menuHasQuery = str_contains($menuUrl, '?');

      if (! $menuHasQuery && $currentRouteName === $submenu->slug) {
          $activeClass = 'active';
      }
      elseif ($menuHasQuery) {
          $targetPath = trim((string) parse_url($menuUrl, PHP_URL_PATH), '/');
          $targetQuery = [];
          parse_str((string) parse_url($menuUrl, PHP_URL_QUERY), $targetQuery);
          $pathMatches = request()->is($targetPath) || request()->is($targetPath.'/*');
          $queryMatches = true;
          foreach ($targetQuery as $key => $value) {
              if ((string) request()->query($key) !== (string) $value) {
                  $queryMatches = false;
              }
          }
          if ($pathMatches && $queryMatches) {
              $activeClass = 'active';
          }
      }
      elseif (isset($submenu->submenu)) {
        if (gettype($submenu->slug) === 'array') {
          foreach($submenu->slug as $slug){
            if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
                $activeClass = $active;
            }
          }
        }
        else{
          if (str_contains($currentRouteName,$submenu->slug) and strpos($currentRouteName,$submenu->slug) === 0) {
            $activeClass = $active;
          }
        }
      }
    @endphp

      <li class="menu-item {{$activeClass}}">
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
          <i class="{{ $submenu->icon }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endisset
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.vuexy.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
  @endif
</ul>
