<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceRootUrl((string) config('app.url'));

        View::composer('*', function ($view): void {
            static $uiSettings = null;
            static $currentSchoolType = null;

            if ($uiSettings === null) {
                $defaults = [
                    'app_logo' => 'assets/img/logo.png',
                    'background_image' => 'assets/img/illustrations/auth-login-illustration-light.png',
                ];

                try {
                    if (Schema::hasTable('settings')) {
                        $settings = DB::table('settings')
                            ->whereIn('key', array_keys($defaults))
                            ->pluck('value', 'key')
                            ->all();

                        $uiSettings = array_merge($defaults, array_filter($settings));
                    } else {
                        $uiSettings = $defaults;
                    }
                } catch (Throwable) {
                    // Fall back to defaults if database/settings table is not ready.
                    $uiSettings = $defaults;
                }

                try {
                    if (Schema::hasTable('schools')) {
                        $schoolBranding = DB::table('schools')->first();

                        if ($schoolBranding !== null) {
                            $logo = property_exists($schoolBranding, 'logo') ? $schoolBranding->logo : null;
                            if ((!is_string($logo) || $logo === '') && property_exists($schoolBranding, 'logo_path')) {
                                $logo = $schoolBranding->logo_path;
                            }

                            $background = property_exists($schoolBranding, 'bg_countdown')
                                ? $schoolBranding->bg_countdown
                                : null;

                            if (is_string($logo) && $logo !== '') {
                                $uiSettings['app_logo'] = $logo;
                            }
                            if (is_string($background) && $background !== '') {
                                $uiSettings['background_image'] = $background;
                            }
                        }
                    }
                } catch (Throwable) {
                    // Keep existing ui settings if school branding is not accessible.
                }
            }

            if ($currentSchoolType === null) {
                try {
                    if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'tipe_sekolah')) {
                        $currentSchoolType = DB::table('schools')->value('tipe_sekolah');
                    }
                } catch (Throwable) {
                    $currentSchoolType = null;
                }
            }

            $resolveMediaUrl = static function (?string $path, string $fallback): string {
                if (!is_string($path) || $path === '') {
                    return asset($fallback);
                }

                if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
                    return $path;
                }

                $normalized = ltrim($path, '/');

                if (Str::startsWith($normalized, ['assets/', 'storage/'])) {
                    return asset($normalized);
                }

                return asset('storage/' . $normalized);
            };

            $view->with('uiSettings', $uiSettings);
            $view->with('uiSettingsUrl', [
                'app_logo' => $resolveMediaUrl($uiSettings['app_logo'] ?? null, 'assets/img/logo.png'),
                'background_image' => $resolveMediaUrl($uiSettings['background_image'] ?? null, 'assets/img/illustrations/auth-login-illustration-light.png'),
            ]);
            $view->with('currentSchoolType', $currentSchoolType);

            // Share school name for branding
            static $schoolName = null;
            if ($schoolName === null) {
                try {
                    if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'nama_sekolah')) {
                        $schoolName = DB::table('schools')->value('nama_sekolah') ?: 'SIK-T';
                    } else {
                        $schoolName = 'SIK-T';
                    }
                } catch (Throwable) {
                    $schoolName = 'SIK-T';
                }
            }
            $view->with('schoolName', $schoolName);
        });
    }
}
