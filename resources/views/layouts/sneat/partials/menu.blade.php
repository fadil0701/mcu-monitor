<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ Auth::user()->isAdmin() ? route('dashboard') : route('client.dashboard') }}" class="app-brand-link">
            <!-- <img
                src="{{ asset('assets/img/logo-ppkp.png') }}"
                alt="PPKP DKI Jakarta"
                class="app-brand-logo-img app-brand-logo-img--sidebar"
                style="display:block;height:40px;width:auto;max-width:100%;object-fit:contain;"
            > -->
            <span class="app-brand-text text-primary fw-bold fs-4">Monitoring MCU</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach(\App\Helpers\MenuHelper::getMainNavItems() as $item)
            @if(($item['type'] ?? null) === 'header')
                <li class="menu-header small text-uppercase mcu-menu-section">
                    <span class="menu-header-text">{{ $item['name'] }}</span>
                </li>
            @elseif(isset($item['subItems']))
                @php $subActive = \App\Helpers\MenuHelper::isSubmenuActive($item['subItems']); @endphp
                <li class="menu-item {{ $subActive ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx {{ $item['icon'] ?? 'bx-grid-alt' }}"></i>
                        <div class="d-flex align-items-center justify-content-between w-100 pe-1">
                            <span>{{ $item['name'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="badge rounded-pill bg-danger">{{ $item['badge'] }}</span>
                            @endif
                        </div>
                    </a>
                    <ul class="menu-sub">
                        @foreach($item['subItems'] as $sub)
                            <li class="menu-item {{ \App\Helpers\MenuHelper::isActive($sub['path']) ? 'active' : '' }}">
                                <a href="{{ $sub['path'] }}" class="menu-link">
                                    <div class="d-flex align-items-center justify-content-between w-100 pe-1">
                                        <span>{{ $sub['name'] }}</span>
                                        @if(!empty($sub['badge']))
                                            <span class="badge rounded-pill bg-danger">{{ $sub['badge'] }}</span>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="menu-item {{ \App\Helpers\MenuHelper::isActive($item['path']) ? 'active' : '' }}">
                    <a href="{{ $item['path'] }}" class="menu-link">
                        <i class="menu-icon tf-icons bx {{ $item['icon'] ?? 'bx-circle' }}"></i>
                        <div class="d-flex align-items-center justify-content-between w-100 pe-1">
                            <span>{{ $item['name'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="badge rounded-pill bg-danger">{{ $item['badge'] }}</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</aside>
