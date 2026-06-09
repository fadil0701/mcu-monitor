<!DOCTYPE html>
<html
  lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="{{ asset('assets/') }}/"
  data-template="vertical-menu-template-free"
>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auth') - {{ config('app.name', 'Monitoring MCU') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/img/icon-ppkp.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/mcu-admin.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}" />
    <style>
        .authentication-inner .app-brand {
            width: 100%;
            overflow: hidden;
        }
        .authentication-inner .app-brand .app-brand-link {
            display: inline-block;
            max-width: 100%;
        }
        .authentication-inner .app-brand-logo-img--auth {
            display: block;
            height: 48px;
            width: auto;
            max-width: 100%;
            margin: 0 auto;
            object-fit: contain;
        }
        .authentication-wrapper.authentication-basic .authentication-inner.auth-form-wide {
            max-width: 960px;
        }
        .auth-form-field-row > [class*="col-"] {
            align-items: stretch;
        }
        .form-field-stack {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .form-field-stack > .form-label {
            margin-bottom: 0.5rem;
        }
        .form-field-stack > .form-field-control {
            display: flex;
            flex-direction: column;
        }
        .form-field-stack .form-control,
        .form-field-stack .instansi-combobox .form-control {
            width: 100%;
            min-height: calc(1.53em + 0.875rem + 2px);
            padding: 0.4375rem 0.875rem;
            font-size: 0.9375rem;
            line-height: 1.53;
        }
        .form-field-stack .instansi-combobox {
            display: block;
            width: 100%;
        }
        .form-field-stack select[data-instansi-searchable]:not([data-instansi-searchable-init="1"]) {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
    @stack('page-css')
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>
<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner @yield('auth-inner-class')" @hasSection('auth-max-width') style="max-width: @yield('auth-max-width');" @endif>
                <div class="card">
                    <div class="card-body">
                        @include('layouts.sneat.partials.brand')

                        @hasSection('heading')
                            <h4 class="mb-2">@yield('heading')</h4>
                        @endif
                        @hasSection('subheading')
                            <p class="mb-4">@yield('subheading')</p>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if(session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')

                        @hasSection('footer-links')
                            <div class="mt-3">@yield('footer-links')</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
