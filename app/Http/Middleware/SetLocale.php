<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = (array) config('sik.supported_locales', ['id', 'en']);
        $locale = (string) $request->session()->get('locale', (string) config('app.locale', 'id'));

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = (string) config('app.locale', 'id');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
