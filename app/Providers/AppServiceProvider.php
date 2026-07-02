<?php

namespace App\Providers;

use App\Support\InstansiPemprovDkiCatalog;
use App\Support\UserPasswordRules;
use App\Support\WhatsAppSendSettings;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => UserPasswordRules::defaults());

        View::composer([
            'auth.register',
            'auth.peserta-register',
            'client.profile',
            'admin.participants.create',
            'admin.participants.edit',
            'admin.reports.index',
            'admin.reschedule-center.index',
        ], function ($view): void {
            $view->with('instansiPemprov', InstansiPemprovDkiCatalog::optionsForForms());
        });

        View::composer([
            'admin.schedules.index',
            'admin.mcu-results.index',
            'admin.mcu-results.edit',
        ], function ($view): void {
            $view->with('whatsappSendEnabled', WhatsAppSendSettings::buttonsEnabled());
        });

        Paginator::defaultView('vendor.pagination.bootstrap-5');
        Paginator::defaultSimpleView('vendor.pagination.simple-bootstrap-5');

        if (! $this->app->runningInConsole() && $this->app->bound('request')) {
            $request = $this->app->make('request');

            if ($this->shouldUseRequestOrigin($request)) {
                $root = $request->getSchemeAndHttpHost();
                URL::forceRootUrl($root);
                URL::useAssetOrigin($root);
            } else {
                $appUrl = config('app.url');
                if (is_string($appUrl) && $appUrl !== '') {
                    URL::forceRootUrl(rtrim($appUrl, '/'));
                    if (str_starts_with($appUrl, 'https://')) {
                        URL::forceScheme('https');
                    }
                }
            }
        }
    }

    private function shouldUseRequestOrigin(Request $request): bool
    {
        if (! filter_var(env('APP_USE_REQUEST_URL', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return (int) $request->getPort() === (int) env('APP_PORT', 9003);
    }
}
