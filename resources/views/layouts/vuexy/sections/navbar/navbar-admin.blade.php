@php
$containerNav = ($configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
$admin = Auth::guard('cms')->user();
@endphp

<nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
      <i class="ti ti-menu-2 ti-sm"></i>
    </a>
  </div>

  <div class="navbar-nav-right d-flex align-items-center flex-grow-1" id="navbar-collapse">
    <div class="navbar-nav align-items-center flex-grow-1"></div>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
      <li class="nav-item me-2">
        <a href="{{ route('home') }}" target="_blank" class="btn btn-sm btn-label-primary">
          <i class="ti ti-external-link ti-xs me-1"></i>
          مشاهده سایت
        </a>
      </li>

      @if ($configData['hasCustomizer'])
      <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-0">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <i class="ti ti-md"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-start dropdown-styles">
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
              <span class="align-middle"><i class="ti ti-sun me-2"></i>روشن</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
              <span class="align-middle"><i class="ti ti-moon me-2"></i>تیره</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
              <span class="align-middle"><i class="ti ti-device-desktop me-2"></i>سیستم</span>
            </a>
          </li>
        </ul>
      </li>
      @endif

      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="ti ti-user ti-sm"></i>
            </span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <div class="dropdown-item">
              <div class="d-flex">
                <div class="flex-shrink-0 me-3">
                  <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                      <i class="ti ti-user ti-sm"></i>
                    </span>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <span class="fw-medium d-block">{{ $admin?->name ?? 'مدیر' }}</span>
                  <small class="text-muted">{{ $admin?->email }}</small>
                </div>
              </div>
            </div>
          </li>
          <li><div class="dropdown-divider"></div></li>
          <li>
            <form method="POST" action="{{ route('admin.logout') }}" id="admin-logout-form">
              @csrf
              <a class="dropdown-item" href="{{ route('admin.logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                <i class="ti ti-logout me-2"></i>
                <span class="align-middle">خروج</span>
              </a>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
