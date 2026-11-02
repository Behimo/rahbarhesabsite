@php
$containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
@endphp

<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
      <div>
        © {{ date('Y') }}،
        <a href="{{ route('home') }}" class="footer-link fw-medium" target="_blank">راهبرحساب</a>
        — پنل مدیریت محتوا
      </div>
      <div class="d-none d-lg-inline-block">
        <span class="footer-link text-muted">راهبرحساب CMS</span>
      </div>
    </div>
  </div>
</footer>
