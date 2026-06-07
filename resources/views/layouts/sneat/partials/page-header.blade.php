@if(isset($pageTitle) || View::hasSection('pageTitle'))
    <h4 class="fw-bold py-3 mb-4">
        <span class="breadcrumb-muted">@yield('breadcrumb', 'Monitoring MCU') /</span>
        @yield('pageTitle', $pageTitle ?? 'Halaman')
    </h4>
@endif
